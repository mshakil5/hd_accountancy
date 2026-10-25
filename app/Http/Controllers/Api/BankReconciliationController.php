<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{BankImport, Client, ClientCredential, Receipt, AccountHead};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\BankStatementRecognitionService;
use App\Services\BankStatementVerificationService;

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
        $text=$recognizer->extractPdfTextWithOcr($request->file('file')->getRealPath());
        if (mb_strlen(trim($text)) < 35) {
            return response()->json(['detected_bank'=>null,'detected_account_last_four'=>null,
                'confidence'=>'unknown','suggested_bank_account_id'=>null,'candidates'=>[],
                'requires_confirmation'=>true,'ocr_required'=>true,
                'note'=>'This PDF has no extractable text even after OCR. Select and verify the bank manually.']);
        }
        return response()->json($recognizer->detect($text,$accounts));
    }
    private function resolveBankAccount(Request $request, int $businessId, array $detection): int {
        $accounts = DB::table('reconciliation_bank_accounts')->where('client_id', $businessId)->where('active', true)->get();
        $chosen = $request->input('bank_account_id');
        if ($chosen !== null && $chosen !== '') {
            $match = $accounts->first(fn($a) => (string)$a->id === (string)$chosen);
            if (!$match) throw \Illuminate\Validation\ValidationException::withMessages(['bank_account_id' => 'Select a valid active bank account for this business.']);
            return (int)$match->id;
        }
        if (!empty($detection['suggested_bank_account_id'])) {
            return (int)$detection['suggested_bank_account_id'];
        }
        $bankName=trim((string)($detection['detected_bank']??''));
        $lastFour=trim((string)($detection['detected_account_last_four']??''));
        if ($bankName!=='' && preg_match('/^\d{4}$/',$lastFour)) {
            // An OCR-detected bank alone is not enough to distinguish two
            // accounts. Bank + final four digits is safe to register as new.
            return (int) DB::table('reconciliation_bank_accounts')->insertGetId([
                'client_id'=>$businessId,'bank_name'=>$bankName,
                'account_label'=>'Account ending '.$lastFour,'account_last_four'=>$lastFour,
                'currency'=>'GBP','active'=>true,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        throw \Illuminate\Validation\ValidationException::withMessages(['bank_account_id' => 'We could not safely identify this statement account. Upload a clearer first page or select its registered bank account.']);
    }
    public function upload(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:pdf|max:20480','bank_account_id'=>'nullable|integer']);
        $file=$request->file('file');

        $verification=app(BankStatementVerificationService::class);
        $analysis=['rows'=>[],'pages'=>[],'unparsed'=>0,'opening_balance'=>null,'closing_balance'=>null,'ocr'=>false,'pages_total'=>0,'text'=>''];

        $recognizer=app(BankStatementRecognitionService::class);
        $accounts=DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)->get()->all();
        // The verification engine keeps the PDF text layer to preserve row/page
        // mapping. Detection also needs OCR because a clean PDF can still render
        // its bank logo and name as an image.
        $detection=$recognizer->detect($recognizer->extractPdfTextWithOcr($file->getRealPath()),$accounts);
        $bankAccountId=$this->resolveBankAccount($request,$businessId,$detection);
        $selected=collect($accounts)->first(fn($account)=>(int)$account->id===$bankAccountId);
        $detectedBank=strtolower(trim((string)($detection['detected_bank']??'')));
        $selectedBank=strtolower(trim((string)($selected->bank_name??'')));
        $detectedLastFour=(string)($detection['detected_account_last_four']??'');
        $selectedLastFour=(string)($selected->account_last_four??'');
        $banksMatch=$detectedBank==='' || $selectedBank===$detectedBank
            || str_contains($selectedBank,$detectedBank) || str_contains($detectedBank,$selectedBank);
        if ($request->filled('bank_account_id') && ((!$banksMatch)
            || ($detectedLastFour!=='' && $selectedLastFour!=='' && $selectedLastFour!==$detectedLastFour))) {
            Log::warning('Bank statement account selection rejected', [
                'business_id' => $businessId,
                'selected_bank' => $selected->bank_name ?? null,
                'selected_last_four' => $selectedLastFour ?: null,
                'detected_bank' => $detection['detected_bank'] ?? null,
                'detected_last_four' => $detectedLastFour ?: null,
                'bank_matches' => $banksMatch,
                'last_four_matches' => $detectedLastFour === '' || $selectedLastFour === '' || $selectedLastFour === $detectedLastFour,
            ]);
            throw ValidationException::withMessages(['bank_account_id'=>'The selected bank account conflicts with the bank or account number detected in this statement. Choose the matching account or ask an accountant to register it first.']);
        }
        $hash=hash_file('sha256',$file->getRealPath());
        if(DB::table('bank_imports')->where('client_id',$businessId)->where('bank_account_id',$bankAccountId)->where('sha256',$hash)->exists())
            throw ValidationException::withMessages(['file'=>'This bank statement was already imported.']);

        $rows=$analysis['rows'];
        if(count($rows)>10000)throw ValidationException::withMessages(['file'=>'Maximum 10,000 transactions per statement.']);

        $storagePath=$file->storeAs('bank-statements/'.$businessId,bin2hex(random_bytes(16)).'.pdf','local');
        try {
            $result=DB::transaction(function()use($rows,$businessId,$bankAccountId,$hash,$file,$request,$storagePath,$analysis,$verification){
                $importId=DB::table('bank_imports')->insertGetId([
                    'client_id'=>$businessId,'bank_account_id'=>$bankAccountId,'filename'=>mb_substr($file->getClientOriginalName(),0,255),
                    'sha256'=>$hash,'storage_path'=>$storagePath,'file_type'=>'pdf',
                    'created_by'=>$request->user()->id,'transaction_count'=>0,
                    'processing_status'=>'awaiting_verification','verification_status'=>'pending',
                    'pages_total'=>count($analysis['pages']),
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
                    $row['client_id']=$businessId;$row['currency']='GBP';
                    $row['bank_import_id']=$importId;$row['bank_account_id']=$bankAccountId;
                    $row['line_fingerprint']=$key;$row['line_occurrence']=$occurrence;
                    $row['status']='needs_review';
                    $row['original_values']=json_encode([
                        'transaction_date'=>$row['transaction_date'],'description'=>$row['description'],
                        'amount'=>$row['amount'],'balance_after'=>$row['balance_after']??null,
                    ]);
                    $row['created_at']=now();$row['updated_at']=now();
                    DB::table('bank_entries')->insert($row);$added++;
                }
                DB::table('bank_imports')->where('id',$importId)->update(['transaction_count'=>$added]);

                $import=BankImport::findOrFail($importId);
                $summary=$verification->refresh($import,$analysis);

                $message=match($summary['status']){
                    BankStatementVerificationService::READY=>'Bank statement imported. All automated checks passed — ready for reconciliation.',
                    BankStatementVerificationService::REVIEW=>'Bank statement imported, but '.$summary['blocking'].' issue(s) need an accountant to compare the rows with the PDF before reconciliation.',
                    default=>'PDF uploaded safely, but no transactions could be extracted reliably. Please upload a text-based PDF export or ask your accountant to review the original.',
                };
                return ['import_id'=>$importId,'bank_account_id'=>$bankAccountId,'count'=>$added,'duplicates_skipped'=>$skipped,
                    'requires_review'=>$summary['status']!==BankStatementVerificationService::READY,
                    'verification_status'=>$summary['status'],'blocking_exceptions'=>$summary['blocking'],
                    'pages_total'=>$summary['pages_total'],'opening_balance'=>$summary['opening_balance'],
                    'closing_balance'=>$summary['closing_balance'],'calculated_closing_balance'=>$summary['calculated_closing_balance'],
                    'balance_difference'=>$summary['balance_difference'],'message'=>$message];
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
        $recognizer=app(BankStatementRecognitionService::class);
        try{$text=$recognizer->extractPdfTextWithOcr($path);}
        catch(\Throwable $e){throw ValidationException::withMessages(['file'=>'Unable to read this PDF. Export a text-based PDF from your bank.']);}
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
            if($head!==null && !AccountHead::whereKey($head)->where('client_credential_id',$client->client_credential_id)->whereNotNull('account_type_id')->exists())abort(422,'Invalid account head.');
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
        abort_unless(AccountHead::whereKey($data['account_head_id'])->where('client_credential_id',$client->client_credential_id)->whereNotNull('account_type_id')->exists(),422,'Invalid account head.');
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
             ->where('is_active',1)->whereNotNull('account_type_id')->orderBy('code')->get(['id','code','name'])]);
    }
    public function transfer(Request $request,int $businessId,int $entryId){
        $client=$this->client($request,$businessId);
        if($request->user() instanceof ClientCredential)abort(403,'Accountant approval required.');
        $data=$request->validate(['linked_entry_id'=>'required|integer','account_head_id'=>'nullable|integer']);
        return DB::transaction(function()use($request,$businessId,$entryId,$data,$client){
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            $linkedId=(int)$data['linked_entry_id'];
            abort_if($linkedId===$entryId,422,'Choose a different counterparty entry.');
            $counter=DB::table('bank_entries')->where('id',$linkedId)->where('client_id',$businessId)->lockForUpdate()->first();
            abort_unless($counter,404,'Counterparty bank entry not found.');
            abort_unless(abs(round((float)$entry->amount*100)+round((float)$counter->amount*100))<1,409,'Transfer entries must have equal and opposite amounts.');
            abort_unless(($entry->bank_account_id??0)!==($counter->bank_account_id??0),409,'Transfer must be between two different bank accounts.');
            $head=$data['account_head_id']??null;
            if($head!==null && !AccountHead::whereKey($head)->where('client_credential_id',$client->client_credential_id)->whereNotNull('account_type_id')->exists())abort(422,'Invalid account head.');
            $change=['transfer_type'=>'internal_transfer','linked_bank_entry_id'=>$linkedId,'status'=>'categorized','account_head_id'=>$head,'updated_at'=>now()];
            DB::table('bank_entries')->where('id',$entryId)->update($change);
            DB::table('bank_entries')->where('id',$linkedId)->update(['transfer_type'=>'internal_transfer','linked_bank_entry_id'=>$entryId,'status'=>'categorized','updated_at'=>now()]);
            $this->audit($entry,$request->user()->id,'transfer',$change);
            return response()->json(['message'=>'Internal transfer linked. Both sides post net-zero to the P&L on approval.']);
        });
    }
    public function settle(Request $request,int $businessId,int $entryId){
        $this->client($request,$businessId);
        if($request->user() instanceof ClientCredential)abort(403,'Accountant approval required.');
        $data=$request->validate(['receipt_id'=>'nullable|integer','account_head_id'=>'nullable|integer',
            'settlement_type'=>'nullable|in:exact,partial,over_payment,under_payment']);
        return DB::transaction(function()use($request,$businessId,$entryId,$data){
            $entry=DB::table('bank_entries')->where('client_id',$businessId)->lockForUpdate()->find($entryId);abort_unless($entry,404);
            $type=$data['settlement_type']??'partial';
            $bankAmt=abs((float)$entry->amount);
            if(!empty($data['receipt_id'])){
                $receipt=Receipt::with('detail')->where('client_id',$businessId)->findOrFail($data['receipt_id']);
                $receiptAmt=(float)($receipt->detail?->total_amount??0);
                $settled=min($bankAmt,$receiptAmt);$remaining=abs($bankAmt-$receiptAmt);
                DB::table('bank_settlements')->insert(['client_id'=>$businessId,'bank_entry_id'=>$entryId,
                    'receipt_id'=>$receipt->id,'bank_amount'=>$bankAmt,'receipt_amount'=>$receiptAmt,
                    'settled_amount'=>$settled,'remaining_amount'=>$remaining,'settlement_type'=>$type,
                    'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
                $new=['receipt_id'=>$receipt->id,'status'=>'categorized',
                    'account_head_id'=>$data['account_head_id']??$receipt->detail?->account_head_id,'updated_at'=>now()];
            } else {
                abort_unless(!empty($data['account_head_id']),422,'Choose an account head for the settlement.');
                $new=['status'=>'categorized','account_head_id'=>$data['account_head_id'],'updated_at'=>now()];
            }
            DB::table('bank_entries')->where('id',$entryId)->update($new);
            $this->audit($entry,$request->user()->id,'settle',$new);
            return response()->json(['message'=>'Settlement recorded. Approve the row to post it.']);
        });
    }
    public function settlements(Request $request,int $businessId){
        $this->client($request,$businessId);
        $rows=DB::table('bank_settlements as s')->leftJoin('receipts as r','r.id','=','s.receipt_id')
            ->where('s.client_id',$businessId)
            ->when($request->filled('status'),fn($q)=>$q->where('s.status',$request->input('status')))
            ->select('s.*','r.receipt_number','r.supplier')
            ->orderByDesc('s.id')->limit(200)->get();
        return response()->json(['data'=>$rows]);
    }
    private function audit($entry,int $actor,string $action,array $new):void {
        DB::table('bank_match_audits')->insert(['bank_entry_id'=>$entry->id,'actor_id'=>$actor,'action'=>$action,'old_values'=>json_encode($entry),'new_values'=>json_encode($new),'created_at'=>now(),'updated_at'=>now()]);
    }
}
