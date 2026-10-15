<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountHead;
use App\Models\Client;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BankReconciliationPageController extends Controller
{
    public function index(Request $request) {
        $clients=Client::orderBy('id','desc')->get(['id','client_credential_id']);
        $selected=(int)$request->query('client_id',0);
        if(!$selected && $clients->count())$selected=$clients->first()->id;
        $client=Client::find($selected);
        $entries=DB::table('bank_entries')->where('client_id',$selected)
            ->when($request->filled('status'),fn($q)=>$q->where('status',$request->input('status')))
            ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(30)->withQueryString();
        $heads=$client?AccountHead::where('client_credential_id',$client->client_credential_id)
            ->orderBy('code')->get(['id','code','name']):collect();
        $stats=DB::table('bank_entries')->where('client_id',$selected)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $imports=DB::table('bank_imports')->where('client_id',$selected)->latest()->limit(10)->get();
        $receipts=Receipt::where('client_id',$selected)->with('detail')->latest('id')->limit(400)->get()
            ->filter(fn($r)=>$r->detail && $r->detail->total_amount!==null);
        $splits=DB::table('bank_allocation_lines')->whereIn('bank_entry_id',$entries->getCollection()->pluck('id'))->get()->groupBy('bank_entry_id');
        $unmatchedReceipts=$receipts->filter(function($r)use($selected){
            return !DB::table('bank_entries')->where('client_id',$selected)->where('receipt_id',$r->id)->where('status','matched')->exists()
                && !DB::table('bank_allocation_lines as l')->join('bank_entries as b','b.id','=','l.bank_entry_id')
                   ->where('b.client_id',$selected)->where('l.receipt_id',$r->id)->exists();
        });
        return view('admin.accounting.bank_reconciliation',compact('clients','selected','entries','heads','stats','imports','receipts','splits','unmatchedReceipts'));
    }
    public function action(Request $request,int $entryId) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'action'=>'required|in:match,unmatch,verify,categorize,request_receipt,split,add_note',
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
        DB::transaction(function()use($request,$entryId,$client,$data){
            $entry=DB::table('bank_entries')->where('client_id',$client->id)->lockForUpdate()->find($entryId);
            abort_unless($entry,404);
            $change=['updated_at'=>now()];
            $action=$data['action'];
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
                $change=['status'=>'split','receipt_id'=>null,'account_head_id'=>null,'matched_by'=>auth()->id(),'matched_at'=>now(),'updated_at'=>now()];
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
            DB::table('bank_entries')->where('id',$entryId)->update($change);
            if($action==='match')DB::table('bank_receipt_requests')->where('bank_entry_id',$entryId)
                ->where('status','open')->update(['status'=>'resolved','updated_at'=>now()]);
            DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entryId,'actor_id'=>auth()->id(),
                'action'=>$action,'old_values'=>json_encode($entry),'new_values'=>json_encode($change),
                'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('admin.bank-reconciliation.index',['client_id'=>$client->id])
            ->with('success','Bank reconciliation action saved. No accounting journal posted.');
    }
    public function statement(int $importId){
        $item=DB::table('bank_imports')->findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return Storage::disk('local')->download($item->storage_path,basename($item->filename));
    }
}
