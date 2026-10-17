<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\Client;
use App\Models\Receipt;
use App\Services\BankLedgerPostingService;
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
        $bankAccounts=$businessId ? DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)->orderBy('bank_name')->get() : collect();
        if($bankAccountId && (!$businessId || !$bankAccounts->contains('id',$bankAccountId))) {
            abort(422,'Choose a bank account belonging to the selected business.');
        }
        $businessIds=$credentialId
            ? Client::where('client_credential_id',$credentialId)->where('status',1)->pluck('id')->all()
            : [];
        if($businessId)
            $businessIds=in_array($businessId,$businessIds,true)?[$businessId]:[];
        if(!$credentialId||!$businessIds){
            // Nothing selected yet — render the picker with an empty workspace.
            return view('admin.accounting.bank_reconciliation',[
                'credentialId'=>$credentialId,'businessId'=>$businessId,'bankAccounts'=>$bankAccounts,'bankAccountId'=>$bankAccountId,'bankOverview'=>collect(),
                'entries'=>DB::table('bank_entries')->whereRaw('1 = 0')->paginate(30),
                'heads'=>collect(),'stats'=>collect(),'approvalStats'=>collect(),
                'imports'=>collect(),'receipts'=>collect(),'splits'=>collect(),'unmatchedReceipts'=>collect(),
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
        $heads=AccountHead::where('client_credential_id',$credentialId)->orderBy('code')->get(['id','code','name']);
        $stats=$scopeEntries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $approvalStats=$scopeEntries()->selectRaw('reconciliation_status, count(*) as total')->groupBy('reconciliation_status')->pluck('total','reconciliation_status');
        $imports=DB::table('bank_imports')->whereIn('client_id',$businessIds)->when($bankAccountId,fn($q)=>$q->where('bank_account_id',$bankAccountId))->latest()->limit(10)->get();
        $receipts=Receipt::whereIn('client_id',$businessIds)->with('detail')->latest('id')->limit(400)->get()
            ->filter(fn($r)=>$r->detail && $r->detail->total_amount!==null);
        $splits=DB::table('bank_allocation_lines')->whereIn('bank_entry_id',$entries->getCollection()->pluck('id'))->get()->groupBy('bank_entry_id');
        $unmatchedReceipts=$receipts->filter(function($r)use($businessIds){
            return !DB::table('bank_entries')->whereIn('client_id',$businessIds)->where('receipt_id',$r->id)->where('status','matched')->exists()
                && !DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')
                   ->whereIn('b.client_id',$businessIds)->where('l.receipt_id',$r->id)->exists();
        });
        return view('admin.accounting.bank_reconciliation',compact('credentialId','businessId','bankAccountId','bankAccounts','bankOverview','entries','heads','stats','approvalStats','imports','receipts','splits','unmatchedReceipts'));
    }
    public function createBankAccount(Request $request) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'bank_name'=>'required|string|max:100',
            'account_label'=>'required|string|max:100',
            'account_last_four'=>'nullable|digits:4',
            'currency'=>'required|in:GBP,EUR,USD',
        ]);
        $client=Client::findOrFail($data['client_id']);
        // Only allow account management within the selected credential/business.
        abort_unless((int)$client->client_credential_id===(int)$request->input('client_credential_id'),403);
        $data['created_at']=now();$data['updated_at']=now();
        DB::table('reconciliation_bank_accounts')->insert($data);
        return redirect()->route('admin.bank-reconciliation.index',[
            'client_credential_id'=>$client->client_credential_id,'client_id'=>$client->id,
        ])->with('success','Bank account added. Select it when uploading statements.');
    }
    public function action(Request $request,int $entryId) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'action'=>'required|in:match,unmatch,verify,categorize,request_receipt,split,add_note,approve,reject,reopen',
            'receipt_id'=>'nullable|integer',
            'account_head_id'=>'nullable|integer',
            'review_note'=>'nullable|string|max:2000',
            'amount'=>'nullable|numeric|between:-999999999,999999999',
            'transaction_date'=>'nullable|date_format:Y-m-d',
            'description'=>'nullable|string|max:500',
            'allocations'=>'required_if:action,split|array|min:2|max:30',
            'allocations.*.amount'=>'required_if:action,split|numeric|gt:0',
            'allocations.*.account_head_id'=>'nullable|integer',
            'allocations.*.receipt_id'=>'nullable|integer',
            'allocations.*.memo'=>'nullable|string|max:500',
        ]);
        $client=Client::findOrFail($data['client_id']);
        $posted=0;$reversed=0;
        DB::transaction(function()use($request,$entryId,$client,$data,&$posted,&$reversed){
            $entry=DB::table('bank_entries')->where('client_id',$client->id)->lockForUpdate()->find($entryId);
            abort_unless($entry,404);
            $change=['updated_at'=>now()];
            $action=$data['action'];
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
                    if($head && !AccountHead::where('client_credential_id',$client->client_credential_id)->whereKey($head)->exists())abort(422,'Invalid account head.');
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
            } elseif($action==='unmatch'){
                abort_unless(in_array($entry->status,['matched','split','categorized']),409,'Nothing to undo.');
                DB::table('bank_allocation_lines')->where('bank_entry_id',$entryId)->delete();
                $change+=['status'=>'unmatched','receipt_id'=>null,'account_head_id'=>null,'matched_by'=>null,'matched_at'=>null];
            } elseif(in_array($action,['approve','reject','reopen'])){
                // Approval state already prepared above; nothing further to reconcile.
            } else {
                abort_if($entry->status==='needs_review',409,'Verify PDF transaction first.');
                $head=$data['account_head_id']??null;
                if($head!==null)abort_unless(AccountHead::where('client_credential_id',$client->client_credential_id)->whereKey($head)->exists(),422,'Invalid account head');
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
                    $change+=['status'=>'matched','receipt_id'=>$receipt->id,'account_head_id'=>$head??$receipt->detail?->account_head_id,
                        'matched_by'=>auth()->id(),'matched_at'=>now()];
                }
            }
            // Any change to a reconciled row returns it to draft for fresh sign-off.
            if(!in_array($action,['approve','reject','reopen','add_note']))
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
        return redirect()->route('admin.bank-reconciliation.index',['client_credential_id'=>$client->client_credential_id,'client_id'=>$client->id])
            ->with('success',$message);
    }
    private function splitBalances(object $entry):bool {
        $lines=DB::table('bank_allocation_lines')->where('bank_entry_id',$entry->id)->get();
        if($lines->count()<2)return false;
        $sum=0;foreach($lines as $line)$sum+=(int)round((float)$line->amount*100);
        return $sum===(int)round(abs((float)$entry->amount)*100);
    }
    public function approveImport(Request $request,int $importId) {
        $data=$request->validate(['client_id'=>'required|integer|exists:clients,id']);
        $client=Client::findOrFail($data['client_id']);
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
        $item=DB::table('bank_imports')->findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return Storage::disk('local')->download($item->storage_path,basename($item->filename));
    }
}
