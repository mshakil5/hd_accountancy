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
use Auth;

class BankReconciliationPageController extends Controller
{
    public function __construct(private BankLedgerPostingService $posting) {}

    public function index(Request $request) {
        $scope = $this->resolveScope($request);
        $shared = [
            'credentialId' => $scope['credentialId'],
            'businessId' => $scope['businessId'],
            'bankAccountId' => $scope['bankAccountId'],
            'bankAccounts' => $scope['bankAccounts'],
        ];
        if (!$scope['credentialId'] || !$scope['businessIds']) {
            // Nothing selected yet — render the picker with an empty workspace.
            return view('admin.accounting.bank_reconciliation', $shared + $this->emptyWorkspace());
        }
        return view('admin.accounting.bank_reconciliation', $shared + $this->workspaceData($request, $scope));
    }

    /**
     * Fragment endpoint for the workspace. Rows are still rendered by Blade (the
     * same partial the first paint uses); this only decides which rows the current
     * filter/page shows and hands back the HTML with the counters and pager state,
     * so the browser never reloads the page to change page or status.
     */
    public function rows(Request $request) {
        $scope = $this->resolveScope($request);
        if (!$scope['credentialId'] || !$scope['businessIds']) {
            return response()->json($this->workspacePayload($this->emptyWorkspace()));
        }
        return response()->json($this->workspacePayload($this->workspaceData($request, $scope)));
    }

    /** Workspace data when no client is chosen: empty rows, zero counters. */
    private function emptyWorkspace(): array {
        return [
            'entries' => DB::table('bank_entries')->whereRaw('1 = 0')->paginate(25),
            'bankAccounts' => collect(),
            'heads' => collect(),
            'receipts' => collect(),
            'splits' => collect(),
            'notes' => collect(),
            'fuzzyMatches' => [],
            'findMatches' => [],
            'suggestionMeta' => [],
            'receiptRemaining' => [],
            'importMap' => collect(),
            'reconcileFilter' => 'unreconciled',
            'totalCount' => 0,
            'unreconciledCount' => 0,
            'reconciledCount' => 0,
            'filteredCount' => 0,
            'currentPage' => 1,
            'lastPage' => 1,
            'onlyEntry' => null,
        ];
    }

    /** Counters + pager + rendered rows: one shape for every JS caller. */
    private function workspacePayload(array $data): array {
        $perPage = 25;
        $total = (int) $data['filteredCount'];
        $page = max(1, (int) $data['currentPage']);
        return [
            'html' => view('admin.accounting.partials.reconcile_rows', $data)->render(),
            'empty_html' => view('admin.accounting.partials.reconcile_empty')->render(),
            'stats' => [
                'unreconciled' => (int) $data['unreconciledCount'],
                'reconciled' => (int) $data['reconciledCount'],
                'total' => (int) $data['totalCount'],
            ],
            'pagination' => [
                'page' => $page,
                'last_page' => max(1, (int) $data['lastPage']),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total ? (($page - 1) * $perPage) + 1 : null,
                'to' => $total ? min($total, $page * $perPage) : null,
            ],
            'reconcile_status' => $data['reconcileFilter'],
        ];
    }

    /**
     * Just-finished action: re-render the one row it touched (plus refreshed
     * counters) so the browser swaps a single row instead of reloading the list.
     */
    private function actionPayload(Request $request, int $entryId): array {
        $scope = $this->resolveScope($request);
        if (!$scope['businessIds']) {
            $clientId = (int) $request->input('client_id', 0);
            if ($clientId) {
                $scope['businessIds'] = [$clientId];
                if (!$scope['credentialId']) {
                    $scope['credentialId'] = (int) Client::whereKey($clientId)->value('client_credential_id');
                }
            }
        }
        if ($scope['bankAccounts']->isEmpty() && $scope['businessIds']) {
            $scope['bankAccounts'] = DB::table('reconciliation_bank_accounts')
                ->whereIn('client_id', $scope['businessIds'])->orderBy('bank_name')->get();
        }
        $data = $this->workspaceData($request, $scope, $entryId);
        $entry = $data['onlyEntry'];
        $approved = $entry && ((string) ($entry->reconciliation_status ?? '') === 'approved');
        $visible = (bool) $entry && (
            $data['reconcileFilter'] === 'all'
            || (($data['reconcileFilter'] === 'reconciled') === $approved)
        );
        $payload = $this->workspacePayload($data);
        $payload['html'] = null;
        $payload['row'] = [
            'visible' => $visible,
            'html' => $visible
                ? view('admin.accounting.partials.reconcile_row', $data + ['entry' => $entry])->render()
                : null,
        ];
        return $payload;
    }

