<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{BankImport, Client, ClientCredential, Receipt, AccountHead, BankAccount};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
        $imports=DB::table('bank_imports')
        ->leftJoin('reconciliation_bank_accounts','reconciliation_bank_accounts.id','=','bank_imports.bank_account_id')
        ->where('bank_imports.client_id',$businessId)
        ->when($request->filled('bank_account_id'),fn($q)=>$q->where('bank_imports.bank_account_id',$request->input('bank_account_id')))
        ->select('bank_imports.*','reconciliation_bank_accounts.bank_name')
        ->latest('bank_imports.created_at')->limit(100)->get();
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
    /**
     * Replace the PDF of an existing import (same import id). Allowed only while the
     * statement is not verified and has no reconciled rows; verification restarts.
     */
    public function replaceStatement(Request $request,int $businessId,int $importId) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:pdf|max:20480']);
        $import=BankImport::where('client_id',$businessId)->findOrFail($importId);

        $locked=DB::table('bank_entries')->where('bank_import_id',$importId)
            ->where(fn($q)=>$q->whereIn('status',['matched','categorized','split'])->orWhere('reconciliation_status','approved'))
            ->exists();
        if($import->verification_status===BankStatementVerificationService::VERIFIED || $locked)
            throw ValidationException::withMessages(['file'=>'This statement is already verified or reconciled, so its document cannot be replaced. Ask your accountant to reopen it.']);

        $file=$request->file('file');
        $hash=hash_file('sha256',$file->getRealPath());
        if($import->sha256===$hash)
            throw ValidationException::withMessages(['file'=>'This is already the current document.']);
        if(DB::table('bank_imports')->where('client_id',$businessId)->where('bank_account_id',$import->bank_account_id)
            ->where('sha256',$hash)->where('id','!=',$importId)->exists())
            throw ValidationException::withMessages(['file'=>'This bank statement was already imported.']);

        $verification=app(BankStatementVerificationService::class);
        // The new PDF is the source of truth: parse it, drop the lines that
        // belonged to the old document and store the new ones, then re-run the
        // verification checks against the replacement file.
        $analysis=$verification->analyse($file->getRealPath());
        // Never throw away readable rows for a PDF we cannot read — that would
        // silently empty a statement the accountant may already be working on.
        if(!$analysis['rows'])
            throw ValidationException::withMessages(['file'=>'We could not read any transactions from this PDF, so your current statement has been left unchanged.']);

        $oldPath=$import->storage_path;
        $newPath=$file->storeAs('bank-statements/'.$businessId,bin2hex(random_bytes(16)).'.pdf','local');
        if(!$newPath)throw ValidationException::withMessages(['file'=>'The file could not be saved. Please try again.']);

        $bankAccountId=(int)($import->bank_account_id??0);
        if($bankAccountId<=0)$bankAccountId=null;
        try {
            $result=DB::transaction(function()use($importId,$file,$hash,$newPath,$analysis,$verification,$businessId,$bankAccountId){
                DB::table('bank_imports')->where('id',$importId)->update([
                    'filename'=>mb_substr($file->getClientOriginalName(),0,255),
                    'sha256'=>$hash,'storage_path'=>$newPath,'file_type'=>'pdf',
                    'verification_status'=>'pending','processing_status'=>'awaiting_verification',
                    'verified_by'=>null,'verified_at'=>null,'verification_note'=>null,
                    'warnings_acknowledged_at'=>null,'warnings_acknowledged_by'=>null,'warnings_acknowledgement_note'=>null,
                    'updated_at'=>now(),
                ]);
                // Rows were checked against the old PDF, so they are replaced by
                // whatever the new document actually contains.
                DB::table('bank_entries')->where('bank_import_id',$importId)->delete();
                $stored=$this->storeStatementRows($importId,$businessId,$bankAccountId,$analysis['rows']);
                DB::table('bank_imports')->where('id',$importId)->update(['transaction_count'=>$stored['added']]);
                return $verification->refresh(BankImport::findOrFail($importId),$analysis);
            });
        } catch(\Throwable $e) {Storage::disk('local')->delete($newPath);throw $e;}

        if($oldPath && $oldPath!==$newPath)Storage::disk('local')->delete($oldPath);
        return response()->json([
            'import_id'=>$importId,'verification_status'=>$result['status'],
            'count'=>DB::table('bank_entries')->where('bank_import_id',$importId)->count(),
            'message'=>'Statement document replaced. It will be re-checked by your accountant.',
        ]);
    }
    /**
     * Move an existing statement to a different bank account. Used from the
     * statement screen when the wrong bank was picked at upload time, so the
     * PDF does not have to be uploaded again.
     */
    public function changeBankAccount(Request $request,int $businessId,int $importId) {
        $this->client($request,$businessId);
        $data=$request->validate(['bank_account_id'=>'required|integer']);
        $import=BankImport::where('client_id',$businessId)->findOrFail($importId);
        $account=DB::table('reconciliation_bank_accounts')->where('client_id',$businessId)
            ->where('active',true)->find($data['bank_account_id']);
        if(!$account)
            throw ValidationException::withMessages(['bank_account_id'=>'Select a valid active bank account for this business.']);

        if((int)$import->bank_account_id===(int)$account->id)
            return response()->json([
                'import_id'=>$importId,'bank_account_id'=>(int)$account->id,'bank_name'=>$account->bank_name,
                'message'=>'This statement is already linked to '.$account->bank_name.'.',
            ]);

        $locked=DB::table('bank_entries')->where('bank_import_id',$importId)
            ->where(fn($q)=>$q->whereIn('status',['matched','categorized','split'])->orWhere('reconciliation_status','approved'))
            ->exists();
        if($import->verification_status===BankStatementVerificationService::VERIFIED || $locked)
            throw ValidationException::withMessages(['bank_account_id'=>'This statement is already verified or reconciled, so its bank account cannot be changed. Ask your accountant to reopen it.']);

        if($import->sha256 && DB::table('bank_imports')->where('client_id',$businessId)
            ->where('bank_account_id',$account->id)->where('sha256',$import->sha256)->where('id','!=',$importId)->exists())
            throw ValidationException::withMessages(['bank_account_id'=>'That statement is already imported for '.$account->bank_name.'.']);

        try {
            DB::transaction(function()use($importId,$account){
                DB::table('bank_imports')->where('id',$importId)
                    ->update(['bank_account_id'=>$account->id,'updated_at'=>now()]);
                // Statement lines are unique per bank account, so they move with
                // the statement — otherwise reconciliation filters would disagree.
                DB::table('bank_entries')->where('bank_import_id',$importId)
                    ->update(['bank_account_id'=>$account->id,'updated_at'=>now()]);
            });
        } catch(\Illuminate\Database\QueryException $e) {
            if((string)$e->getCode()==='23000')
                throw ValidationException::withMessages(['bank_account_id'=>'These statement lines already exist under '.$account->bank_name.'. Choose the bank account that holds them.']);
            throw $e;
        }

        return response()->json([
            'import_id'=>$importId,'bank_account_id'=>(int)$account->id,'bank_name'=>$account->bank_name,
            'message'=>'Statement moved to '.$account->bank_name.'.',
        ]);
    }
    /** Short-lived link so the phone can open the PDF in a browser/PDF viewer (no bearer header possible there). */
    public function statementLink(Request $request,int $businessId,int $importId) {
        $this->client($request,$businessId);
        $item=DB::table('bank_imports')->where('client_id',$businessId)->find($importId);
        abort_unless($item && $item->storage_path && Storage::disk('local')->exists($item->storage_path),404,'Statement file not found.');
        $token=Str::random(48);
        Cache::put('bank_pdf_link:'.$token,$importId,now()->addMinutes(10));
        $url=route('api.bank-imports.pdf-public',['token'=>$token]);
        if(str_starts_with((string)config('app.url'),'https://'))$url=preg_replace('/^http:/','https:',$url);
        return response()->json(['url'=>$url,'filename'=>basename($item->filename)]);
    }
    public function statementPublic(string $token) {
        $importId=Cache::get('bank_pdf_link:'.$token);
        abort_unless($importId,404);
        $item=DB::table('bank_imports')->find($importId);
        abort_unless($item && $item->storage_path && Storage::disk('local')->exists($item->storage_path),404);
        return response()->file(Storage::disk('local')->path($item->storage_path),[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'inline; filename="'.addslashes(basename($item->filename)).'"',
        ]);
    }
    public function accounts(Request $request,int $businessId) {
        $this->client($request,$businessId);
        return response()->json(DB::table('reconciliation_bank_accounts')
            ->where('client_id',$businessId)->where('active',true)->orderBy('bank_name')->get());
    }

    /**
     * Register a new bank account for this business so it can be picked
     * when uploading statements. The user supplies the details manually;
     * we do NOT auto-create from OCR results.
     */
    public function storeAccount(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $data = $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_last_four'=> 'nullable|string|size:4|regex:/^\d{4}$/',
            'sort_code'       => 'nullable|string|max:32',
        ]);

        $payload = [
            'client_id'        => $businessId,
            'bank_name'        => trim($data['bank_name']),
            'account_last_four'=> $data['account_last_four'] ?? null,
            'currency'         => 'GBP',
            'active'           => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
        if (Schema::hasColumn('reconciliation_bank_accounts','account_label')) {
            // Legacy column is NOT NULL on older databases; the app no longer
            // collects a label, so store an empty value.
            $payload['account_label'] = '';
        }
        if (Schema::hasColumn('reconciliation_bank_accounts','sort_code')
            && !empty($data['sort_code'])) {
            $payload['sort_code'] = $data['sort_code'];
        }
        $id = DB::table('reconciliation_bank_accounts')->insertGetId($payload);

        return response()->json([
            'id'               => $id,
            'bank_name'        => trim($data['bank_name']),
            'account_last_four'=> $data['account_last_four'] ?? null,
            'sort_code'        => $data['sort_code'] ?? null,
            'currency'         => 'GBP',
            'active'           => true,
        ], 201);
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
        // The user MUST pick an account explicitly. No auto-creation from OCR.
        throw \Illuminate\Validation\ValidationException::withMessages(['bank_account_id' => 'Please select or create a bank account for this statement before uploading.']);
    }
    public function upload(Request $request, int $businessId) {
        $this->client($request,$businessId);
        $request->validate(['file'=>'required|file|mimes:pdf|max:20480','bank_account_id'=>'required|integer']);
        $file=$request->file('file');

        $verification=app(BankStatementVerificationService::class);
        // Parse the PDF once. The same analysis drives bank detection, the
        // stored transaction rows and the verification checks, so the statement
        // and its rows can never disagree.
        $analysis=$verification->analyse($file->getRealPath());

        $recognizer = app(BankStatementRecognitionService::class);
        $accounts = DB::table('reconciliation_bank_accounts')->where('client_id', $businessId)->get()->all();
        // Reuse the text already extracted above; only fall back to the slow
        // OCR pass when the PDF produced no usable text at all.
        $detectionText = trim((string) ($analysis['text'] ?? ''));
        if ($detectionText === '') {
            $detectionText = $recognizer->extractPdfTextWithOcr($file->getRealPath());
        }
        $detection = $recognizer->detect($detectionText, $accounts);
        $bankAccountId=$this->resolveBankAccount($request,$businessId,$detection);
        $selected=collect($accounts)->first(fn($account)=>(int)$account->id===$bankAccountId);
        // The user explicitly picked this account. Log a warning if the OCR
        // disagrees, but never block — the choice was deliberate.
        $detectedBank=strtolower(trim((string)($detection['detected_bank']??'')));
        $selectedBank=strtolower(trim((string)($selected->bank_name??'')));
        $detectedLastFour=(string)($detection['detected_account_last_four']??'');
        $selectedLastFour=(string)($selected->account_last_four??'');
        $banksMatch=$detectedBank==='' || $selectedBank===$detectedBank
            || str_contains($selectedBank,$detectedBank) || str_contains($detectedBank,$selectedBank);
        if ((!$banksMatch) || ($detectedLastFour!=='' && $selectedLastFour!=='' && $selectedLastFour!==$detectedLastFour)) {
            Log::warning('Bank statement uploaded against user-selected account that OCR disagrees with', [
                'business_id' => $businessId,
                'selected_bank' => $selected->bank_name ?? null,
                'selected_last_four' => $selectedLastFour ?: null,
                'detected_bank' => $detection['detected_bank'] ?? null,
                'detected_last_four' => $detectedLastFour ?: null,
            ]);
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
                $stored=$this->storeStatementRows($importId,$businessId,$bankAccountId,$rows);
                DB::table('bank_imports')->where('id',$importId)->update(['transaction_count'=>$stored['added']]);

                $import=BankImport::findOrFail($importId);
                $summary=$verification->refresh($import,$analysis);

                $message=match($summary['status']){
                    BankStatementVerificationService::READY=>'Bank statement imported. All automated checks passed — ready for reconciliation.',
                    BankStatementVerificationService::REVIEW=>'Bank statement imported, but '.$summary['blocking'].' issue(s) need an accountant to compare the rows with the PDF before reconciliation.',
                    default=>'PDF uploaded safely, but no transactions could be extracted reliably. Please upload a text-based PDF export or ask your accountant to review the original.',
                };
                return ['import_id'=>$importId,'bank_account_id'=>$bankAccountId,'count'=>$stored['added'],'duplicates_skipped'=>$stored['skipped'],
                    'requires_review'=>$summary['status']!==BankStatementVerificationService::READY,
                    'verification_status'=>$summary['status'],'blocking_exceptions'=>$summary['blocking'],
                    'pages_total'=>$summary['pages_total'],'opening_balance'=>$summary['opening_balance'],
                    'closing_balance'=>$summary['closing_balance'],'calculated_closing_balance'=>$summary['calculated_closing_balance'],
                    'balance_difference'=>$summary['balance_difference'],'message'=>$message];
            });
        } catch(\Throwable $e) {Storage::disk('local')->delete($storagePath);throw $e;}
        return response()->json($result,201);
    }

    /**
     * Store the parsed statement lines for an import.
     *
     * Repeated identical payments inside one statement are preserved by
     * tracking an occurrence number, while lines that already exist for the
     * same bank account are skipped so overlapping statements can be uploaded
     * without creating duplicates.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array{added:int,skipped:int}
     */
    private function storeStatementRows(int $importId, int $businessId, ?int $bankAccountId, array $rows): array {
        $seen=[];$added=0;$skipped=0;
        foreach($rows as $row) {
            $key=hash('sha256',implode('|',[$row['transaction_date'],$row['description'],$row['reference']??'',number_format((float)$row['amount'],2,'.','')]));
            $occurrence=($seen[$key]??0)+1;$seen[$key]=$occurrence;
            $already=DB::table('bank_entries')->where('client_id',$businessId)
                ->when($bankAccountId,fn($q)=>$q->where('bank_account_id',$bankAccountId))
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
        return ['added'=>$added,'skipped'=>$skipped];
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