<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Client, ClientCredential, Receipt, AccountHead};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BankReconciliationController extends Controller
{
    private function client(Request $request, int $id): Client {
        $q=Client::whereKey($id)->where('status',true);
        if ($request->user() instanceof ClientCredential) $q->where('client_credential_id',$request->user()->id);
        return $q->firstOrFail();
    }
    public function imports(Request $request, int $businessId) {
        $this->client($request,$businessId);
        return response()->json(['data'=>DB::table('bank_imports')->where('client_id',$businessId)->latest()->get()]);
    }
    public function upload(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:csv,txt|max:10240','date_format'=>'nullable|in:d/m/Y,m/d/Y,Y-m-d']);
        $file=$request->file('file'); $hash=hash_file('sha256',$file->getRealPath());
        if(DB::table('bank_imports')->where('client_id',$businessId)->where('sha256',$hash)->exists())
            throw ValidationException::withMessages(['file'=>'This bank statement was already imported.']);
        $handle=fopen($file->getRealPath(),'r'); $header=fgetcsv($handle);
        if (!$header) throw ValidationException::withMessages(['file'=>'Empty CSV file.']);
        $head=array_map(fn($x)=>strtolower(trim(preg_replace('/\s+/',' ',preg_replace('/^\xEF\xBB\xBF/','',$x)))),$header);
        $col=function(array $names) use ($head) {foreach ($names as $n) { $i=array_search($n,$head,true); if($i!==false)return $i; }return null;};
        $date=$col(['date','transaction date','posted date']);
        $description=$col(['description','details','payee','merchant','narrative','transaction description']);
        $amount=$col(['amount','transaction amount']); $debit=$col(['debit','money out','paid out','withdrawal']); $credit=$col(['credit','money in','paid in','deposit']);
        $ref=$col(['reference','ref','transaction reference']);
        if($date===null || $description===null || ($amount===null && $debit===null && $credit===null)) {
            fclose($handle); throw ValidationException::withMessages(['file'=>'CSV needs Date, Description and Amount (or Debit/Credit) columns.']);
        }
        $rows=[];$line=1;
        try {
            while(($values=fgetcsv($handle))!==false) {
                $line++;if(!array_filter($values,fn($v)=>trim((string)$v)!==''))continue;
                $get=fn($i)=>$i===null?'':trim((string)($values[$i]??''));
                try { $rawDate=$get($date); $format=$request->input('date_format');
                    if($format) { $dt=Carbon::createFromFormat('!'.$format,$rawDate); if(!$dt || $dt->format($format)!==$rawDate)throw new \Exception('Invalid date'); }
                    else { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$rawDate)) throw new \Exception('Choose a date format for this file'); $dt=Carbon::createFromFormat('!Y-m-d',$rawDate); }
                    if (!$dt)throw new \Exception('Invalid date');
                    $money=function($v) { $v=str_replace([',','£',' ','\xC2\xA3'],'',$v); if($v==='')return 0.0; if(preg_match('/^\((.+)\)$/',$v,$m))$v='-'.$m[1];if(!is_numeric($v))throw new \Exception('Invalid amount');return (float)$v;};
                    $n=$amount!==null?$money($get($amount)):$money($get($credit))-$money($get($debit));
                    if(!is_finite($n)||abs($n)>999999999)throw new \Exception('Invalid amount');
                    $desc=$get($description);if($desc==='')throw new \Exception('Missing description');
                    $rows[]=['client_id'=>$businessId,'transaction_date'=>$dt->toDateString(),'description'=>mb_substr($desc,0,500),'reference'=>mb_substr($get($ref),0,255),'amount'=>number_format($n,2,'.',''),'created_at'=>now(),'updated_at'=>now()];
                } catch (\Throwable $e) {throw ValidationException::withMessages(['file'=>"Row {$line}: ".$e->getMessage()]);}
                if(count($rows)>10000)throw ValidationException::withMessages(['file'=>'Maximum 10,000 transactions per statement.']);
            }
        } finally {fclose($handle);}
        if(!$rows)throw ValidationException::withMessages(['file'=>'No bank transactions found.']);
        $id=DB::transaction(function()use($rows,$businessId,$hash,$file,$request){
            $id=DB::table('bank_imports')->insertGetId(['client_id'=>$businessId,'filename'=>$file->getClientOriginalName(),'sha256'=>$hash,'created_by'=>$request->user()->id,'transaction_count'=>count($rows),'created_at'=>now(),'updated_at'=>now()]);
            foreach(array_chunk($rows,500) as $chunk) { foreach($chunk as &$r)$r['bank_import_id']=$id;unset($r);DB::table('bank_entries')->insert($chunk); }return $id;
        });
        return response()->json(['import_id'=>$id,'count'=>count($rows)],201);
    }
    public function entries(Request $request,int $businessId) {
        $this->client($request,$businessId);
        $query=DB::table('bank_entries as b')->leftJoin('receipts as r','r.id','=','b.receipt_id')->where('b.client_id',$businessId)->select('b.*','r.receipt_number');
        if($request->filled('status'))$query->where('b.status',$request->input('status'));
        return response()->json($query->orderByDesc('b.transaction_date')->orderByDesc('b.id')->paginate(50));
    }
    public function suggestions(Request $request,int $businessId,int $entryId) {
        $client=$this->client($request,$businessId);
        $entry=DB::table('bank_entries')->where('client_id',$businessId)->find($entryId);abort_unless($entry,404);
        $candidates=Receipt::where('client_id',$businessId)->whereNull('deleted_at')->with('detail')->whereHas('detail',function($q)use($entry){
            $q->whereBetween('total_amount',[max(0,abs($entry->amount)-2),abs($entry->amount)+2]);
        })->limit(100)->get();
        $ranked=$candidates->map(function($r)use($entry){
            $amount=(float)($r->detail->total_amount??0);$diff=abs(abs((float)$entry->amount)-$amount);
            $score=$diff<0.005?60:($diff<=1?20:0);
            $name=strtolower((string)($r->supplier?:$r->notes?:''));$desc=strtolower($entry->description);
            if(strlen($name)>3 && (str_contains($desc,$name)||str_contains($name,$desc)))$score+=25;
            $date=$r->detail->invoice_date?:$r->receipt_date;
            if($date){$days=abs(Carbon::parse($date)->diffInDays(Carbon::parse($entry->transaction_date)));if($days<=3)$score+=15;elseif($days<=14)$score+=7;}
            return ['receipt_id'=>$r->id,'receipt_number'=>$r->receipt_number,'supplier'=>$r->supplier,'total_amount'=>$amount,'date'=>(string)$date,'score'=>$score,'account_head_id'=>$r->detail->account_head_id];
        })->sortByDesc('score')->take(10)->values();
        return response()->json(['data'=>$ranked]);
    }
    public function match(Request $request,int $businessId,int $entryId) {
        $client=$this->client($request,$businessId);
        if($request->user() instanceof ClientCredential)abort(403,'Accountant approval required.');
        $data=$request->validate(['receipt_id'=>'required|integer','account_head_id'=>'nullable|integer']);
        return DB::transaction(function()use($request,$businessId,$entryId,$data,$client){
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            abort_if($entry->status==='matched',409,'Entry already matched.');
            $receipt=Receipt::where('client_id',$businessId)->whereKey($data['receipt_id'])->firstOrFail();
            $head=$data['account_head_id']??$receipt->detail?->account_head_id;
            if($head!==null && !AccountHead::whereKey($head)->where('client_credential_id',$client->client_credential_id)->exists())abort(422,'Invalid account head.');
            $new=['status'=>'matched','receipt_id'=>$receipt->id,'account_head_id'=>$head,'matched_by'=>$request->user()->id,'matched_at'=>now(),'updated_at'=>now()];
            DB::table('bank_entries')->where('id',$entryId)->update($new);
            $this->audit($entry,$request->user()->id,'match',$new);
            return response()->json(['message'=>'Match recorded. Not yet posted to accounting ledger.']);
        });
    }
    public function unmatch(Request $request,int $businessId,int $entryId) {
        $this->client($request,$businessId);if($request->user() instanceof ClientCredential)abort(403);
        return DB::transaction(function()use($request,$businessId,$entryId){
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            $new=['status'=>'unmatched','receipt_id'=>null,'account_head_id'=>null,'matched_at'=>null,'matched_by'=>null,'updated_at'=>now()];
            DB::table('bank_entries')->where('id',$entryId)->update($new);$this->audit($entry,$request->user()->id,'unmatch',$new);
            return response()->json(['message'=>'Match reversed.']);
        });
    }
    private function audit($entry,int $actor,string $action,array $new):void {
        DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entry->id,'actor_id'=>$actor,'action'=>$action,'old_values'=>json_encode($entry),'new_values'=>json_encode($new),'created_at'=>now(),'updated_at'=>now()]);
    }
}
