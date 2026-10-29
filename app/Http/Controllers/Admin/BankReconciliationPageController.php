<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\BankImport;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use App\Services\BankLedgerPostingService;
use App\Services\BankStatementVerificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BankReconciliationPageController extends Controller
{
    public function __construct(private BankLedgerPostingService $posting) {}

    public function index(Request $request) {
        // Same entry point as the receipts page: pick a client credential first, then a business under it.
        $credentialId=(int)$request->query('client_credential_id',0);
        $businessId=(int)$request->query('client_id',0);
        $bankAccountId=(int)$request->query('bank_account_id',0);
        $businessIds=$credentialId
            ? Client::where('client_credential_id',$credentialId)->where('status',1)->pluck('id')->all()
            : [];
        if($businessId)
            $businessIds=in_array($businessId,$businessIds,true)?[$businessId]:[];
        // Load bank accounts across every business in scope so bank names resolve on
        // each row even when viewing "All businesses" (a single selection narrows this).
        $bankAccounts=$businessIds ? DB::table('reconciliation_bank_accounts')->whereIn('client_id',$businessIds)->orderBy('bank_name')->get() : collect();
        if($bankAccountId && !$bankAccounts->contains('id',$bankAccountId)) {
            abort(422,'Choose a bank account belonging to the selected business.');
        }
        if(!$credentialId||!$businessIds){
            // Nothing selected yet — render the picker with an empty workspace.
            return view('admin.accounting.bank_reconciliation',[
                'credentialId'=>$credentialId,'businessId'=>$businessId,'bankAccounts'=>$bankAccounts,'bankAccountId'=>$bankAccountId,
                'entries'=>DB::table('bank_entries')->whereRaw('1 = 0')->paginate(25),
                'heads'=>collect(),'stats'=>collect(),'approvalStats'=>collect(),
                'imports'=>collect(),'receipts'=>collect(),'splits'=>collect(),
                'claimedReceiptIds'=>collect(),'notes'=>collect(),'fuzzyMatches'=>[],
                'reconcileFilter'=>'unreconciled','totalCount'=>0,'unreconciledCount'=>0,'reconciledCount'=>0,'importMap'=>collect(),
            ]);
        }
        // Overview is calculated across ALL bank accounts for this business, regardless of
        // the current account/status filter. One row per bank; no cross-business totals.
        $bankOverview=collect();
        if ($businessId && count($businessIds) === 1) {
            $entryTotals=DB::table('bank_entries')->where('client_id',$businessId)
                ->whereNotNull('bank_account_id')
                ->select('bank_account_id')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw("SUM(CASE WHEN reconciliation_status = 'approved' THEN 1 ELSE 0 END) AS approved")
                ->selectRaw("SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) AS matched")
                ->selectRaw("SUM(CASE WHEN status = 'unmatched' THEN 1 ELSE 0 END) AS unmatched")
                ->selectRaw("SUM(CASE WHEN status = 'needs_review' THEN 1 ELSE 0 END) AS needs_review")
                ->selectRaw("SUM(CASE WHEN reconciliation_status IS NULL OR reconciliation_status <> 'approved' THEN 1 ELSE 0 END) AS pending")
                ->groupBy('bank_account_id')->get()->keyBy('bank_account_id');
            $importTotals=DB::table('bank_imports')->where('client_id',$businessId)
                ->whereNotNull('bank_account_id')->select('bank_account_id')
                ->selectRaw('COUNT(*) AS statement_count')
                ->selectRaw('MAX(created_at) AS last_upload')
                ->groupBy('bank_account_id')->get()->keyBy('bank_account_id');
            $bankOverview=$bankAccounts->map(function($bank)use($entryTotals,$importTotals){
                $counts=$entryTotals->get($bank->id);
                $imports=$importTotals->get($bank->id);
                return (object)[
                    'bank'=>$bank,'total'=>(int)($counts->total??0),
                    'approved'=>(int)($counts->approved??0),
                    'matched'=>(int)($counts->matched??0),
                    'unmatched'=>(int)($counts->unmatched??0),
                    'needs_review'=>(int)($counts->needs_review??0),
                    'pending'=>(int)($counts->pending??0),
                    'statement_count'=>(int)($imports->statement_count??0),
                    'last_upload'=>$imports->last_upload??null,
                ];
            });
        }
        $reconcileFilter = $request->input('reconcile_status', 'unreconciled');
        $scopeEntries = fn() => DB::table('bank_entries')
            ->whereIn('client_id', $businessIds)
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId));

        $entries = $scopeEntries()
            ->when($reconcileFilter === 'unreconciled', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('reconciliation_status')
                        ->orWhere('reconciliation_status', '!=', 'approved');
                });
            })
            ->when($reconcileFilter === 'reconciled', function ($q) {
                $q->where('reconciliation_status', 'approved');
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $totalCount = $scopeEntries()->count();
        $reconciledCount = $scopeEntries()->where('reconciliation_status', 'approved')->count();
        $unreconciledCount = $totalCount - $reconciledCount;

        // Account heads must be global + this credential's, matching the receipt page.
        $heads = AccountHead::where('is_active', 1)->whereNotNull('account_type_id')
            ->where(function ($q) use ($credentialId) {
                $q->whereNull('client_credential_id');
                if ($credentialId) $q->orWhere('client_credential_id', $credentialId);
            })
            ->orderBy('code')->get(['id', 'code', 'name']);

        $stats = $scopeEntries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $approvalStats = $scopeEntries()->selectRaw('reconciliation_status, count(*) as total')->groupBy('reconciliation_status')->pluck('total', 'reconciliation_status');
        $imports = DB::table('bank_imports')->whereIn('client_id', $businessIds)->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))->latest()->limit(10)->get();

        // Claimed receipts that are already reconciled
        $claimedReceiptIds = DB::table('bank_entries')->whereIn('client_id', $businessIds)
            ->whereNotNull('receipt_id')
            ->where('reconciliation_status', 'approved')
            ->pluck('receipt_id')
            ->merge(
                DB::table('bank_allocation_lines as l')->join('bank_entries as b', 'b.id', '=', 'l.bank_entry_id')
                    ->whereIn('b.client_id', $businessIds)
                    ->where('b.reconciliation_status', 'approved')
                    ->whereNotNull('l.receipt_id')
                    ->pluck('l.receipt_id')
            )
            ->map(fn($id) => (int)$id)->unique()->flip();

        // Eligible receipts: must be ready, paid via bank, and not already reconciled
        $receipts = Receipt::whereIn('client_id', $businessIds)
            ->where('status', 'ready')
            ->whereHas('detail', function ($q) {
                $q->where('paid', 1)
                  ->where('payment_method', 'bank')
                  ->whereNotNull('total_amount');
            })
            ->with(['detail.accountHead', 'detail'])
            ->latest('id')
            ->limit(500)
            ->get()
            ->filter(fn($r) => $r->detail && $r->detail->total_amount !== null && !isset($claimedReceiptIds[$r->id]));

        $entryIds = $entries->getCollection()->pluck('id');
        $splits = DB::table('bank_allocation_lines')->whereIn('bank_entry_id', $entryIds)->get()->groupBy('bank_entry_id');
        $notes = DB::table('bank_review_notes')->whereIn('bank_entry_id', $entryIds)->orderByDesc('id')->get()->groupBy('bank_entry_id');

        $importMap = DB::table('bank_imports')->whereIn('id', $entries->getCollection()->pluck('bank_import_id')->filter())->get()->keyBy('id');

        // Clean match scoring: finds eligible receipts matching transaction amount & narrative
        $fuzzyMatches = [];
        foreach ($entries as $entry) {
            $absAmt = abs((float)$entry->amount);
            $exactList = $receipts->filter(fn($r) => abs((float)$r->detail->total_amount - $absAmt) < 0.01);
            $fuzzyMatches[(int)$entry->id] = $exactList;
        }

        return view('admin.accounting.bank_reconciliation', compact(
            'credentialId', 'businessId', 'bankAccountId', 'bankAccounts',
            'entries', 'heads', 'stats', 'approvalStats', 'imports', 'receipts',
            'splits', 'notes', 'fuzzyMatches', 'claimedReceiptIds',
            'reconcileFilter', 'totalCount', 'unreconciledCount', 'reconciledCount', 'importMap'
        ));
    }

    /**
     * Score uploaded receipts against a bank entry for fuzzy matching suggestions.
     * Weights: amount 40%, date 30%, reference 15%, supplier 15%.
     *
     * @return array<int,array{id:int,receipt_number:string|null,supplier:string|null,amount:float,score:float,reasons:array}>
     */
    private function scoreReceipts(object $entry, \Illuminate\Support\Collection $receipts): array {
        $bankAmt=abs((float)$entry->amount);
        $bankDate=\Illuminate\Support\Carbon::parse($entry->transaction_date);
        $desc=strtolower((string)($entry->description??''));
        $ref=strtolower((string)($entry->reference??''));
        $out=[];
        foreach($receipts as $r){
            $detail=$r->detail??null;
            $receiptAmt=(float)($detail?->total_amount ?? 0);
            if($receiptAmt<=0) continue;
            // Amount score (0-1), only within 10% tolerance.
            $amtDiff=abs($bankAmt-$receiptAmt);
            $amtScore=$amtDiff<=max($bankAmt,$receiptAmt)*0.10 ? max(0,1-$amtDiff/max($bankAmt,$receiptAmt)) : 0;
            if($amtScore===0) continue;
            // Date score.
            $rDate=$detail?->invoice_date ? \Illuminate\Support\Carbon::parse($detail->invoice_date) : null;
            if($rDate){
                $days=abs($bankDate->diffInDays($rDate));
                $dateScore=$days===0 ? 1 : ($days<=3 ? 0.7 : ($days<=7 ? 0.4 : 0));
            } else {
                $dateScore=0.5;
            }
            // Reference score.
            $rNum=strtolower((string)($r->receipt_number??''));
            $refScore=0;
            if($rNum && ($ref && (str_contains($ref,$rNum) || str_contains($rNum,$ref)))) $refScore=1;
            elseif($rNum && str_contains($desc,$rNum)) $refScore=0.5;
            // Supplier score.
            $sup=strtolower((string)($r->supplier??''));
            $supScore=0;
            if($sup && str_contains($desc,$sup)) $supScore=1;
            elseif($sup && similar_text($desc,$sup)/max(strlen($desc),strlen($sup),1)>0.6) $supScore=0.5;
            $score=round($amtScore*0.40+$dateScore*0.30+$refScore*0.15+$supScore*0.15,2);
            if($score>=0.50){
                $out[]=['id'=>(int)$r->id,'receipt_number'=>$r->receipt_number,'supplier'=>$r->supplier,'amount'=>$receiptAmt,'score'=>$score,
                    'reasons'=>array_filter(['amt'=>round($amtScore,2),'date'=>round($dateScore,2),'ref'=>round($refScore,2),'sup'=>round($supScore,2)])];
            }
        }
        usort($out,fn($a,$b)=>$b['score']<=>$a['score']);
        return array_slice($out,0,10);
    }
    public function createBankAccount(Request $request) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'bank_name'=>'required|string|max:100',
            'account_last_four'=>'nullable|digits:4',
            'sort_code'=>'nullable|string|max:32',
        ]);
        $client=Client::findOrFail($data['client_id']);
        // Only allow account management within the selected credential/business.
        abort_unless((int)$client->client_credential_id===(int)$request->input('client_credential_id'),403);
        // Account label is no longer collected from the app; keep the legacy
        // column populated so the NOT NULL schema remains valid.
        $data['account_label']='';
        $data['currency']='GBP';
        if(empty($data['sort_code'])
            || !\Illuminate\Support\Facades\Schema::hasColumn('reconciliation_bank_accounts','sort_code'))
            unset($data['sort_code']);
        if(empty($data['account_last_four'])) unset($data['account_last_four']);
        $data['created_at']=now();$data['updated_at']=now();
        DB::table('reconciliation_bank_accounts')->insert($data);
        return redirect()->route('admin.bank-reconciliation.index',[
            'client_credential_id'=>$client->client_credential_id,'client_id'=>$client->id,
        ])->with('success','Bank account added. Select it when uploading statements.');
    }
    public function action(Request $request, int $entryId) {
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'action' => 'required|in:match,create,split,find_match,transfer,add_note,unmatch,categorize',
            'receipt_id' => 'nullable|integer',
            'receipt_ids' => 'nullable|array',
            'account_head_id' => 'nullable|integer',
            'supplier' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'review_note' => 'nullable|string|max:2000',
            'destination_bank_account_id' => 'nullable|integer',
            'splits' => 'nullable|array',
            'splits.*.amount' => 'nullable|numeric|gt:0',
            'splits.*.account_head_id' => 'nullable|integer',
            'splits.*.supplier' => 'nullable|string|max:255',
            'splits.*.invoice_date' => 'nullable|date',
            'splits.*.memo' => 'nullable|string|max:500',
        ]);

        $client = Client::findOrFail($data['client_id']);
        $posted = 0;
        $reversed = 0;
        $isAjax = $request->ajax() || $request->wantsJson();

        try {
            DB::transaction(function() use ($request, $entryId, $client, $data, &$posted, &$reversed) {
                $entry = DB::table('bank_entries')->where('client_id', $client->id)->lockForUpdate()->find($entryId);
                abort_unless($entry, 404, 'Transaction not found.');
                $action = $data['action'];

                // 1. ADD INTERNAL NOTE
                if ($action === 'add_note') {
                    $note = trim($data['review_note'] ?? '');
                    if ($note === '') throw ValidationException::withMessages(['review_note' => 'Please enter a note.']);
                    DB::table('bank_review_notes')->insert([
                        'bank_entry_id' => $entryId,
                        'author_id' => auth()->id(),
                        'type' => 'internal',
                        'body' => $note,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    DB::table('bank_match_audits')->insert([
                        'bank_entry_id' => $entryId,
                        'actor_id' => auth()->id(),
                        'action' => 'add_note',
                        'old_values' => null,
                        'new_values' => json_encode(['note' => $note]),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    return;
                }

                // 2. UNMATCH / UNDO RECONCILIATION
                if ($action === 'unmatch') {
                    $this->posting->reverseEntry($entry);
                    DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->delete();
                    if (!empty($entry->linked_bank_entry_id) && ($entry->transfer_type ?? null) === 'internal_transfer') {
                        DB::table('bank_entries')->where('id', $entry->linked_bank_entry_id)
                            ->where('status', 'unmatched')->whereNull('receipt_id')->delete();
                    }
                    $reset = [
                        'status' => 'unmatched',
                        'receipt_id' => null,
                        'account_head_id' => null,
                        'matched_by' => null,
                        'matched_at' => null,
                        'transfer_type' => null,
                        'linked_bank_entry_id' => null,
                        'reconciliation_status' => 'draft',
                        'approved_by' => null,
                        'approved_at' => null,
                        'journal_posted_at' => null,
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($reset);
                    DB::table('bank_match_audits')->insert([
                        'bank_entry_id' => $entryId,
                        'actor_id' => auth()->id(),
                        'action' => 'unmatch',
                        'old_values' => json_encode($entry),
                        'new_values' => json_encode($reset),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    return;
                }

                $absAmount = abs((float)$entry->amount);
                $expectedPence = (int)round($absAmount * 100);

                // 3. MATCH EXISTING SUGGESTED RECEIPT
                if ($action === 'match') {
                    $receiptId = (int)($data['receipt_id'] ?? 0);
                    abort_unless($receiptId > 0, 422, 'Select a receipt to match.');
                    $receipt = Receipt::with('detail')->where('client_id', $client->id)->findOrFail($receiptId);
                    
                    abort_unless($receipt->detail && (int)$receipt->detail->paid === 1 && $receipt->detail->payment_method === 'bank', 422, 'Only paid bank receipts can be matched.');
                    abort_if(DB::table('bank_entries')->where('receipt_id', $receipt->id)->where('id', '!=', $entryId)->where('reconciliation_status', 'approved')->exists(), 409, 'Receipt is already reconciled to another transaction.');

                    $headId = $data['account_head_id'] ?? $receipt->detail->account_head_id;
                    abort_unless($headId, 422, 'Account head is required for this receipt.');

                    $change = [
                        'status' => 'matched',
                        'receipt_id' => $receipt->id,
                        'account_head_id' => $headId,
                        'matched_by' => auth()->id(),
                        'matched_at' => now(),
                        'reconciliation_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    $freshEntry = DB::table('bank_entries')->find($entryId);
                    $posted = $this->posting->postEntry($freshEntry, auth()->id());
                    return;
                }

                // 4. CREATE NEW RECEIPT DIRECTLY
                if ($action === 'create') {
                    $headId = (int)($data['account_head_id'] ?? 0);
                    abort_unless($headId > 0, 422, 'Please select an account head.');
                    $supplier = trim($data['supplier'] ?? '') ?: ($entry->description ?: 'Vendor');
                    $invoiceDate = $data['invoice_date'] ?: $entry->transaction_date;

                    $receipt = Receipt::create([
                        'client_id' => $client->id,
                        'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                        'receipt_date' => $invoiceDate,
                        'supplier' => $supplier,
                        'status' => 'ready',
                        'created_by' => auth()->id(),
                    ]);

                    ReceiptDetail::create([
                        'receipt_id' => $receipt->id,
                        'account_head_id' => $headId,
                        'invoice_date' => $invoiceDate,
                        'net_amount' => $absAmount,
                        'tax_amount' => 0,
                        'vat_amount' => 0,
                        'total_amount' => $absAmount,
                        'paid' => 1,
                        'payment_method' => 'bank',
                        'description' => $entry->description,
                    ]);

                    $change = [
                        'status' => 'matched',
                        'receipt_id' => $receipt->id,
                        'account_head_id' => $headId,
                        'matched_by' => auth()->id(),
                        'matched_at' => now(),
                        'reconciliation_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    $freshEntry = DB::table('bank_entries')->find($entryId);
                    $posted = $this->posting->postEntry($freshEntry, auth()->id());
                    return;
                }

                // 5. SPLIT INTO MULTIPLE RECEIPTS / CATEGORIES
                if ($action === 'split') {
                    $lines = $data['splits'] ?? [];
                    abort_if(count($lines) < 2, 422, 'Add at least 2 split lines.');

                    $sumPence = 0;
                    foreach ($lines as $line) {
                        $pence = (int)round((float)($line['amount'] ?? 0) * 100);
                        abort_if($pence <= 0, 422, 'Each split amount must be greater than zero.');
                        $sumPence += $pence;
                    }

                    if ($sumPence !== $expectedPence) {
                        $diff = ($sumPence - $expectedPence) / 100;
                        if ($diff > 0) {
                            throw ValidationException::withMessages(['splits' => 'Split total (£' . number_format($sumPence / 100, 2) . ') exceeds bank amount by £' . number_format($diff, 2) . '.']);
                        } else {
                            throw ValidationException::withMessages(['splits' => 'Split total (£' . number_format($sumPence / 100, 2) . ') falls short of bank amount by £' . number_format(abs($diff), 2) . '.']);
                        }
                    }

                    DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->delete();
                    $normalized = [];

                    foreach ($lines as $line) {
                        $lineAmt = (float)$line['amount'];
                        $headId = (int)($line['account_head_id'] ?? 0);
                        abort_unless($headId > 0, 422, 'Select an account head for each split line.');
                        $supplier = trim($line['supplier'] ?? '') ?: ($entry->description ?: 'Vendor');
                        $invDate = $line['invoice_date'] ?: $entry->transaction_date;

                        $r = Receipt::create([
                            'client_id' => $client->id,
                            'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                            'receipt_date' => $invDate,
                            'supplier' => $supplier,
                            'status' => 'ready',
                            'created_by' => auth()->id(),
                        ]);

                        ReceiptDetail::create([
                            'receipt_id' => $r->id,
                            'account_head_id' => $headId,
                            'invoice_date' => $invDate,
                            'net_amount' => $lineAmt,
                            'tax_amount' => 0,
                            'vat_amount' => 0,
                            'total_amount' => $lineAmt,
                            'paid' => 1,
                            'payment_method' => 'bank',
                            'description' => $line['memo'] ?? $entry->description,
                        ]);

                        $normalized[] = [
                            'bank_entry_id' => $entryId,
                            'account_head_id' => $headId,
                            'receipt_id' => $r->id,
                            'amount' => number_format($lineAmt, 2, '.', ''),
                            'memo' => $line['memo'] ?? null,
                            'tax_percent' => 0,
                            'tax_amount' => 0,
                            'vat_amount' => 0,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    DB::table('bank_allocation_lines')->insert($normalized);
                    $change = [
                        'status' => 'split',
                        'receipt_id' => null,
                        'account_head_id' => null,
                        'matched_by' => auth()->id(),
                        'matched_at' => now(),
                        'reconciliation_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    $freshEntry = DB::table('bank_entries')->find($entryId);
                    $posted = $this->posting->postEntry($freshEntry, auth()->id());
                    return;
                }

                // 6. FIND & MATCH (MULTI-RECEIPT MATCH)
                if ($action === 'find_match') {
                    $receiptIds = $data['receipt_ids'] ?? [];
                    abort_if(empty($receiptIds), 422, 'Select at least one receipt to match.');

                    $selectedReceipts = Receipt::with('detail')->where('client_id', $client->id)->whereIn('id', $receiptIds)->get();
                    abort_unless($selectedReceipts->count() === count($receiptIds), 422, 'One or more selected receipts could not be found.');

                    $sumPence = 0;
                    foreach ($selectedReceipts as $sr) {
                        abort_unless($sr->detail && (int)$sr->detail->paid === 1 && $sr->detail->payment_method === 'bank', 422, 'Receipt #' . $sr->receipt_number . ' is not a paid bank receipt.');
                        $sumPence += (int)round((float)$sr->detail->total_amount * 100);
                    }

                    if ($sumPence !== $expectedPence) {
                        $diff = ($sumPence - $expectedPence) / 100;
                        if ($diff > 0) {
                            throw ValidationException::withMessages(['receipt_ids' => 'Selected receipts total (£' . number_format($sumPence / 100, 2) . ') exceeds bank amount (£' . number_format($absAmount, 2) . ') by £' . number_format($diff, 2) . '. Total cannot exceed!']);
                        } else {
                            throw ValidationException::withMessages(['receipt_ids' => 'Selected receipts total (£' . number_format($sumPence / 100, 2) . ') falls short of bank amount (£' . number_format($absAmount, 2) . ') by £' . number_format(abs($diff), 2) . '.']);
                        }
                    }

                    if ($selectedReceipts->count() === 1) {
                        $r = $selectedReceipts->first();
                        $change = [
                            'status' => 'matched',
                            'receipt_id' => $r->id,
                            'account_head_id' => $r->detail->account_head_id,
                            'matched_by' => auth()->id(),
                            'matched_at' => now(),
                            'reconciliation_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'updated_at' => now(),
                        ];
                    } else {
                        DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->delete();
                        $lines = [];
                        foreach ($selectedReceipts as $sr) {
                            $lines[] = [
                                'bank_entry_id' => $entryId,
                                'account_head_id' => $sr->detail->account_head_id,
                                'receipt_id' => $sr->id,
                                'amount' => number_format((float)$sr->detail->total_amount, 2, '.', ''),
                                'memo' => '#' . $sr->receipt_number . ' ' . $sr->supplier,
                                'tax_percent' => 0,
                                'tax_amount' => 0,
                                'vat_amount' => 0,
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        DB::table('bank_allocation_lines')->insert($lines);
                        $change = [
                            'status' => 'split',
                            'receipt_id' => null,
                            'account_head_id' => null,
                            'matched_by' => auth()->id(),
                            'matched_at' => now(),
                            'reconciliation_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    $freshEntry = DB::table('bank_entries')->find($entryId);
                    $posted = $this->posting->postEntry($freshEntry, auth()->id());
                    return;
                }

                // 7. TRANSFER TO ANOTHER BANK ACCOUNT
                if ($action === 'transfer') {
                    $destBankId = (int)($data['destination_bank_account_id'] ?? 0);
                    abort_unless($destBankId > 0 && $destBankId !== (int)$entry->bank_account_id, 422, 'Select a different destination bank account.');
                    $destBank = DB::table('reconciliation_bank_accounts')->where('client_id', $client->id)->findOrFail($destBankId);
                    $sourceBankName = optional(DB::table('reconciliation_bank_accounts')->find($entry->bank_account_id))->bank_name ?: 'Bank Account';

                    // Create opposite counterparty entry in the destination account
                    $counterId = DB::table('bank_entries')->insertGetId([
                        'client_id' => $client->id,
                        'bank_account_id' => $destBankId,
                        'transaction_date' => $entry->transaction_date,
                        'description' => 'Transfer from ' . $sourceBankName . ': ' . $entry->description,
                        'reference' => 'Transfer from #' . $entry->id,
                        'amount' => -1 * (float)$entry->amount,
                        'currency' => $entry->currency ?? 'GBP',
                        'status' => 'unmatched',
                        'transfer_type' => 'internal_transfer',
                        'linked_bank_entry_id' => $entry->id,
                        'reconciliation_status' => 'draft',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $change = [
                        'status' => 'categorized',
                        'transfer_type' => 'internal_transfer',
                        'linked_bank_entry_id' => $counterId,
                        'reconciliation_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'review_note' => 'Transferred to ' . $destBank->bank_name,
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    return;
                }

                // 8. CATEGORIZE DIRECTLY (LEGACY COMPATIBILITY)
                if ($action === 'categorize') {
                    $headId = (int)($data['account_head_id'] ?? 0);
                    abort_unless($headId > 0, 422, 'Select an account head.');
                    $change = [
                        'status' => 'categorized',
                        'account_head_id' => $headId,
                        'review_note' => $data['review_note'] ?? null,
                        'reconciliation_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('bank_entries')->where('id', $entryId)->update($change);
                    $freshEntry = DB::table('bank_entries')->find($entryId);
                    $posted = $this->posting->postEntry($freshEntry, auth()->id());
                    return;
                }
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isAjax) return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($isAjax) return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
            throw $e;
        } catch (\Throwable $e) {
            if ($isAjax) return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            return back()->with('error', $e->getMessage());
        }

        $message = match($data['action']) {
            'match' => 'Transaction matched with receipt and reconciled successfully.',
            'create' => 'Receipt created and transaction reconciled successfully.',
            'split' => 'Split receipts created and transaction reconciled successfully.',
            'find_match' => 'Selected receipts matched and transaction reconciled successfully.',
            'transfer' => 'Internal transfer recorded and posted to destination bank account.',
            'add_note' => 'Internal note saved.',
            'unmatch' => 'Reconciliation undone. Transaction reopened for review.',
            default => 'Reconciliation action saved successfully.',
        };

        if ($isAjax) {
            return response()->json(['success' => true, 'message' => $message, 'entry_id' => $entryId, 'action' => $data['action']]);
        }
        return redirect()->back()->with('success', $message);
    }
    private function splitBalances(object $entry):bool {
        $lines=DB::table('bank_allocation_lines')->where('bank_entry_id',$entry->id)->get();
        if($lines->count()<2)return false;
        $sum=0;foreach($lines as $line)$sum+=(int)round((float)$line->amount*100);
        return $sum===(int)round(abs((float)$entry->amount)*100);
    }
    /* ------------------------------------------------------------------ *
     * Statement verification (accountant workspace)
     * ------------------------------------------------------------------ */

    public function verification(int $importId) {
        $import=BankImport::findOrFail($importId);
        $entries=DB::table('bank_entries')->where('bank_import_id',$importId)
            ->orderByRaw('COALESCE(source_page,1)')->orderByRaw('COALESCE(source_position,0)')->orderBy('id')->get();
        $pages=DB::table('bank_statement_pages')->where('bank_import_id',$importId)->orderBy('page_no')->get();
        $exceptions=DB::table('bank_statement_exceptions')->where('bank_import_id',$importId)
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')->orderByDesc('id')->get();
        $revisions=DB::table('bank_entry_revisions')->whereIn('bank_entry_id',$entries->pluck('id'))
            ->latest('id')->limit(100)->get();
        return view('admin.accounting.bank_verification',[
            'import'=>$import,'entries'=>$entries,'pages'=>$pages,'exceptions'=>$exceptions,'revisions'=>$revisions,
            'blocking'=>app(BankStatementVerificationService::class)->blockingCount($import),
            'bank'=>DB::table('reconciliation_bank_accounts')->find($import->bank_account_id),
            'client'=>$import->client,
        ]);
    }

    public function streamStatement(int $importId) {
        $item=BankImport::findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return response()->file(Storage::disk('local')->path($item->storage_path),['Content-Type'=>'application/pdf']);
    }

    public function verifyImport(Request $request,int $importId) {
        $data=$request->validate(['verification_note'=>'nullable|string|max:2000']);
        $import=BankImport::findOrFail($importId);
        try {
            app(BankStatementVerificationService::class)->verify($import,auth()->id(),$data['verification_note']??null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error',$exception->getMessage());
        }
        return back()->with('success','Statement verified. Its rows can now be reconciled.');
    }

    public function updateEntry(Request $request,int $entryId) {
        $data=$request->validate([
            'transaction_date'=>'required|date','description'=>'required|string|max:500',
            'amount'=>'required|numeric|between:-999999999,999999999','reason'=>'required|string|max:1000',
        ]);
        $entry=DB::table('bank_entries')->find($entryId);
        abort_unless($entry,404);
        abort_if(($entry->reconciliation_status??'draft')==='approved',409,'Approved reconciliations cannot be edited.');
        $import=BankImport::findOrFail($entry->bank_import_id);
        DB::transaction(function()use($entry,$import,$data){
            $date=Carbon::parse($data['transaction_date'])->toDateString();
            $amount=round((float)$data['amount'],2);
            DB::table('bank_entries')->where('id',$entry->id)->update([
                'transaction_date'=>$date,'description'=>$data['description'],
                'amount'=>number_format($amount,2,'.',''),'updated_at'=>now(),
            ]);
            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id'=>$entry->id,'actor_id'=>auth()->id(),'action'=>'edit',
                'original_values'=>json_encode(['transaction_date'=>$entry->transaction_date,
                    'description'=>$entry->description,'amount'=>(float)$entry->amount,'balance_after'=>$entry->balance_after]),
                'corrected_values'=>json_encode(['transaction_date'=>$date,'description'=>$data['description'],'amount'=>$amount]),
                'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now(),
            ]);
            $verifier=app(BankStatementVerificationService::class);
            $verifier->reopen($import);
            $verifier->refresh($import);
        });
        return redirect()->route('admin.bank-reconciliation.verification',$import->id)
            ->with('success','Row corrected. Original values, corrected values and your reason were saved.');
    }

    public function storeEntry(Request $request,int $importId) {
        $data=$request->validate([
            'transaction_date'=>'required|date','description'=>'required|string|max:500',
            'amount'=>'required|numeric|between:-999999999,999999999','reason'=>'nullable|string|max:1000',
        ]);
        $import=BankImport::findOrFail($importId);
        DB::transaction(function()use($import,$data){
            $date=Carbon::parse($data['transaction_date'])->toDateString();
            $amount=round((float)$data['amount'],2);
            $entryId=DB::table('bank_entries')->insertGetId([
                'client_id'=>$import->client_id,'bank_import_id'=>$import->id,
                'bank_account_id'=>$import->bank_account_id,
                'transaction_date'=>$date,'description'=>$data['description'],'reference'=>null,
                'amount'=>number_format($amount,2,'.',''),'currency'=>'GBP','status'=>'needs_review',
                'extraction_source'=>'manual','reconciliation_status'=>'draft',
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),'action'=>'add',
                'original_values'=>null,
                'corrected_values'=>json_encode(['transaction_date'=>$date,'description'=>$data['description'],'amount'=>$amount]),
                'reason'=>$data['reason']??'Row added by accountant','created_at'=>now(),'updated_at'=>now(),
            ]);
            $verifier=app(BankStatementVerificationService::class);
            $verifier->reopen($import);
            $verifier->refresh($import);
        });
        return redirect()->route('admin.bank-reconciliation.verification',$import->id)
            ->with('success','Row added to the statement. Checks have been re-run.');
    }

    public function destroyEntry(Request $request,int $entryId) {
        $data=$request->validate(['reason'=>'required|string|max:1000']);
        $entry=DB::table('bank_entries')->find($entryId);
        abort_unless($entry,404);
        abort_if(($entry->reconciliation_status??'draft')==='approved',409,'Approved reconciliations cannot be removed.');
        $import=BankImport::findOrFail($entry->bank_import_id);
        DB::transaction(function()use($entry,$import,$data){
            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id'=>null,'actor_id'=>auth()->id(),'action'=>'remove',
                'original_values'=>json_encode($entry),'corrected_values'=>null,
                'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('bank_entries')->where('id',$entry->id)->delete();
            $verifier=app(BankStatementVerificationService::class);
            $verifier->reopen($import);
            $verifier->refresh($import);
        });
        return redirect()->route('admin.bank-reconciliation.verification',$import->id)
            ->with('success','Row removed and the removal reason was recorded.');
    }

    private function getScopeClientIds(Request $request): array
    {
        $businessId = (int)$request->input('client_id');
        $credentialId = (int)$request->input('client_credential_id');
        if ($businessId) {
            return [$businessId];
        }
        if ($credentialId) {
            return Client::where('client_credential_id', $credentialId)
                ->where('status', 1)
                ->pluck('id')
                ->all();
        }
        return [];
    }

    public function verifyAllRows(Request $request, int $importId) {
        $import = DB::table('bank_imports')->find($importId);
        abort_unless($import, 404);
        DB::table('bank_entries')
            ->where('bank_import_id', $importId)
            ->where('status', 'needs_review')
            ->update([
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
                'status' => 'unmatched',
            ]);
        $count = DB::table('bank_entries')
            ->where('bank_import_id', $importId)
            ->where('status', 'unmatched')
            ->count();
        return redirect()->route('admin.bank-reconciliation.verification', $importId)
            ->with('success', 'All ' . ($count) . ' rows verified and ready for reconciliation.');
    }

    public function verifyEntry(int $entryId, Request $request) {
        $entry=DB::table('bank_entries')->find($entryId);
        abort_unless($entry, 404);
        DB::table('bank_entries')->where('id',$entryId)
            ->update(['verified_by'=>auth()->id(),'verified_at'=>now(),'updated_at'=>now(),'status'=>'unmatched']);
        DB::table('bank_entry_revisions')->insert([
            'bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),
            'action'=>'verify','original_values'=>null,'corrected_values'=>null,'reason'=>'Row checked against the PDF',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        // Try to verify the parent import so reconcile actions are unblocked once all rows are checked.
        $importVerified = false;
        $import = \App\Models\BankImport::find($entry->bank_import_id ?? 0);
        if ($import) {
            try {
                app(BankStatementVerificationService::class)->verify($import, auth()->id());
                $importVerified = true;
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Import has unresolved exceptions — entry is still marked unmatched.
            }
        }
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success'=>true,'message'=>'Row verified against the original statement.','entry_id'=>$entryId,'status'=>'unmatched','import_verified'=>$importVerified]);
        }
        return redirect()->back()->with('success','Row verified against the original statement.'.($importVerified?' Statement verified.':' Statement still has unresolved exceptions — check the verification workspace.'));
    }

    public function resolveException(Request $request,int $exceptionId) {
        $data=$request->validate(['resolution'=>'required|string|max:1000']);
        $exception=DB::table('bank_statement_exceptions')->find($exceptionId);
        abort_unless($exception,404);
        DB::table('bank_statement_exceptions')->where('id',$exceptionId)->update([
            'resolved_by'=>auth()->id(),'resolved_at'=>now(),'resolution'=>$data['resolution'],'updated_at'=>now(),
        ]);
        return back()->with('success','Exception resolved with your reason.');
    }

    public function approveImport(Request $request,int $importId) {
        $data=$request->validate(['client_id'=>'required|integer|exists:clients,id']);
        $client=Client::findOrFail($data['client_id']);
        $import=BankImport::findOrFail($importId);
        if($import->verification_status!==BankStatementVerificationService::VERIFIED){
            return back()->with('error','Verify this statement before approving its transactions.');
        }
        $approved=0;$skipped=0;$posted=0;
        DB::transaction(function()use($client,$importId,&$approved,&$skipped,&$posted){
            $entries=DB::table('bank_entries')->where('client_id',$client->id)
                ->where('bank_import_id',$importId)->lockForUpdate()->get();
            foreach($entries as $entry){
                $isApproved=($entry->reconciliation_status??'draft')==='approved';
                if($isApproved || !in_array($entry->status,['matched','categorized','split'])){
                    $skipped++;continue;
                }
                if($entry->status==='split' && !$this->splitBalances($entry)){
                    $skipped++;continue;
                }
                try { $this->posting->assertReadyForApproval($entry); }
                catch (ValidationException $exception) { $skipped++;continue; }
                $change=['reconciliation_status'=>'approved','approved_by'=>auth()->id(),
                    'approved_at'=>now(),'updated_at'=>now()];
                DB::table('bank_entries')->where('id',$entry->id)->update($change);
                $posted+=$this->posting->postEntry($entry,auth()->id());
                DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entry->id,'actor_id'=>auth()->id(),
                    'action'=>'approve','old_values'=>json_encode($entry),'new_values'=>json_encode($change),
                    'created_at'=>now(),'updated_at'=>now()]);
                $approved++;
            }
        });
        return redirect()->route('admin.bank-reconciliation.index',['client_credential_id'=>$client->client_credential_id,'client_id'=>$client->id])
            ->with('success',$approved.' transaction(s) approved'.($posted?' and '.$posted.' ledger entr'.($posted===1?'y':'ies').' posted':'').'.'.($skipped?' '.$skipped.' skipped (unreconciled or already approved).':''));
    }
    public function statement(int $importId){
        $item=BankImport::findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return Storage::disk('local')->download($item->storage_path,basename($item->filename));
    }
}
