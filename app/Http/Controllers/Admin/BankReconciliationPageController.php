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
        return view('admin.accounting.bank_reconciliation',compact('clients','selected','entries','heads','stats','imports','receipts'));
    }
    public function action(Request $request,int $entryId) {
        $data=$request->validate([
            'client_id'=>'required|integer|exists:clients,id',
            'action'=>'required|in:match,unmatch,verify,categorize,request_receipt',
            'receipt_id'=>'nullable|integer',
            'account_head_id'=>'nullable|integer',
            'review_note'=>'nullable|string|max:2000',
            'amount'=>'nullable|numeric|between:-999999999,999999999',
            'transaction_date'=>'nullable|date_format:Y-m-d',
            'description'=>'nullable|string|max:500'
        ]);
        $client=Client::findOrFail($data['client_id']);
        DB::transaction(function()use($request,$entryId,$client,$data){
            $entry=DB::table('bank_entries')->where('client_id',$client->id)->lockForUpdate()->find($entryId);
            abort_unless($entry,404);
            $change=['updated_at'=>now()];
            $action=$data['action'];
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
                $change+=['status'=>'unmatched','receipt_id'=>null,'account_head_id'=>null,'matched_by'=>null,'matched_at'=>null];
            } else {
                abort_if($entry->status==='needs_review',409,'Verify PDF transaction first.');
                $head=$data['account_head_id']??null;
                if($head!==null)abort_unless(AccountHead::where('client_credential_id',$client->client_credential_id)->whereKey($head)->exists(),422,'Invalid account head');
                if($action==='categorize'){
                    if(!$head)throw ValidationException::withMessages(['account_head_id'=>'Choose an account head.']);
                    $change+=['status'=>'categorized','account_head_id'=>$head,'review_note'=>$data['review_note']??null];
                } else {
                    $receipt=Receipt::with('detail')->where('client_id',$client->id)->findOrFail($data['receipt_id']??0);
                    abort_if($entry->status==='matched',409,'Already matched.');
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
