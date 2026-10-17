<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Client, ClientCredential, Receipt, AccountHead};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use App\Services\BankStatementRecognitionService;

class BankReconciliationController extends Controller
{
    private function client(Request $request, int $id): Client {
        $q=Client::whereKey($id)->where('status',true);
        if ($request->user() instanceof ClientCredential) $q->where('client_credential_id',$request->user()->id);
        return $q->firstOrFail();
    }
    public function imports(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $imports=DB::table('bank_imports')->where('client_id',$businessId)->when($request->filled('bank_account_id'),fn($q)=>$q->where('bank_account_id',$request->input('bank_account_id')))->latest()->limit(100)->get();
        $counts=DB::table('bank_entries')->where('client_id',$businessId)
            ->select('bank_import_id','status',DB::raw('count(*) as total'))
            ->groupBy('bank_import_id','status')->get()->groupBy('bank_import_id');
        $totals=DB::table('bank_entries')->where('client_id',$businessId)
            ->selectRaw('bank_import_id, sum(amount) as total')
            ->groupBy('bank_import_id')->pluck('total','bank_import_id');
        $approved=DB::table('bank_entries')->where('client_id',$businessId)->where('reconciliation_status','approved')
            ->selectRaw('bank_import_id, count(*) as total')->groupBy('bank_import_id')->pluck('total','bank_import_id');
        $imports->each(function($import)use($counts,$totals,$approved){
            $statusCounts=[];
            foreach($counts->get($import->id,collect()) as $row)$statusCounts[$row->status]=(int)$row->total;
            $import->status_counts=$statusCounts;
            $import->total_amount=round((float)($totals[$import->id]??0),2);
            $import->approved_count=(int)($approved[$import->id]??0);
        });
        return response()->json(['data'=>$imports]);
    }
    public function downloadStatement(Request $request,int $businessId,int $importId) {
        $this->client($request,$businessId);
        $item=DB::table('bank_imports')->where('client_id',$businessId)->find($importId);
        abort_unless($item && $item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return Storage::disk('local')->download($item->storage_path,basename($item->filename));
    }
    public function accounts(Request $request,int $businessId) {
        $this->client($request,$businessId);
        return response()->json(DB::table('reconciliation_bank_accounts')
            ->where('client_id',$businessId)->orderBy('bank_name')->get());
    }
    public function recogniseStatement(Request $request,int $businessId, BankStatementRecognitionService $recognizer) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:pdf|max:20480']);
        $accounts=DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)->get()->all();
        $text=$recognizer->extractPdfText($request->file('file')->getRealPath());
        if (mb_strlen(trim($text)) < 35) {
            return response()->json(['detected_bank'=>null,'detected_account_last_four'=>null,
                'confidence'=>'unknown','suggested_bank_account_id'=>null,'candidates'=>[],
                'requires_confirmation'=>true,'ocr_required'=>true,
                'note'=>'This PDF has insufficient selectable text. Scanned statements need a separately configured OCR service. Select and verify the bank manually.']);
        }
        return response()->json($recognizer->detect($text,$accounts));
    }
    private function resolveBankAccount(Request $request,int $businessId): int {
        $accounts=DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)->where('active',true)->get();
        $selected=$request->input('bank_account_id');
        if($selected!==null && $selected!=='') {
            $match=$accounts->first(fn($a)=>(string)$a->id===(string)$selected);
            if(!$match) throw ValidationException::withMessages(['bank_account_id'=>'Select a valid active bank account for this business.']);
            return (int)$match->id;
        }
        // Backwards-compatible for a business with just one known bank account.
        if($accounts->count()===1) return (int)$accounts->first()->id;
        throw ValidationException::withMessages(['bank_account_id'=>'Select a bank account before uploading. Contact your accountant to register the account if necessary.']);
    }
    public function upload(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:csv,txt,pdf|max:20480','date_format'=>'nullable|string|max:10','bank_account_id'=>'nullable|integer']);
        $file=$request->file('file');
        $isPdf=strtolower($file->getClientOriginalExtension())==='pdf';
        if ($isPdf) {
            $recognizer=app(BankStatementRecognitionService::class);
            $text=$recognizer->extractPdfText($file->getRealPath());
            $accounts=DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)->get()->all();
            $detection=$recognizer->detect($text,$accounts);
            $chosen=$request->input('bank_account_id');
            if (($chosen===null || $chosen==='') && count(array_filter($accounts,fn($a)=>(bool)$a->active)) > 1) {
                throw ValidationException::withMessages(['bank_account_id'=>'Confirm the bank account before uploading this PDF. Use the statement recognition preview for a suggestion.']);
            }
            if ($detection['confidence']==='high' && $chosen && (int)$chosen !== (int)$detection['suggested_bank_account_id']) {
                throw ValidationException::withMessages(['bank_account_id'=>'Statement account details appear to conflict with the selected bank account. Verify both before importing.']);
            }
        }
        $bankAccountId=$this->resolveBankAccount($request,$businessId);
        $hash=hash_file('sha256',$file->getRealPath());
        if(DB::table('bank_imports')->where('client_id',$businessId)->where('bank_account_id',$bankAccountId)->where('sha256',$hash)->exists())
            throw ValidationException::withMessages(['file'=>'This bank statement was already imported.']);
        $pdf=strtolower($file->getClientOriginalExtension())==='pdf';
        $rows=$pdf ? $this->readPdf($file->getRealPath(),$businessId) : $this->readCsv($file->getRealPath(),$businessId,$request->input('date_format'));
        if(count($rows)>10000)throw ValidationException::withMessages(['file'=>'Maximum 10,000 transactions per statement.']);
        if(!$rows && !$pdf)throw ValidationException::withMessages(['file'=>'No bank transactions found.']);
        $storagePath=$file->storeAs('bank-statements/'.$businessId,bin2hex(random_bytes(16)).'.'.($pdf?'pdf':'csv'),'local');
        try {
            $result=DB::transaction(function()use($rows,$businessId,$bankAccountId,$hash,$file,$request,$storagePath,$pdf){
                $id=DB::table('bank_imports')->insertGetId([
                    'client_id'=>$businessId,'bank_account_id'=>$bankAccountId,'filename'=>mb_substr($file->getClientOriginalName(),0,255),
                    'sha256'=>$hash,'storage_path'=>$storagePath,'file_type'=>$pdf?'pdf':'csv',
                    'created_by'=>$request->user()->id,'transaction_count'=>count($rows),
                    'processing_status'=>$pdf?($rows?'awaiting_verification':'manual_extraction_required'):'parsed',
                    'created_at'=>now(),'updated_at'=>now()
                ]);
                // Avoid re-importing overlapping statement lines, preserving repeated identical
                // payments inside a statement by tracking their occurrence number.
                $seen=[];$added=0;$skipped=0;
                foreach($rows as $row) {
                    $key=hash('sha256',implode('|',[$row['transaction_date'],$row['description'],$row['reference'],$row['amount']]));
                    $occurrence=($seen[$key]??0)+1;$seen[$key]=$occurrence;
                    $already=DB::table('bank_entries')->where('client_id',$businessId)->where('bank_account_id',$bankAccountId)
                        ->where('line_fingerprint',$key)->where('line_occurrence',$occurrence)->exists();
                    if($already){$skipped++;continue;}
                    $row['bank_import_id']=$id;$row['bank_account_id']=$bankAccountId;$row['line_fingerprint']=$key;$row['line_occurrence']=$occurrence;
                    $row['status']=$pdf?'needs_review':'unmatched';
                    DB::table('bank_entries')->insert($row);$added++;
                }
                DB::table('bank_imports')->where('id',$id)->update(['transaction_count'=>$added]);
                return ['import_id'=>$id,'bank_account_id'=>$bankAccountId,'count'=>$added,'duplicates_skipped'=>$skipped,'requires_review'=>$pdf,
                    'message'=>$pdf && !$rows?'PDF uploaded safely, but the bank layout could not be extracted reliably. Please provide a CSV or ask your accountant to review the original.':'Bank statement imported.'];
            });
        } catch(\Throwable $e) {Storage::disk('local')->delete($storagePath);throw $e;}
        return response()->json($result,201);
    }
    private function parseMoney(string $raw): float {
        $n=trim(str_replace(['£',',',' '],'',$raw));
        if(preg_match('/^\\((.*)\\)$/',$n,$m))$n='-'.$m[1];
        if(!preg_match('/^-?\\d+(?:\\.\\d{1,2})?$/',$n))throw new \RuntimeException('Invalid money amount');
        return round((float)$n,2);
    }
    private function makeRow(int $businessId,string $date,string $description,string $reference,float $amount): array {
        if(abs($amount)>999999999 || !is_finite($amount) || trim($description)==='')throw new \RuntimeException('Invalid bank transaction');
        return ['client_id'=>$businessId,'transaction_date'=>$date,'description'=>mb_substr(trim($description),0,500),
            'reference'=>mb_substr(trim($reference),0,255),'amount'=>number_format($amount,2,'.',''),
            'created_at'=>now(),'updated_at'=>now()];
    }
    // Ordered list of CSV date layouts tried when the caller does not force one.
    // Unambiguous values (e.g. day > 12) resolve themselves; genuinely ambiguous
    // values such as 01/02/2026 fall back to UK DD/MM/YYYY, the practice default.
    private const CSV_DATE_FORMATS=['d/m/Y','j/n/Y','m/d/Y','n/j/Y','Y-m-d','d-m-Y','j-n-Y','m-d-Y','n-j-Y','d.m.Y','j.n.Y','m.d.Y','n.j.Y','Y/m/d','Y.m.d','d M Y','j M Y','d/m/y','m/d/y','d-m-y','m-d-y','Ymd'];
    private function parseCsvDate(string $raw,?string $forced=null):?string {
        $raw=trim($raw);
        if($raw==='')return null;
        foreach($forced?[$forced]:self::CSV_DATE_FORMATS as $fmt){
            try{$dt=Carbon::createFromFormat('!'.$fmt,$raw);}
            catch(\Throwable $e){continue;}
            if($dt && $dt->format($fmt)===$raw)return $dt->format('Y-m-d');
        }
        return null;
    }
    private function readCsv(string $path,int $businessId,?string $format):array {
        $forced=in_array($format,['d/m/Y','m/d/Y','Y-m-d','d-m-Y','m-d-Y','d.m.Y'],true)?$format:null;
        $handle=fopen($path,'r');$header=fgetcsv($handle);
        if(!$header){fclose($handle);throw ValidationException::withMessages(['file'=>'Empty CSV file.']);}
        $head=array_map(fn($x)=>strtolower(trim(preg_replace('/\\s+/',' ',preg_replace('/^\\xEF\\xBB\\xBF/','',$x)))),$header);
        $col=function(array $names)use($head){foreach($names as $n){$i=array_search($n,$head,true);if($i!==false)return $i;}return null;};
        $date=$col(['date','transaction date','posted date']);$description=$col(['description','details','payee','merchant','narrative','transaction description']);
        $amount=$col(['amount','transaction amount']);$debit=$col(['debit','money out','paid out','withdrawal']);$credit=$col(['credit','money in','paid in','deposit']);
        $ref=$col(['reference','ref','transaction reference']);
        if($date===null || $description===null || ($amount===null && $debit===null && $credit===null)){
            fclose($handle);throw ValidationException::withMessages(['file'=>'CSV needs Date, Description and Amount or Debit/Credit.']);
        }
        $rows=[];$line=1;
        try {
            while(($values=fgetcsv($handle))!==false){
                $line++;if(!array_filter($values,fn($v)=>trim((string)$v)!==''))continue;
                $get=fn($i)=>$i===null?'':trim((string)($values[$i]??''));
                try{
                    $dateValue=$this->parseCsvDate($get($date),$forced);
                    if($dateValue===null)throw new \RuntimeException('Unrecognised date "'.mb_substr($get($date),0,40).'"');
                    $n=$amount!==null?$this->parseMoney($get($amount)):(
                        ($get($credit)===''?0:$this->parseMoney($get($credit)))-
                        ($get($debit)===''?0:$this->parseMoney($get($debit)))
                    );
                    $rows[]=$this->makeRow($businessId,$dateValue,$get($description),$get($ref),$n);
                }catch(\Throwable $e){throw ValidationException::withMessages(['file'=>"Row {$line}: ".$e->getMessage()]);}
                if(count($rows)>10000)throw ValidationException::withMessages(['file'=>'Statement exceeds 10,000 transactions.']);
            }
        } finally {fclose($handle);}
        return $rows;
    }
    private function readPdf(string $path,int $businessId):array {
        try{$text=(new Parser())->parseFile($path)->getText();}
        catch(\Throwable $e){throw ValidationException::withMessages(['file'=>'Unable to read this PDF. Export a text-based PDF or CSV from your bank.']);}
        if(mb_strlen(trim($text))<35)return []; // scanned PDF: retained for accountant review
        $rows=[];$unparsed=0;$candidateRows=0;
        // Conservative generic UK bank statement layout: date + payee/description +
        // signed final amount. Ambiguous tables, debit/credit/balance layouts and
        // documents requiring OCR are rejected rather than silently misclassified.
        foreach(preg_split('/\\R/u',$text) as $line){
            $line=trim(preg_replace('/\\s+/u',' ',$line));
            // Accept only unambiguous signed amounts and recognised UK date styles.
            if(!preg_match('/^(\d{2}[\/\-]\d{2}[\/\-]\d{4}|\d{1,2}\s+[A-Za-z]{3}\s+\d{4})\s+(.+?)\s+([+-]\s*£?\s*[\d,]+\.\d{2}|\(£?[\d,]+\.\d{2}\))$/u',$line,$m))continue;
            $dateInput=preg_replace('/\s+/',' ',trim($m[1]));
            $fmt=preg_match('/[A-Za-z]/',$dateInput)?'!j M Y':'!d/m/Y';
            if($fmt==='!d/m/Y')$dateInput=str_replace('-','/',$dateInput);
            try{$dt=Carbon::createFromFormat($fmt,$dateInput);}catch(\Throwable $e){continue;}
            if(!$dt || $dt->format(substr($fmt,1))!==$dateInput)continue;
            $desc=trim($m[2]);$amountString=trim(str_replace('£','',$m[3]));
            // Do not invent debit/credit direction: PDF must show an explicit + or -.
            if(!preg_match('/^[+-]|^\\(/',$amountString)){$unparsed++;continue;}
            $rows[]=$this->makeRow($businessId,$dt->format('Y-m-d'),$desc,'',$this->parseMoney($amountString));
        }
        if(!$rows || $unparsed>0 || $candidateRows!==count($rows))return []; // preserve original file, never guess totals

        return $rows;
    }
    public function entries(Request $request,int $businessId) {
        $this->client($request,$businessId);
        $query=DB::table('bank_entries as b')->leftJoin('receipts as r','r.id','=','b.receipt_id')->where('b.client_id',$businessId)->select('b.*','r.receipt_number');
        if($request->filled('status'))$query->where('b.status',$request->input('status'));
        if($request->filled('bank_account_id'))$query->where('b.bank_account_id',$request->input('bank_account_id'));
        if($request->filled('import_id'))$query->where('b.bank_import_id',$request->integer('import_id'));
        $perPage=min(max((int)$request->input('per_page',50),1),200);
        return response()->json($query->orderByDesc('b.transaction_date')->orderByDesc('b.id')->paginate($perPage));
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
            abort_if($entry->status==='needs_review',409,'Verify the PDF-extracted bank transaction first.');
            $receipt=Receipt::where('client_id',$businessId)->whereKey($data['receipt_id'])->firstOrFail();
            if(DB::table('bank_entries')->where('client_id',$businessId)->where('receipt_id',$receipt->id)
                ->where('status','matched')->where('id','!=',$entryId)->exists())
                abort(409,'This receipt has already been matched to another bank transaction. Split-payment matching requires manual review.');
            if($entry->status==='needs_review')
                abort(409,'Verify the PDF-extracted bank transaction before matching.');

            $receiptTotal=(float)($receipt->detail?->total_amount??0);
            abort_unless($receiptTotal>0 && abs($receiptTotal-abs((float)$entry->amount))<0.01,409,'Receipt and bank transaction amounts do not match. Partial payments require manual review.');
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
            abort_unless(in_array($entry->status,['matched','categorized']),409,'Nothing to undo.');
            $new=['status'=>'unmatched','receipt_id'=>null,'account_head_id'=>null,'matched_at'=>null,'matched_by'=>null,'updated_at'=>now()];
            DB::table('bank_entries')->where('id',$entryId)->update($new);$this->audit($entry,$request->user()->id,'unmatch',$new);
            return response()->json(['message'=>'Match reversed.']);
        });
    }
    public function review(Request $request,int $businessId,int $entryId) {
        $this->client($request,$businessId);
        if($request->user() instanceof ClientCredential)abort(403,'Accountant approval required.');
        $data=$request->validate([
            'transaction_date'=>'sometimes|required|date_format:Y-m-d',
            'description'=>'sometimes|required|string|max:500',
            'amount'=>'sometimes|required|numeric|between:-999999999,999999999',
            'reference'=>'nullable|string|max:255',
            'review_note'=>'nullable|string|max:2000',
        ]);
        return DB::transaction(function()use($request,$businessId,$entryId,$data){
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            abort_unless($entry->status==='needs_review',409,'Only pending PDF entries can be verified.');
            $new=array_merge($data,['status'=>'unmatched','updated_at'=>now()]);
            DB::table('bank_entries')->where('id',$entryId)->update($new);
            $this->audit($entry,$request->user()->id,'verify_pdf',$new);
            return response()->json(['message'=>'PDF bank transaction verified and ready for reconciliation.']);
        });
    }
    public function categorize(Request $request,int $businessId,int $entryId){
        $client=$this->client($request,$businessId);
        if($request->user() instanceof ClientCredential)abort(403,'Accountant approval required.');
        $data=$request->validate(['account_head_id'=>'required|integer','review_note'=>'nullable|string|max:2000']);
        abort_unless(AccountHead::whereKey($data['account_head_id'])->where('client_credential_id',$client->client_credential_id)->exists(),422,'Invalid account head.');
        return DB::transaction(function()use($request,$businessId,$entryId,$data) {
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            abort_if(in_array($entry->status,['matched','needs_review']),409,'Verify or unmatch the transaction first.');
            $new=['status'=>'categorized','account_head_id'=>$data['account_head_id'],
                  'review_note'=>$data['review_note']??null,'updated_at'=>now()];
            DB::table('bank_entries')->where('id',$entryId)->update($new);
            $this->audit($entry,$request->user()->id,'categorize',$new);
            return response()->json(['message'=>'Account category assigned. This does not create an accounting journal.']);
        });
    }
    public function missingReceipts(Request $request,int $businessId){
        $this->client($request,$businessId);
        $rows=DB::table('bank_receipt_requests as r')->join('bank_entries as e','r.bank_entry_id','=','e.id')
            ->where('r.client_id',$businessId)->where('r.status','open')
            ->select('r.id','r.bank_entry_id','r.message','r.created_at','e.transaction_date','e.description','e.amount')
            ->orderByDesc('r.created_at')->get();
        return response()->json(['data'=>$rows]);
    }
    public function accountHeads(Request $request,int $businessId){
        $client=$this->client($request,$businessId);
        return response()->json(['data'=>AccountHead::where('client_credential_id',$client->client_credential_id)
             ->where('is_active',1)->orderBy('code')->get(['id','code','name'])]);
    }
    private function audit($entry,int $actor,string $action,array $new):void {
        DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entry->id,'actor_id'=>$actor,'action'=>$action,'old_values'=>json_encode($entry),'new_values'=>json_encode($new),'created_at'=>now(),'updated_at'=>now()]);
    }
}