    /** Resolves credential → businesses → bank accounts, then normalises the filters. */
    private function resolveScope(Request $request): array {
        // Same entry point as the receipts page: pick a client credential first, then a business under it.
        $credentialId=(int)$request->query('client_credential_id',0);
        $businessId=(int)$request->query('client_id',0);
        $bankAccountId=(int)$request->query('bank_account_id',0);
        $businessIds=$credentialId
            ? Client::where('client_credential_id',$credentialId)->where('status',1)->pluck('id')->all()
            : [];
        if ($businessId) {
            if (in_array($businessId, $businessIds, true)) {
                $businessIds = [$businessId];
            } else {
                $businessId = 0;
                $request->query->remove('client_id');
            }
        }
        // Load bank accounts across every business in scope so bank names resolve on
        // each row even when viewing "All businesses" (a single selection narrows this).
        $bankAccounts=$businessIds ? DB::table('reconciliation_bank_accounts')->whereIn('client_id',$businessIds)->orderBy('bank_name')->get() : collect();
        if ($bankAccounts->isEmpty() && $credentialId) {
            $allClientIds = Client::where('client_credential_id', $credentialId)->where('status', 1)->pluck('id')->all();
            if ($allClientIds) {
                $bankAccounts = DB::table('reconciliation_bank_accounts')->whereIn('client_id', $allClientIds)->where('active', 1)->orderBy('bank_name')->get();
            }
        }
        // Always load banks for the credential so transfer dropdown never stays empty
        if ($credentialId && $bankAccounts->isEmpty()) {
            $allClientIds = Client::where('client_credential_id', $credentialId)->where('status', 1)->pluck('id')->all();
            if ($allClientIds) {
                $bankAccounts = DB::table('reconciliation_bank_accounts')->whereIn('client_id', $allClientIds)->where('active', 1)->orderBy('bank_name')->get();
            }
        }
        if($bankAccountId && !$bankAccounts->contains('id',$bankAccountId)) {
            $bankAccountId = 0;
            $request->query->remove('bank_account_id');
        }
        return compact('credentialId', 'businessId', 'bankAccountId', 'businessIds', 'bankAccounts');
    }

