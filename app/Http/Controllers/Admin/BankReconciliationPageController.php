<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\BankImport;
use App\Models\Client;
use App\Models\Receipt;
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
                'credentialId'=>$credentialId,'businessId'=>$businessId,'bankAccounts'=>$bankAccounts,'bankAccountId'=>$bankAccountId,'bankOverview'=>collect(),
                'entries'=>DB::table('bank_entries')->whereRaw('1 = 0')->paginate(30),
                'heads'=>collect(),'taxRates'=>collect(),'stats'=>collect(),'approvalStats'=>collect(),
                'imports'=>collect(),'receipts'=>collect(),'splits'=>collect(),'unmatchedReceipts'=>collect(),
                'claimedReceiptIds'=>collect(),'settledTotals'=>collect(),'notes'=>collect(),'receiptRequests'=>collect(),
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
        $scopeEntries=fn()=>DB::table('bank_entries')->whereIn('client_id',$businessIds)->when($bankAccountId,fn($q)=>$q->where('bank_account_id',$bankAccountId));
        $entries=$scopeEntries()
            ->when($request->filled('status'),fn($q)=>$q->where('status',$request->input('status')))
            ->when($request->filled('approval'),fn($q)=>$q->where('reconciliation_status',$request->input('approval')))
            ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(30)->withQueryString();
        // Account heads must be global + this credential's, matching the receipt page.
        // (Client-only returns nothing for credentials that rely on the global chart.)
        $heads=AccountHead::where('is_active',1)->whereNotNull('account_type_id')
            ->where(function($q)use($credentialId){$q->whereNull('client_credential_id');if($credentialId)$q->orWhere('client_credential_id',$credentialId);})
            ->orderBy('code')->get(['id','code','name']);
        $taxRates=\App\Models\TaxRate::where('is_active',true)->select('id','name','rate')->get();
        $stats=$scopeEntries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $approvalStats=$scopeEntries()->selectRaw('reconciliation_status, count(*) as total')->groupBy('reconciliation_status')->pluck('total','reconciliation_status');
        $imports=DB::table('bank_imports')->whereIn('client_id',$businessIds)->when($bankAccountId,fn($q)=>$q->where('bank_account_id',$bankAccountId))->latest()->limit(10)->get();
        // Only reviewed receipts may be matched: a pending/to_review receipt has no
        // confirmed head, so approval would refuse it. Mirrors assertReceiptReady().
        $receipts=Receipt::whereIn('client_id',$businessIds)->whereIn('status',['ready','archived'])
            ->with('detail')->latest('id')->limit(400)->get()
            ->filter(fn($r)=>$r->detail && $r->detail->total_amount!==null);
        $splits=DB::table('bank_allocation_lines')->whereIn('bank_entry_id',$entries->getCollection()->pluck('id'))->get()->groupBy('bank_entry_id');
        $settlements=DB::table('bank_settlements')->whereIn('bank_entry_id',$entries->getCollection()->pluck('id'))->orderByDesc('id')->get()->groupBy('bank_entry_id');
        $unmatchedReceipts=$receipts->filter(function($r)use($businessIds){
            return !DB::table('bank_entries')->whereIn('client_id',$businessIds)->where('receipt_id',$r->id)->where('status','matched')->exists()
                && !DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')
                   ->whereIn('b.client_id',$businessIds)->where('l.receipt_id',$r->id)->exists();
        });
        // Receipts the backend refuses to match or split again (already matched, or
        // already allocated inside a split). The tabs must not offer them as if free.
        $claimedReceiptIds=DB::table('bank_entries')->whereIn('client_id',$businessIds)
            ->whereNotNull('receipt_id')->where('status','matched')->pluck('receipt_id')
            ->merge(DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')
                ->whereIn('b.client_id',$businessIds)->whereNotNull('l.receipt_id')->pluck('l.receipt_id'))
            ->map(fn($id)=>(int)$id)->unique()->flip();
        // Per-receipt settlement totals so the Settle tab can show what is already
        // covered (one receipt may legitimately settle across several bank rows).
        $settledTotals=DB::table('bank_settlements')->whereIn('client_id',$businessIds)
            ->whereNotNull('receipt_id')->selectRaw('receipt_id, SUM(settled_amount) settled, MAX(remaining_amount) remaining')
            ->groupBy('receipt_id')->get()->keyBy('receipt_id');
        // Saved accountant notes and client receipt requests, shown on their row.
        $entryIds=$entries->getCollection()->pluck('id');
        $notes=DB::table('bank_review_notes')->whereIn('bank_entry_id',$entryIds)
            ->orderByDesc('id')->get()->groupBy('bank_entry_id');
        $receiptRequests=DB::table('bank_receipt_requests')->whereIn('bank_entry_id',$entryIds)
            ->orderByDesc('id')->get()->groupBy('bank_entry_id');
        $fuzzyMatches=[];
        foreach($entries as $entry){
            $fuzzyMatches[(int)$entry->id]=$this->scoreReceipts($entry,$receipts);
        }
        // Open entries available to link as the other side of an internal transfer.
        // Exclude anything already linked as a transfer so a counterparty can't be reused.
        $transferCandidates=DB::table('bank_entries')->whereIn('client_id',$businessIds)
            ->whereIn('status',['unmatched','needs_review'])
            ->whereNull('linked_bank_entry_id')->whereNull('transfer_type')
            ->orderByDesc('transaction_date')->limit(300)
            ->get(['id','transaction_date','description','amount','bank_account_id']);
        return view('admin.accounting.bank_reconciliation',compact('credentialId','businessId','bankAccountId','bankAccounts','bankOverview','entries','heads','taxRates','stats','approvalStats','imports','receipts','splits','settlements','unmatchedReceipts','fuzzyMatches','transferCandidates','claimedReceiptIds','settledTotals','notes','receiptRequests'));
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
        $data['created_at']=now();$data['updated_at']=now();
        DB::table('reconciliation_bank_accounts')->insert($data);
        return redirect()->route('admin.bank-reconciliation.index',[
            'client_credential_id'=>$client->client_credential_id,'client_id'=>$client->id,
        ])->with('success','Bank account added. Select it when uploading statements.');
    }
    public function action(Request $request,int $entryId) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'action'=>'required|in:match,unmatch,verify,categorize,request_receipt,split,add_note,approve,reject,reopen,transfer,settle',
            'receipt_id'=>'nullable|integer',
            'account_head_id'=>'nullable|integer',
            'review_note'=>'nullable|string|max:2000',
            'amount'=>'nullable|numeric|between:-999999999,999999999',
            'transaction_date'=>'nullable|date_format:Y-m-d',
            'description'=>'nullable|string|max:500',
            'linked_entry_id'=>'nullable|integer',
            'settlement_type'=>'nullable|in:exact,partial,over_payment,under_payment',
            'allocations'=>'required_if:action,split|array|min:2|max:30',
            'allocations.*.amount'=>'required_if:action,split|numeric|gt:0',
            'allocations.*.account_head_id'=>'nullable|integer',
            'allocations.*.receipt_id'=>'nullable|integer',
            'allocations.*.memo'=>'nullable|string|max:500',
            'allocations.*.tax_percent'=>'nullable|numeric|between:0,100',
            'allocations.*.tax_amount'=>'nullable|numeric|min:0',
            'allocations.*.vat_amount'=>'nullable|numeric|min:0',
        ]);
        $client=Client::findOrFail($data['client_id']);
        $posted=0;$reversed=0;
        $isAjax=$request->ajax()||$request->wantsJson();
        try {
        DB::transaction(function()use($request,$entryId,$client,$data,&$posted,&$reversed){
            $entry=DB::table('bank_entries')->where('client_id',$client->id)->lockForUpdate()->find($entryId);
            abort_unless($entry,404);
            $change=['updated_at'=>now()];
            $action=$data['action'];
            if(!in_array($action, ['add_note', 'verify'], true)
                && $entry->bank_import_id
                && DB::table('bank_imports')->where('id',$entry->bank_import_id)->value('verification_status')!==BankStatementVerificationService::VERIFIED) {
                throw ValidationException::withMessages(['action'=>'Verify this statement in the verification workspace before reconciling its rows.']);
            }
            $isApproved=($entry->reconciliation_status??'draft')==='approved';
            if(in_array($action,['approve','reject','reopen'])){
                if($action==='approve'){
                    abort_if($isApproved,409,'Reconciliation is already approved.');
                    abort_unless(in_array($entry->status,['matched','categorized','split']),409,'Match, categorise or split the transaction before approving.');
                    if($entry->status==='split')abort_unless($this->splitBalances($entry),409,'Split does not balance; review the allocations before approving.');
                    $this->posting->assertReadyForApproval($entry);
                    $change+=['reconciliation_status'=>'approved','approved_by'=>auth()->id(),'approved_at'=>now()];
                }elseif($action==='reject'){
                    abort_if($isApproved,409,'Reopen the approved reconciliation before rejecting it.');
                    $change+=['reconciliation_status'=>'rejected','approved_by'=>null,'approved_at'=>null];
                }else{
                    abort_unless($isApproved,409,'This reconciliation is not approved.');
                    // Reopening reverses any posted journal so the row can be edited
                    // and approved again without leaving stale ledger entries behind.
                    $change+=['reconciliation_status'=>'draft','approved_by'=>null,'approved_at'=>null];
                }
            }elseif($isApproved){
                // An approved reconciliation is locked; only notes may still be added.
                abort_if($action!=='add_note',409,'Reopen the approved reconciliation before making changes.');
            }
            if($action==='add_note') {
                $note=trim($data['review_note']??'');
                if($note==='')throw ValidationException::withMessages(['review_note'=>'Enter a review note.']);
                DB::table('bank_review_notes')->insert(['bank_entry_id'=>$entryId,'author_id'=>auth()->id(),
                    'type'=>'internal','body'=>$note,'created_at'=>now(),'updated_at'=>now()]);
                DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),
                    'action'=>'add_note','old_values'=>null,'new_values'=>json_encode(['note'=>$note]),
                    'created_at'=>now(),'updated_at'=>now()]);
                return;
            }
            if($action==='split') {
                abort_if(in_array($entry->status,['matched','needs_review']),409,'Verify or unmatch first.');
                $lines=$data['allocations']??[];
                if(count($lines)<2)throw ValidationException::withMessages(['allocations'=>'Add at least two allocation lines.']);
                // Work in integer pence to guarantee an exact balance.
                $expected=(int)round(abs((float)$entry->amount)*100);
                $allocated=0;$normalized=[];$used=[];
                foreach($lines as $line){
                    $pence=(int)round((float)$line['amount']*100);
                    if($pence<=0 || abs($pence/100-(float)$line['amount'])>0.00001)
                        throw ValidationException::withMessages(['allocations'=>'Amounts must be positive with at most two decimals.']);
                    $head=$line['account_head_id']??null;$receiptId=$line['receipt_id']??null;
                    if(!$head && !$receiptId)throw ValidationException::withMessages(['allocations'=>'Each line needs an account head or existing receipt.']);
                    if($head && !AccountHead::where('is_active',1)->whereNotNull('account_type_id')->whereKey($head)
                        ->where(function($q)use($client){$q->whereNull('client_credential_id')->orWhere('client_credential_id',$client->client_credential_id);})->exists())abort(422,'Invalid account head.');
                    if($receiptId){
                        $receipt=Receipt::with('detail')->where('client_id',$client->id)->findOrFail($receiptId);
                        abort_if(in_array($receiptId,$used),422,'Select each receipt only once per split.');
                        $used[]=$receiptId;
                        abort_if(DB::table('bank_entries')->where('client_id',$client->id)->where('status','matched')->where('receipt_id',$receiptId)->exists(),409,'Receipt is already matched.');
                        abort_if(DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')
                            ->where('b.client_id',$client->id)->where('b.id','!=',$entryId)->where('l.receipt_id',$receiptId)->exists(),409,'Receipt already allocated to a split.');
                        // Partial receipts and liability settlements require accountant review; no automatic ledger posting.
                    }
                    $allocated+=$pence;
                    $normalized[]=['bank_entry_id'=>$entryId,'account_head_id'=>$head,'receipt_id'=>$receiptId,
                        'amount'=>number_format($pence/100,2,'.',''),'memo'=>$line['memo']??null,
                        'tax_percent'=>(float)($line['tax_percent']??0),
                        'tax_amount'=>(float)($line['tax_amount']??0),
                        'vat_amount'=>(float)($line['vat_amount']??0),
                        'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()];
                }
                if($allocated!==$expected)throw ValidationException::withMessages(['allocations'=>'Split total must exactly equal bank amount.']);
                DB::table('bank_allocation_lines')->where('bank_entry_id',$entryId)->delete();
                DB::table('bank_allocation_lines')->insert($normalized);
                $change=['status'=>'split','receipt_id'=>null,'account_head_id'=>null,'matched_by'=>auth()->id(),'matched_at'=>now(),'reconciliation_status'=>'draft','approved_by'=>null,'approved_at'=>null,'updated_at'=>now()];
                DB::table('bank_entries')->where('id',$entryId)->update($change);
                DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),
                    'action'=>'split','old_values'=>json_encode($entry),'new_values'=>json_encode(['entry'=>$change,'allocations'=>$normalized]),
                    'created_at'=>now(),'updated_at'=>now()]);
                return;
            }
            if($action==='request_receipt') {
                abort_if($entry->status==='matched',409,'Receipt is already matched.');
                if(!DB::table('bank_receipt_requests')->where('bank_entry_id',$entryId)->where('status','open')->exists()){
                    DB::table('bank_receipt_requests')->insert([
                        'client_id'=>$client->id,'bank_entry_id'=>$entryId,'requested_by'=>auth()->id(),
                        'message'=>$data['review_note']??'Please upload the receipt for this bank transaction.',
                        'status'=>'open','created_at'=>now(),'updated_at'=>now()
                    ]);
                }
                return;
            }
            if($action==='verify'){
                abort_unless($entry->status==='needs_review',409,'Not awaiting PDF verification.');
                foreach(['amount','transaction_date','description','review_note'] as $field)
                    if(array_key_exists($field,$data)&&$data[$field]!==null)$change[$field]=$data[$field];
                $change['status']='unmatched';
                $change['verified_by']=auth()->id();
                $change['verified_at']=now();
                DB::table('bank_entry_revisions')->insert([
                    'bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),'action'=>'verify',
                    'original_values'=>null,'corrected_values'=>null,'reason'=>'Row checked against the PDF',
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
                $import = \App\Models\BankImport::find($entry->bank_import_id ?? 0);
                if ($import) {
                    try {
                        app(BankStatementVerificationService::class)->verify($import, auth()->id() ?? 1);
                    } catch (\Throwable $e) {}
                }
            } elseif($action==='unmatch'){
                abort_unless(in_array($entry->status,['matched','split','categorized']),409,'Nothing to undo.');
                DB::table('bank_allocation_lines')->where('bank_entry_id',$entryId)->delete();
                $change+=['status'=>'unmatched','receipt_id'=>null,'account_head_id'=>null,'matched_by'=>null,'matched_at'=>null];
            } elseif($action==='transfer'){
                $linkedId=(int)($data['linked_entry_id']??0);
                abort_unless($linkedId && $linkedId!==$entryId,422,'Choose a different bank entry to link as the transfer counterparty.');
                $counter=DB::table('bank_entries')->where('id',$linkedId)->where('client_id',$client->id)->first();
                abort_unless($counter,404,'Counterparty bank entry not found.');
                abort_unless(abs(round((float)$entry->amount*100)+round((float)$counter->amount*100))<1,409,'Transfer entries must have equal and opposite amounts.');
                abort_unless(($entry->bank_account_id??0)!==($counter->bank_account_id??0),409,'Transfer must be between two different bank accounts.');
                $change+=['transfer_type'=>'internal_transfer','linked_bank_entry_id'=>$linkedId,'status'=>'categorized','account_head_id'=>$data['account_head_id']??null,'review_note'=>'Internal transfer · linked to bank entry #'.$linkedId,'reconciliation_status'=>'draft','approved_by'=>null,'approved_at'=>null];
                DB::table('bank_entries')->where('id',$linkedId)->update(['transfer_type'=>'internal_transfer','linked_bank_entry_id'=>$entryId,'status'=>'categorized','review_note'=>'Internal transfer · linked to bank entry #'.$entryId,'reconciliation_status'=>'draft','approved_by'=>null,'approved_at'=>null,'updated_at'=>now()]);
            } elseif($action==='settle'){
                $settleType=$data['settlement_type']??'partial';
                $receiptId=$data['receipt_id']??null;
                if($receiptId){
                    $receipt=Receipt::with('detail')->where('client_id',$client->id)->findOrFail($receiptId);
                    $bankAmt=abs((float)$entry->amount);
                    $receiptAmt=(float)($receipt->detail?->total_amount ?? 0);
                    $settled=min($bankAmt,$receiptAmt);
                    $remaining=abs($bankAmt-$receiptAmt);
                    DB::table('bank_settlements')->insert([
                        'client_id'=>$client->id,'bank_entry_id'=>$entryId,'receipt_id'=>$receiptId,
                        'bank_amount'=>$bankAmt,'receipt_amount'=>$receiptAmt,'settled_amount'=>$settled,
                        'remaining_amount'=>$remaining,'settlement_type'=>$settleType,'status'=>'open',
                        'created_at'=>now(),'updated_at'=>now(),
                    ]);
                    $change+=['receipt_id'=>$receiptId,'status'=>'categorized','account_head_id'=>$data['account_head_id']??$receipt->detail?->account_head_id,'review_note'=>'Partial settlement · settled £'.number_format($settled,2).' of £'.number_format($bankAmt,2)];
                } else {
                    abort_unless($data['account_head_id'] ?? null,422,'Choose an account head for the settlement.');
                    $change+=['status'=>'categorized','account_head_id'=>$data['account_head_id'],'review_note'=>'Unmatched settlement · no receipt linked'];
                }
            } elseif(in_array($action,['approve','reject','reopen'])){
                // Approval state already prepared above; nothing further to reconcile.
            } else {
                abort_if($entry->status==='needs_review',409,'Verify PDF transaction first.');
                $head=$data['account_head_id']??null;
                if($head!==null)abort_unless(AccountHead::where('is_active',1)->whereNotNull('account_type_id')->whereKey($head)
                    ->where(function($q)use($client){$q->whereNull('client_credential_id')->orWhere('client_credential_id',$client->client_credential_id);})->exists(),422,'Invalid account head');
                if($action==='categorize'){
                    abort_if($entry->status==='split',409,'Undo the split before categorising.');
                    if(!$head)throw ValidationException::withMessages(['account_head_id'=>'Choose an account head.']);
                    $change+=['status'=>'categorized','account_head_id'=>$head,'review_note'=>$data['review_note']??null];
                } else {
                    $receipt=Receipt::with('detail')->where('client_id',$client->id)->findOrFail($data['receipt_id']??0);
                    abort_if(in_array($entry->status,['matched','split']),409,'Already reconciled.');
                    abort_if(DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')->where('b.client_id',$client->id)->where('l.receipt_id',$receipt->id)->exists(),409,'Receipt already used in a split.');
                    abort_if(DB::table('bank_entries')->where('receipt_id',$receipt->id)->where('status','matched')
                      ->where('id','!=',$entryId)->exists(),409,'Receipt is already matched to another bank entry.');
                    $total=(float)($receipt->detail?->total_amount ?? 0);
                    abort_unless($total>0 && abs(abs((float)$entry->amount)-$total)<0.01,409,'Amounts differ. Review partial payments manually.');
                    $chosenHead = $head ?? $receipt->detail?->account_head_id;
                    if(!$chosenHead) {
                        throw ValidationException::withMessages(['account_head_id' => 'Select an account head for this receipt match.']);
                    }
                    if ($receipt->detail && !$receipt->detail->account_head_id && $chosenHead) {
                        $receipt->detail->update(['account_head_id' => $chosenHead]);
                    }
                    if (in_array($receipt->status, ['pending', 'to_review'], true)) {
                        $receipt->update(['status' => 'ready']);
                    }
                    $change+=['status'=>'matched','receipt_id'=>$receipt->id,'account_head_id'=>$chosenHead,
                        'matched_by'=>auth()->id(),'matched_at'=>now()];
                }
            }
            // Any change to a reconciled row returns it to draft for fresh sign-off.
            if(!in_array($action,['approve','reject','reopen','add_note','transfer','settle']))
                $change=['reconciliation_status'=>'draft','approved_by'=>null,'approved_at'=>null]+$change;
            DB::table('bank_entries')->where('id',$entryId)->update($change);
            // Approval is the single source of truth for bank postings; reopening
            // reverses them so the ledger never double-counts.
            if($action==='approve')$posted=$this->posting->postEntry($entry,auth()->id());
            elseif($action==='reopen')$reversed=$this->posting->reverseEntry($entry);
            if($action==='match')DB::table('bank_receipt_requests')->where('bank_entry_id',$entryId)
                ->where('status','open')->update(['status'=>'resolved','updated_at'=>now()]);
            DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),
                'action'=>$action,'old_values'=>json_encode($entry),'new_values'=>json_encode($change),
                'created_at'=>now(),'updated_at'=>now()]);
        });
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isAjax) return response()->json(['success'=>false,'message'=>$e->getMessage(),'errors'=>$e->errors()],422);
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($isAjax) return response()->json(['success'=>false,'message'=>$e->getMessage()], $e->getStatusCode());
            throw $e;
        }
        $message=match($data['action']){
            'approve'=>$posted
                ? 'Reconciliation approved and posted to the ledger ('.$posted.' entr'.($posted===1?'y':'ies').').'
                : 'Reconciliation approved. The linked receipt already carries its own ledger journal.',
            'reject'=>'Reconciliation rejected and returned for review.',
            'reopen'=>$reversed
                ? 'Reconciliation reopened and its ledger posting reversed.'
                : 'Reconciliation reopened for editing.',
            default => 'Bank reconciliation action saved. No accounting journal posted.',
        };
        if ($isAjax) {
            return response()->json(['success'=>true,'message'=>$message,'entry_id'=>$entryId,'action'=>$data['action']]);
        }
        return redirect()->back()->with('success',$message);
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

    public function verifyAllRowsForClient(Request $request) {
        $clientIds = $this->getScopeClientIds($request);
        $entryIds = DB::table('bank_entries')
            ->whereIn('client_id', $clientIds)
            ->where('status', 'needs_review')
            ->pluck('id');
        $count = DB::table('bank_entries')
            ->whereIn('client_id', $clientIds)
            ->where('status', 'needs_review')
            ->update([
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
                'status' => 'unmatched',
            ]);
        // Verify each affected import so reconcile actions are unblocked.
        $verifier = app(BankStatementVerificationService::class);
        $importIds = DB::table('bank_entries')
            ->whereIn('id', $entryIds)
            ->whereNotNull('bank_import_id')
            ->pluck('bank_import_id')
            ->unique();
        $verifiedImports = 0;
        $blockedImports = 0;
        foreach ($importIds as $importId) {
            $import = \App\Models\BankImport::find($importId);
            if (!$import) continue;
            try {
                $verifier->verify($import, auth()->id());
                $verifiedImports++;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $blockedImports++;
            }
        }
        $message = $count.' row(s) verified and marked ready for reconciliation.';
        if ($verifiedImports) {
            $message .= ' '.$verifiedImports.' statement(s) verified.';
        }
        if ($blockedImports) {
            $message .= ' '.$blockedImports.' statement(s) have unresolved exceptions — resolve them in the verification workspace before reconciling.';
        }
        return redirect()->back()->with('success', $message);
    }

    public function approveAllRows(Request $request) {
        $clientIds = $this->getScopeClientIds($request);
        $rows=DB::table('bank_entries')
            ->whereIn('client_id', $clientIds)
            ->whereIn('status', ['matched', 'categorized', 'split'])
            ->where(function($q) {
                $q->whereNull('reconciliation_status')
                  ->orWhere('reconciliation_status', '!=', 'approved');
            });
        $count=0;$skipped=0;
        foreach ($rows->get() as $entry) {
            // The statement-level verification gate applies to bulk approval too.
            $importVerified = !$entry->bank_import_id || (DB::table('bank_imports')->where('id',$entry->bank_import_id)
                ->value('verification_status')===BankStatementVerificationService::VERIFIED);
            if(!$importVerified){$skipped++;continue;}
            try {
                if ($entry->status === 'split') {
                    $lines = DB::table('bank_allocation_lines')->where('bank_entry_id', $entry->id)->get();
                    $sum = 0;
                    foreach ($lines as $line) $sum += (int) round((float) $line->amount * 100);
                    if ($sum !== (int) round(abs((float) $entry->amount) * 100)) { $skipped++; continue; }
                }
                $this->posting->assertReadyForApproval((object)$entry);
                DB::table('bank_entries')->where('id', $entry->id)->update([
                    'reconciliation_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $this->posting->postEntry((object)$entry, auth()->id());
                $count++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }
        $message=$count.' reconciled row(s) approved and posted to the ledger.';
        if($skipped) $message.=' '.$skipped.' row(s) skipped (not reconciled, or statement not verified).';
        return redirect()->back()->with('success', $message);
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