    /**
     * Everything one workspace view needs: the rows (paged, or a single one after an
     * action), the split/note/match context those rows render from, and the counts.
     */
    private function workspaceData(Request $request, array $scope, ?int $onlyEntryId = null): array {
        $credentialId = $scope['credentialId'];
        $businessId = $scope['businessId'];
        $bankAccountId = $scope['bankAccountId'];
        $businessIds = $scope['businessIds'];
        $bankAccounts = $scope['bankAccounts'];

        $reconcileFilter = $request->input('reconcile_status', 'unreconciled');
        if ($reconcileFilter === null || $reconcileFilter === '') {
            $reconcileFilter = 'all';
        }
        $scopeEntries = fn() => DB::table('bank_entries')
            ->whereIn('client_id', $businessIds)
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))
            ->when($request->filled('date_from'), fn($q) => $q->where('transaction_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->where('transaction_date', '<=', $request->input('date_to')));

        $filteredScope = fn() => $scopeEntries()
            ->when($reconcileFilter === 'unreconciled', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('reconciliation_status')
                        ->orWhere('reconciliation_status', '!=', 'approved');
                });
            })
            ->when($reconcileFilter === 'reconciled', function ($q) {
                $q->where('reconciliation_status', 'approved');
            })
            ->when($reconcileFilter === 'all', function ($q) {
                // No additional filter - show everything
            });

        if ($onlyEntryId) {
            // Single-row mode (action response): this row plus the filtered counts,
            // so the pager the browser is showing can be corrected in place.
            $rows = $filteredScope()->where('id', $onlyEntryId)->get();
            $filteredCount = $filteredScope()->count();
            $lastPage = max(1, (int) ceil($filteredCount / 25));
            $currentPage = min($lastPage, max(1, (int) $request->query('page', 1)));
            $entries = new \Illuminate\Pagination\LengthAwarePaginator($rows, $filteredCount, 25, $currentPage);
        } else {
            $entries = $filteredScope()
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();
            $filteredCount = $entries->total();
            $lastPage = max(1, $entries->lastPage());
            $currentPage = $entries->currentPage();
        }
        $onlyEntry = $onlyEntryId ? $entries->first() : null;

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


        // How much of each receipt is already consumed by reconciled bank lines.
        // A full match (bank_entries.receipt_id) and a partial allocation
        // (bank_allocation_lines) both reduce the remaining balance, so a partly
        // used receipt stays available for the rest of its value.
        $allocatedRows = DB::table('bank_entries')->whereIn('client_id', $businessIds)
                ->whereNotNull('receipt_id')->where('reconciliation_status', 'approved')
                ->get(['receipt_id', 'amount'])
                ->map(fn($e) => ['id' => (int)$e->receipt_id, 'amount' => abs((float)$e->amount)])
            ->concat(
                DB::table('bank_allocation_lines as l')->join('bank_entries as b', 'b.id', '=', 'l.bank_entry_id')
                    ->whereIn('b.client_id', $businessIds)->where('b.reconciliation_status', 'approved')
                    ->whereNotNull('l.receipt_id')
                    ->get(['l.receipt_id as id', 'l.amount'])
                    ->map(fn($l) => ['id' => (int)$l->id, 'amount' => (float)$l->amount])
            );
        $allocatedByReceipt = $allocatedRows->groupBy('id')->map(fn($rows) => round((float)$rows->sum('amount'), 2));

        $receiptRemaining = [];
        $remainingOf = function ($r) use ($allocatedByReceipt, &$receiptRemaining) {
            $total = round((float)$r->detail->total_amount, 2);
            return $receiptRemaining[(int)$r->id] = max(0.0, round($total - (float)($allocatedByReceipt[$r->id] ?? 0), 2));
        };

        // Eligible receipts: ready and paid. Fully consumed receipts drop out;
        // partly used ones keep their remaining balance.
        $receipts = Receipt::whereIn('client_id', $businessIds)
            ->where('status', 'ready')
            ->whereHas('detail', function ($q) {
                $q->where('paid', 1)
                  ->whereNotNull('total_amount');
            })
            ->with(['detail.accountHead', 'detail'])
            ->latest('id')
            ->limit(500)
            ->get()
            ->filter(fn($r) => $r->detail && $r->detail->total_amount !== null && $remainingOf($r) > 0.004)
            ->values();

        $entryIds = $entries->getCollection()->pluck('id');
        $splits = DB::table('bank_allocation_lines')->whereIn('bank_entry_id', $entryIds)->get()->groupBy('bank_entry_id');
        $noteRows = DB::table('bank_review_notes')->whereIn('bank_entry_id', $entryIds)->orderByDesc('id')->get();
        $noteAuthors = $noteRows->isNotEmpty()
            ? DB::table('users')->whereIn('id', $noteRows->pluck('author_id')->filter()->unique())->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id')
            : collect();
        $noteRows->each(function ($note) use ($noteAuthors) {
            $author = $noteAuthors->get($note->author_id);
            $note->author_name = $author ? (trim(($author->first_name ?? '') . ' ' . ($author->last_name ?? '')) ?: ($author->email ?? null)) : null;
        });
        $notes = $noteRows->groupBy('bank_entry_id');

        $importMap = DB::table('bank_imports')->whereIn('id', $entries->getCollection()->pluck('bank_import_id')->filter())->get()->keyBy('id');

        // Same-amount suggestions: receipts whose total equals the bank amount
        // whatever their date, ordered by the smallest gap between statement
        // date and receipt date. A receipt a few days off (statement date vs
        // invoice date) therefore still ranks first, and Find & Match lists
        // these suggested receipts at the top of its picker.
        // Queried directly because $receipts above is capped at the 500 newest
        // receipts, which would hide older same-amount receipts.
        $pageAmounts = $entries->getCollection()
            ->map(fn($e) => number_format(abs((float)$e->amount), 2, '.', ''))
            ->unique()->values()->all();
        $suggestedPool = $pageAmounts
            ? Receipt::whereIn('client_id', $businessIds)
                ->where('status', 'ready')
                ->whereHas('detail', fn($q) => $q->where('paid', 1)->whereIn('total_amount', $pageAmounts))
                ->with(['detail.accountHead', 'detail'])
                ->get()
                ->filter(fn($r) => $r->detail && $r->detail->total_amount !== null && $remainingOf($r) > 0.004)
            : collect();

        $fuzzyMatches = [];
        $findMatches = [];
        $suggestionMeta = [];
        foreach ($entries as $entry) {
            $absAmt = abs((float)$entry->amount);
            $entryTs = strtotime((string)($entry->transaction_date ?? ''));
            $dateDiff = function ($r) use ($entryTs) {
                $rDate = $r->detail->invoice_date ?? $r->receipt_date ?? null;
                $rTs = $rDate ? strtotime((string)$rDate) : false;
                if ($entryTs === false || $rTs === false) return PHP_INT_MAX;
                return (int) round(abs($entryTs - $rTs) / 86400);
            };
            $exactList = $suggestedPool
                // Only an untouched receipt can cover the bank line in full.
                ->filter(fn($r) => abs((float)$r->detail->total_amount - $absAmt) < 0.01
                    && $remainingOf($r) >= (float)$r->detail->total_amount - 0.004)
                ->sort(function ($a, $b) use ($dateDiff) {
                    return $dateDiff($a) <=> $dateDiff($b) ?: $b->id <=> $a->id;
                })
                ->values();
            $fuzzyMatches[(int)$entry->id] = $exactList;
            $suggestionMeta[(int)$entry->id] = $exactList
                ->mapWithKeys(function ($r) use ($dateDiff) {
                    $days = $dateDiff($r);
                    return [(int)$r->id => ['days' => $days === PHP_INT_MAX ? null : $days]];
                })->all();

            $exactIds = $exactList->pluck('id')->flip();
            $findMatches[(int)$entry->id] = $exactList
                ->concat($receipts->reject(fn($r) => isset($exactIds[$r->id])))
                ->values();
        }

        return [
            'entries' => $entries,
            'bankAccounts' => $bankAccounts,
            'heads' => $heads,
            'receipts' => $receipts,
            'splits' => $splits,
            'notes' => $notes,
            'fuzzyMatches' => $fuzzyMatches,
            'findMatches' => $findMatches,
            'suggestionMeta' => $suggestionMeta,
            'receiptRemaining' => $receiptRemaining,
            'importMap' => $importMap,
            'reconcileFilter' => $reconcileFilter,
            'totalCount' => $totalCount,
            'unreconciledCount' => $unreconciledCount,
            'reconciledCount' => $reconciledCount,
            'filteredCount' => $filteredCount,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'onlyEntry' => $onlyEntry,
        ];
    }

    /**
     * Receipt details for the reconciliation popup (view before selecting).
     * Scoped to the selected business so a receipt from another client can
     * never be opened through this endpoint.
     */
    public function receiptSummary(Request $request, int $receiptId) {
        $clientId = (int)$request->query('client_id', 0);
        abort_unless($clientId > 0, 422, 'Select a business first.');
        $receipt = Receipt::with(['detail.accountHead', 'files'])
            ->where('client_id', $clientId)
            ->findOrFail($receiptId);
        $detail = $receipt->detail;
        $money = fn($v) => $v === null ? null : number_format((float)$v, 2, '.', '');
        $used = $detail ? self::receiptUsed((int)$receipt->id) : 0.0;
        $total = $detail ? round((float)$detail->total_amount, 2) : 0.0;
        return response()->json([
            'used_amount' => $detail ? $money($used) : null,
            'remaining_amount' => $detail ? $money(max(0, $total - $used)) : null,
            'id' => (int)$receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'supplier' => $receipt->supplier,
            'status' => $receipt->status,
            'receipt_date' => $receipt->receipt_date ? Carbon::parse($receipt->receipt_date)->format('d-m-Y') : null,
            'created_at' => $receipt->created_at ? Carbon::parse($receipt->created_at)->format('d-m-Y H:i') : null,
            'total_amount' => $detail ? $money($detail->total_amount) : null,
            'net_amount' => $detail ? $money($detail->net_amount) : null,
            'tax_amount' => $detail ? $money($detail->tax_amount) : null,
            'vat_amount' => $detail ? $money($detail->vat_amount) : null,
            'invoice_number' => $detail?->invoice_number,
            'invoice_date' => $detail && $detail->invoice_date ? Carbon::parse($detail->invoice_date)->format('d-m-Y') : null,
            'payment_method' => $detail?->payment_method,
            'paid' => $detail ? (bool)$detail->paid : false,
            'description' => $detail?->description,
            'account_head' => $detail?->accountHead
                ? ['code' => $detail->accountHead->code, 'name' => $detail->accountHead->name] : null,
            'files' => $receipt->files->map(fn($f) => [
                'id' => (int)$f->id,
                'name' => $f->file_name ?: basename((string)$f->file_path),
                'url' => route('admin.receipt.file.download', [$receipt->id, $f->id]),
            ])->values(),
        ]);
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
            'splits.*.vat_percent' => 'nullable|numeric|min:0|max:100',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'gross' => 'nullable|numeric|min:0',
            // Find & Match: part of an existing receipt can be applied, and the
            // rest of the bank line can be covered by new receipts.
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
            'creates' => 'nullable|array',
            'creates.*.gross' => 'nullable|numeric|min:0',
            'creates.*.vat_percent' => 'nullable|numeric|min:0|max:100',
            'creates.*.account_head_id' => 'nullable|integer',
            'creates.*.supplier' => 'nullable|string|max:255',
            'creates.*.invoice_date' => 'nullable|date',
            'creates.*.memo' => 'nullable|string|max:500',
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

                // The statement must be verified before its rows can be
                // reconciled/posted. Notes and undoing a reconciliation stay
                // available so the accountant can tidy up while reviewing.
                if (in_array($action, ['match', 'create', 'split', 'find_match', 'transfer', 'categorize'], true)
                    && ($entry->bank_import_id ?? null)
                    && DB::table('bank_imports')->where('id', $entry->bank_import_id)->value('verification_status') !== BankStatementVerificationService::VERIFIED) {
                    throw ValidationException::withMessages(['verification' => 'Verify this statement before reconciling its transactions.']);
                }

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
                    
                    abort_unless($receipt->detail && (int)$receipt->detail->paid === 1, 422, 'Only paid receipts can be matched.');
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
                    $invoiceDate = ($data['invoice_date'] ?? null) ?: $entry->transaction_date;

                    // Same rule as Split and Find & Match: the accountant types the
                    // VAT-inclusive gross (and the VAT %), and the net is derived.
                    // A single receipt created here has to cover the bank line exactly;
                    // part payments belong to Split or Find & Match.
                    $gross = (isset($data['gross']) && $data['gross'] !== '') ? round((float)$data['gross'], 2) : $absAmount;
                    abort_if(abs($gross - $absAmount) > 0.004, 422,
                        'Gross amount must equal the bank amount (£' . number_format($absAmount, 2) . '). Use Split or Find & Match to reconcile it in parts.');

                    $receipt = Receipt::create([
                        'client_id' => $client->id,
                        'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                        'receipt_date' => $invoiceDate,
                        'supplier' => $supplier,
                        'status' => 'ready',
                        'from_reconciliation' => true,
                        'created_by' => auth()->id(),
                    ]);

                    $vat = $this->vatSplit($gross, (float)($data['vat_percent'] ?? 0));
                    ReceiptDetail::create([
                        'receipt_id' => $receipt->id,
                        'account_head_id' => $headId,
                        'invoice_date' => $invoiceDate,
                        'net_amount' => $vat['net'],
                        'tax_amount' => 0,
                        'vat_amount' => $vat['vat'],
                        'total_amount' => $vat['gross'],
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
                        $invDate = ($line['invoice_date'] ?? null) ?: $entry->transaction_date;

                        $r = Receipt::create([
                            'client_id' => $client->id,
                            'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                            'receipt_date' => $invDate,
                            'supplier' => $supplier,
                            'status' => 'ready',
                            'from_reconciliation' => true,
                            'created_by' => auth()->id(),
                        ]);

                        // Same gross + VAT% rule as the Create tab: the line
                        // amount is VAT-inclusive and the net is derived.
                        $vat = $this->vatSplit($lineAmt, (float)($line['vat_percent'] ?? 0));

                        ReceiptDetail::create([
                            'receipt_id' => $r->id,
                            'account_head_id' => $headId,
                            'invoice_date' => $invDate,
                            'net_amount' => $vat['net'],
                            'tax_amount' => 0,
                            'vat_amount' => $vat['vat'],
                            'total_amount' => $vat['gross'],
                            'paid' => 1,
                            'payment_method' => 'bank',
                            'description' => $line['memo'] ?? $entry->description,
                        ]);

                        $normalized[] = [
                            'bank_entry_id' => $entryId,
                            'account_head_id' => $headId,
                            'receipt_id' => $r->id,
                            'amount' => number_format($vat['gross'], 2, '.', ''),
                            'memo' => $line['memo'] ?? null,
                            'tax_percent' => $vat['percent'],
                            'tax_amount' => 0,
                            'vat_amount' => $vat['vat'],
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

                // 6. FIND & MATCH (EXISTING RECEIPTS, PARTIAL APPLICATIONS, NEW RECEIPTS)
                if ($action === 'find_match') {
                    // Any combination is allowed: existing receipts can be applied
                    // in full or only for part of what is left of them, and one or
                    // more new receipts can cover the remainder of the bank line.
                    $rawAllocations = $data['allocations'] ?? [];
                    // Only boxes holding an amount count; a ticked receipt with an
                    // empty box is rejected below rather than silently assumed.
                    $givenAllocations = collect($rawAllocations)
                        ->filter(fn($v) => $v !== null && $v !== '' && round((float)$v, 2) > 0)
                        ->map(fn($v) => round((float)$v, 2))->all();
                    $ticked = collect($data['receipt_ids'] ?? [])->map(fn($id) => (int)$id)->filter()->unique()->values()->all();
                    $creates = array_values(array_filter(
                        $data['creates'] ?? [],
                        fn($line) => round((float)($line['gross'] ?? 0), 2) > 0
                    ));

                    // Ticked receipts are the selected set; an apply amount overrides the
                    // full total, which is what older forms (no apply boxes) send.
                    $receiptIds = collect($ticked)->merge(array_keys($givenAllocations))
                        ->map(fn($id) => (int)$id)->filter()->unique()->values()->all();

                    abort_if(empty($receiptIds) && empty($creates), 422,
                        'Select at least one receipt, or create a receipt for the remainder.');

                    $selectedReceipts = $receiptIds
                        ? Receipt::with('detail')->where('client_id', $client->id)->whereIn('id', $receiptIds)->lockForUpdate()->get()->keyBy('id')
                        : collect();
                    abort_unless($selectedReceipts->count() === count($receiptIds), 422, 'One or more selected receipts could not be found.');

                    // Applied amounts: a receipt can only give what is left of it.
                    $applied = [];
                    foreach ($selectedReceipts as $srId => $sr) {
                        abort_unless($sr->detail && (int)$sr->detail->paid === 1, 422, 'Receipt #' . $sr->receipt_number . ' is not a paid receipt.');
                        $receiptTotal = round((float)$sr->detail->total_amount, 2);
                        $left = max(0.0, round($receiptTotal - self::receiptUsed((int)$sr->id, $entryId), 2));
                        abort_if($left <= 0.004, 422, 'Receipt #' . $sr->receipt_number . ' is already fully used by another transaction.');
                        if (array_key_exists($srId, $givenAllocations)) {
                            $wanted = $givenAllocations[$srId];
                        } elseif (in_array($srId, $ticked, true) && array_key_exists($srId, $rawAllocations)) {
                            abort(422, 'Enter how much of receipt #' . $sr->receipt_number . ' to apply, or untick it.');
                        } else {
                            $wanted = $receiptTotal;
                        }
                        abort_if($wanted > $left + 0.004, 422,
                            'Receipt #' . $sr->receipt_number . ' only has £' . number_format($left, 2) . ' left, so £' . number_format($wanted, 2) . ' cannot be applied from it.');
                        $applied[$srId] = $wanted;
                    }

                    $sumPence = 0;
                    foreach ($applied as $amount) $sumPence += (int)round($amount * 100);
                    foreach ($creates as $line) $sumPence += (int)round(round((float)$line['gross'], 2) * 100);

                    if ($sumPence !== $expectedPence) {
                        $diff = ($sumPence - $expectedPence) / 100;
                        if ($diff > 0) {
                            throw ValidationException::withMessages(['allocations' => 'Allocated £' . number_format($sumPence / 100, 2) . ' exceeds the bank amount (£' . number_format($absAmount, 2) . ') by £' . number_format($diff, 2) . '. Reduce an applied amount or a new receipt.']);
                        }
                        throw ValidationException::withMessages(['allocations' => 'Allocated £' . number_format($sumPence / 100, 2) . ' falls short of the bank amount (£' . number_format($absAmount, 2) . ') by £' . number_format(abs($diff), 2) . '. Create a receipt for the remainder or apply more.']);
                    }

                    DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->delete();
                    $lines = [];
                    foreach ($applied as $receiptId => $amount) {
                        $sr = $selectedReceipts->get($receiptId);
                        $lines[] = [
                            'bank_entry_id' => $entryId,
                            'account_head_id' => $sr->detail->account_head_id,
                            'receipt_id' => $sr->id,
                            'amount' => number_format($amount, 2, '.', ''),
                            'memo' => '#' . $sr->receipt_number . ' ' . $sr->supplier,
                            'tax_percent' => 0,
                            'tax_amount' => 0,
                            'vat_amount' => 0,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // New receipts for the uncovered remainder follow the same
                    // gross + VAT% rule as the Create and Split tabs.
                    foreach ($creates as $line) {
                        $headId = (int)($line['account_head_id'] ?? 0);
                        abort_unless($headId > 0, 422, 'Select an account head for each new receipt.');
                        $vat = $this->vatSplit((float)$line['gross'], (float)($line['vat_percent'] ?? 0));
                        $invDate = ($line['invoice_date'] ?? null) ?: $entry->transaction_date;
                        $supplier = trim((string)($line['supplier'] ?? '')) ?: ($entry->description ?: 'Vendor');

                        $newReceipt = Receipt::create([
                            'client_id' => $client->id,
                            'receipt_number' => 'RCT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                            'receipt_date' => $invDate,
                            'supplier' => $supplier,
                            'status' => 'ready',
                            'from_reconciliation' => true,
                            'created_by' => auth()->id(),
                        ]);

                        ReceiptDetail::create([
                            'receipt_id' => $newReceipt->id,
                            'account_head_id' => $headId,
                            'invoice_date' => $invDate,
                            'net_amount' => $vat['net'],
                            'tax_amount' => 0,
                            'vat_amount' => $vat['vat'],
                            'total_amount' => $vat['gross'],
                            'paid' => 1,
                            'payment_method' => 'bank',
                            'description' => $line['memo'] ?? $entry->description,
                        ]);

                        $lines[] = [
                            'bank_entry_id' => $entryId,
                            'account_head_id' => $headId,
                            'receipt_id' => $newReceipt->id,
                            'amount' => number_format($vat['gross'], 2, '.', ''),
                            'memo' => $line['memo'] ?? null,
                            'tax_percent' => $vat['percent'],
                            'tax_amount' => 0,
                            'vat_amount' => $vat['vat'],
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (count($lines) === 1) {
                        // One receipt covers the whole bank line: plain match.
                        $only = $lines[0];
                        $change = [
                            'status' => 'matched',
                            'receipt_id' => $only['receipt_id'],
                            'account_head_id' => $only['account_head_id'],
                            'matched_by' => auth()->id(),
                            'matched_at' => now(),
                            'reconciliation_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'updated_at' => now(),
                        ];
                    } else {
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
            $payload = ['success' => true, 'message' => $message, 'entry_id' => $entryId, 'action' => $data['action'], 'row' => null];
            try {
                // The browser swaps this one row (and the counters) in place.
                $rendered = $this->actionPayload($request, $entryId);
                $payload['row'] = $rendered['row'];
                $payload['stats'] = $rendered['stats'];
                $payload['pagination'] = $rendered['pagination'];
                $payload['empty_html'] = $rendered['empty_html'];
            } catch (\Throwable $e) {
                // The action itself succeeded; the JS falls back to reloading the page.
                report($e);
            }
            return response()->json($payload);
        }
        return redirect()->back()->with('success', $message);
    }
    /**
     * Split a VAT-inclusive gross amount into net + VAT, penny-exact: the net is
     * derived from the gross and the VAT is whatever is left, so the two always
     * add back to the gross (and therefore to the bank amount).
     *
     * @return array{gross:float,percent:float,net:float,vat:float}
     */
    private function vatSplit(float $gross, float $vatPercent): array {
        $grossPence = (int)round($gross * 100);
        $percent = max(0.0, min(100.0, $vatPercent));
        $netPence = (int)round($grossPence / (1 + $percent / 100));
        if ($netPence < 0) $netPence = 0;
        if ($netPence > $grossPence) $netPence = $grossPence;
        return [
            'gross' => $grossPence / 100,
            'percent' => round($percent, 2),
            'net' => $netPence / 100,
            'vat' => ($grossPence - $netPence) / 100,
        ];
    }

    /**
     * How much of a receipt is already consumed by reconciled bank lines — both
     * whole matches (bank_entries.receipt_id) and partial allocations
     * (bank_allocation_lines). Pass an entry id to leave that one bank line out.
     */
    private static function receiptUsed(int $receiptId, ?int $excludeEntryId = null): float {
        $direct = (float)DB::table('bank_entries')
            ->where('receipt_id', $receiptId)
            ->where('reconciliation_status', 'approved')
            ->when($excludeEntryId, fn($q) => $q->where('id', '!=', $excludeEntryId))
            ->selectRaw('COALESCE(SUM(ABS(amount)), 0) AS used')
            ->value('used');
        $allocated = (float)DB::table('bank_allocation_lines as l')
            ->join('bank_entries as b', 'b.id', '=', 'l.bank_entry_id')
            ->where('l.receipt_id', $receiptId)
            ->where('b.reconciliation_status', 'approved')
            ->when($excludeEntryId, fn($q) => $q->where('l.bank_entry_id', '!=', $excludeEntryId))
            ->selectRaw('COALESCE(SUM(l.amount), 0) AS used')
            ->value('used');
        return round($direct + $allocated, 2);
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
        // Display oldest → newest with manually added rows interleaved by date.
        $entries=collect(app(BankStatementVerificationService::class)->sortChronologically($entries, $import->opening_balance !== null ? (float) $import->opening_balance : null));
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
        $item = BankImport::findOrFail($importId);
        if ($item->storage_path && Storage::disk('s3')->exists($item->storage_path)) {
            return response(Storage::disk('s3')->get($item->storage_path), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . addslashes(basename($item->filename)) . '"',
            ]);
        }
        if ($item->storage_path && Storage::disk('s3')->exists($item->storage_path)) {
            return response()->file(Storage::disk('s3')->path($item->storage_path), ['Content-Type' => 'application/pdf']);
        }
        abort(404, 'Statement file not found in cloud or local storage.');
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
                'bank_entry_id'=>$entry->id,'author_id'=>auth()->id(),'action'=>'edit',
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
                'bank_entry_id'=>$entryId,'author_id'=>auth()->id(),'action'=>'add',
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
                'bank_entry_id'=>null,'author_id'=>auth()->id(),'action'=>'remove',
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
            'bank_entry_id'=>$entryId,'author_id'=>auth()->id(),
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
        $item = BankImport::findOrFail($importId);
        $downloadName = $item->filename ? basename($item->filename) : 'statement.pdf';
        if ($item->storage_path && Storage::disk('s3')->exists($item->storage_path)) {
            return Storage::disk('s3')->download($item->storage_path, $downloadName);
        }
        if ($item->storage_path && Storage::disk('s3')->exists($item->storage_path)) {
            return Storage::disk('s3')->download($item->storage_path, $downloadName);
        }
        abort(404, 'Statement file not found in cloud or local storage.');
    }
}
