<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankImport;
use App\Models\Client;
use App\Models\ClientCredential;
use Auth;
use App\Services\BankLedgerPostingService;
use App\Services\BankStatementVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BankStatementPageController extends Controller
{
    public function __construct(
        private BankStatementVerificationService $verification,
        private BankLedgerPostingService $posting
    ) {}

    public function index(Request $request)
    {
        if (Auth::user() && Auth::user()->email === 'jane@gmail.com') {
            $david = ClientCredential::where('email', 'david@gmail.com')->first();
            if ($david) {
                $request->merge(['client_credential_id' => $david->id]);
            }
        }

        $credentialId  = (int)$request->query('client_credential_id', 0);
        $businessId    = (int)$request->query('client_id', 0);
        $bankAccountId = (int)$request->query('bank_account_id', 0);

        $businessIds = $credentialId
            ? Client::where('client_credential_id', $credentialId)->where('status', 1)->pluck('id')->all()
            : [];

        if ($businessId) {
            if (in_array($businessId, $businessIds, true)) {
                $businessIds = [$businessId];
            } else {
                $businessId = 0;
                $request->query->remove('client_id');
            }
        }

        $bankAccounts = $businessIds
            ? DB::table('reconciliation_bank_accounts')->whereIn('client_id', $businessIds)->orderBy('bank_name')->get()
            : collect();

        if ($bankAccountId && !$bankAccounts->contains('id', $bankAccountId)) {
            $bankAccountId = 0;
            $request->query->remove('bank_account_id');
        }

        if (!$credentialId || !$businessIds) {
            return view('admin.accounting.bank_statements', [
                'credentialId'  => $credentialId,
                'businessId'    => $businessId,
                'bankAccounts'  => $bankAccounts,
                'bankAccountId' => $bankAccountId,
                'imports'       => DB::table('bank_imports')->whereRaw('1 = 0')->paginate(20),
                'importStats'   => [
                    'total'        => 0,
                    'verified'     => 0,
                    'needs_review' => 0,
                    'ready'        => 0,
                    'failed'       => 0,
                    'pending'      => 0,
                ],
            ]);
        }



        $query = DB::table('bank_imports')
            ->whereIn('client_id', $businessIds)
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))
            ->when($request->filled('verification_status'), fn($q) => $q->where('verification_status', $request->input('verification_status')));

        $rawStats = DB::table('bank_imports')
            ->whereIn('client_id', $businessIds)
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))
            ->selectRaw('verification_status, count(*) as count')
            ->groupBy('verification_status')
            ->pluck('count', 'verification_status');

        $importStats = [
            'total' => $rawStats->sum(),
            'verified' => (int)($rawStats['verified'] ?? 0),
            'needs_review' => (int)($rawStats['needs_accountant_review'] ?? 0),
            'ready' => (int)($rawStats['ready_for_reconciliation'] ?? 0),
            'failed' => (int)($rawStats['extraction_failed'] ?? 0),
            'pending' => (int)($rawStats['pending'] ?? 0),
        ];

        $imports = $query->orderByDesc('id')->paginate(20)->withQueryString();

        // Row counts per statement, used by the delete confirmation so the
        // accountant knows exactly what else disappears with the statement.
        $importIds = collect($imports->items())->pluck('id')->all();
        $hasReconciliation = Schema::hasColumn('bank_entries', 'journal_posted_at');
        $lockedSql = $hasReconciliation
            ? 'sum(case when journal_posted_at is not null or reconciliation_status = ? then 1 else 0 end)'
            : '0';
        $entryStats = $importIds
            ? DB::table('bank_entries')
                ->whereIn('bank_import_id', $importIds)
                ->selectRaw('bank_import_id, count(*) as rows_total, ' . $lockedSql . ' as rows_locked', $hasReconciliation ? ['approved'] : [])
                ->groupBy('bank_import_id')
                ->get()
                ->keyBy('bank_import_id')
            : collect();

        return view('admin.accounting.bank_statements', compact(
            'credentialId',
            'businessId',
            'bankAccountId',
            'bankAccounts',
            'imports',
            'importStats',
            'entryStats'
        ));
    }



    /**
     * Show the create form so a statement can be uploaded straight from the
     * website, mirroring the receipts "New" flow.
     */
    public function create(Request $request)
    {
        return view('admin.accounting.bank_statement_create', [
            'presetCredentialId' => (int) $request->query('client_credential_id', 0),
            'presetBusinessId'   => (int) $request->query('client_id', 0),
        ]);
    }

    /**
     * Register a bank account for the chosen business so it can be picked when
     * uploading. Same fields and behaviour as the app (BankReconciliationController::storeAccount).
     */
    public function storeBankAccount(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required|integer|exists:clients,id',
            'bank_name'         => 'required|string|max:100',
            'account_last_four' => 'nullable|digits:4',
            'sort_code'         => 'nullable|string|max:32',
        ]);

        $client = Client::findOrFail($data['client_id']);
        if ($request->filled('client_credential_id')) {
            abort_unless((int) $client->client_credential_id === (int) $request->input('client_credential_id'), 403);
        }

        $bankName = trim($data['bank_name']);
        $lastFour = $data['account_last_four'] ?? null;

        // Selecting the same bank twice would otherwise create two identical
        // entries in the statement picker; reuse the existing one instead.
        $existing = DB::table('reconciliation_bank_accounts')
            ->where('client_id', $client->id)
            ->where('active', 1)
            ->whereRaw('LOWER(TRIM(bank_name)) = ?', [mb_strtolower($bankName)])
            ->get()
            ->first(fn($bank) => (string) ($bank->account_last_four ?? '') === (string) ($lastFour ?? ''));

        if ($existing) {
            return response()->json([
                'status'            => 200,
                'id'                => (int) $existing->id,
                'bank_name'         => trim((string) $existing->bank_name),
                'account_last_four' => $existing->account_last_four ?? null,
                'reused'            => true,
                'message'           => 'That bank account already exists, so it was selected instead of adding a duplicate.',
            ]);
        }

        $payload = [
            'client_id'         => $client->id,
            'bank_name'         => $bankName,
            'account_last_four' => $lastFour,
            'currency'          => 'GBP',
            'active'            => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
        if (Schema::hasColumn('reconciliation_bank_accounts', 'account_label')) {
            // Legacy column is NOT NULL on older databases; the form does not
            // collect a label, so store an empty value.
            $payload['account_label'] = '';
        }
        if (!empty($data['sort_code']) && Schema::hasColumn('reconciliation_bank_accounts', 'sort_code')) {
            $payload['sort_code'] = $data['sort_code'];
        }

        $id = DB::table('reconciliation_bank_accounts')->insertGetId($payload);

        return response()->json([
            'status'            => 200,
            'id'                => $id,
            'bank_name'         => $payload['bank_name'],
            'account_last_four' => $payload['account_last_four'],
        ]);
    }

    /**
     * Active bank accounts for one business — exactly what the app's bank
     * picker shows: legacy "Unknown historical bank" buckets and blank names
     * hidden, sort code and last four included.
     */
    public function accounts(Request $request)
    {
        $clientId     = (int) $request->query('client_id', 0);
        $credentialId = (int) $request->query('client_credential_id', 0);

        if ($clientId <= 0) {
            return response()->json([]);
        }

        $client = Client::where('id', $clientId)
            ->where('status', 1)
            ->when($credentialId, fn($q) => $q->where('client_credential_id', $credentialId))
            ->first();

        if (!$client) {
            return response()->json([]);
        }

        $columns = ['id', 'bank_name', 'account_last_four'];
        if (Schema::hasColumn('reconciliation_bank_accounts', 'sort_code')) {
            $columns[] = 'sort_code';
        }

        return response()->json(
            DB::table('reconciliation_bank_accounts')
                ->where('client_id', $client->id)
                ->where('active', 1)
                ->orderBy('bank_name')
                ->get($columns)
                ->filter(fn($bank) => $this->usableBankName($bank->bank_name ?? null) !== null)
                ->map(fn($bank) => [
                    'id'                => (int) $bank->id,
                    'bank_name'         => trim((string) $bank->bank_name),
                    'account_last_four' => $bank->account_last_four ?? null,
                    'sort_code'         => $bank->sort_code ?? null,
                ])
                ->values()
        );
    }

    /** Legacy buckets and empty names cannot be picked for a statement. */
    private function usableBankName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '' || mb_strtolower($name) === 'unknown historical bank') {
            return null;
        }

        return $name;
    }

    /**
     * Store a statement uploaded from the website. The PDF is filed in the same
     * S3 layout and with the same rows as the mobile app upload path, so both
     * entry points feed one verification/reconciliation pipeline.
     */
    public function store(Request $request)
    {
        $errors = [];
        if (!$request->client_credential_id) $errors[] = 'Client (Credential) is required.';
        if (!$request->client_id)            $errors[] = 'Business / Client is required.';
        if (!$request->bank_account_id)      $errors[] = 'Bank account is required.';

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:51200',
        ]);

        if (count($errors)) {
            return $this->statementError($errors);
        }

        $client = Client::where('id', (int) $request->client_id)
            ->where('client_credential_id', (int) $request->client_credential_id)
            ->where('status', 1)
            ->first();
        if (!$client) {
            return $this->statementError(['That business does not belong to the selected client.']);
        }

        $account = DB::table('reconciliation_bank_accounts')
            ->where('id', (int) $request->bank_account_id)
            ->where('client_id', $client->id)
            ->where('active', 1)
            ->first();
        if (!$account) {
            return $this->statementError(['Select a valid active bank account for this business.']);
        }

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());

        if (DB::table('bank_imports')
            ->where('client_id', $client->id)
            ->where('bank_account_id', $account->id)
            ->where('sha256', $hash)
            ->exists()) {
            return $this->statementError(['This bank statement was already imported for ' . $account->bank_name . '.']);
        }

        $uploadedPath = null;
        try {
            $importId = DB::transaction(function () use ($client, $account, $file, $hash, &$uploadedPath) {
                $importId = DB::table('bank_imports')->insertGetId([
                    'client_id'           => $client->id,
                    'bank_account_id'     => $account->id,
                    'filename'            => mb_substr($file->getClientOriginalName(), 0, 255),
                    'sha256'              => $hash,
                    'storage_path'        => '',
                    'file_type'           => 'pdf',
                    'created_by'          => Auth::id(),
                    'transaction_count'   => 0,
                    'processing_status'   => 'awaiting_verification',
                    'verification_status' => 'pending',
                    'pages_total'         => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                $statementDir = $this->getStatementDirectory($client, $importId);
                $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                $uploadedPath = Storage::disk('s3')->putFileAs($statementDir, $file, $filename, 'public');

                DB::table('bank_imports')->where('id', $importId)->update(['storage_path' => $uploadedPath]);

                return $importId;
            });
        } catch (\Throwable $e) {
            if ($uploadedPath) {
                Storage::disk('s3')->delete($uploadedPath);
            }
            report($e);
            return $this->statementError(['The statement could not be uploaded. Please try again.']);
        }

        // Optional immediate scan so the accountant lands on rows ready to
        // verify instead of an empty workspace (same work as "Re-Scan with OCR").
        $extractionWarning = null;
        if ($request->boolean('run_extraction')) {
            @set_time_limit(300);
            try {
                $this->verification->rescanWithOcr(BankImport::findOrFail($importId));
            } catch (\Throwable $e) {
                report($e);
                $extractionWarning = 'The PDF was uploaded, but the automatic scan could not read its rows. Run "Re-Scan with OCR" from the verification workspace.';
            }
        }

        $message = 'Bank statement uploaded for ' . $account->bank_name . '.';
        if (!$request->boolean('run_extraction')) {
            $message .= ' Run "Re-Scan with OCR" to extract its transactions.';
        }

        return response()->json([
            'status'   => 200,
            'message'  => $message,
            'warning'  => $extractionWarning,
            'id'       => $importId,
            'redirect' => route('admin.bank-statements.verification', $importId),
        ]);
    }

    private function statementError(array $messages)
    {
        return response()->json([
            'status'  => 303,
            'message' => "<div class='alert alert-danger'><ul class='mb-0'><li>" . implode('</li><li>', $messages) . '</li></ul></div>',
        ]);
    }

    public function verification(int $importId)
    {
        $import = BankImport::findOrFail($importId);
        // Opening the workspace is a read: never re-parse the PDF here. Extraction
        // runs on upload, on row edits and via "Refresh checks"; only a statement
        // that has never been analysed is processed once, on its first visit.
        if ($import->verification_status === null || $import->verification_status === 'pending') {
            $this->verification->refresh($import);
            $import->refresh();
        }

        $entries = DB::table('bank_entries')->where('bank_import_id', $importId)
            ->orderByRaw('COALESCE(source_page,1)')->orderByRaw('COALESCE(source_position,0)')->orderBy('id')->get();
        // Display oldest → newest with manually added rows interleaved by date.
        $entries = collect($this->verification->sortChronologically($entries, $import->opening_balance !== null ? (float) $import->opening_balance : null));
        $pages = DB::table('bank_statement_pages')->where('bank_import_id', $importId)->orderBy('page_no')->get();
        $exceptions = DB::table('bank_statement_exceptions')->where('bank_import_id', $importId)
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')->orderByDesc('id')->get();
        $revisions = DB::table('bank_entry_revisions')->whereIn('bank_entry_id', $entries->pluck('id'))
            ->latest('id')->limit(100)->get();

        return view('admin.accounting.bank_verification', [
            'import' => $import,
            'entries' => $entries,
            'pages' => $pages,
            'exceptions' => $exceptions,
            'revisions' => $revisions,
            'blocking' => $this->verification->blockingCount($import),
            'bank' => DB::table('reconciliation_bank_accounts')->find($import->bank_account_id),
            'client' => $import->client,
        ]);
    }

    /** Re-run the extraction/check set on demand (the page itself stays read-only). */
    public function refreshImport(int $importId)
    {
        @set_time_limit(300);
        $import = BankImport::findOrFail($importId);

        try {
            $this->verification->refresh($import);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'The statement could not be re-checked: ' . $e->getMessage());
        }

        return back()->with('success', 'Statement checks refreshed.');
    }

    public function streamStatement(int $importId)
    {
        $item = BankImport::findOrFail($importId);
        $headers = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes(basename($item->filename)) . '"',
        ];

        // Stream the file: reading the whole PDF into a string doubles its
        // memory cost and exhausts the limit on large statements.
        if ($item->storage_path && Storage::disk('s3')->exists($item->storage_path)) {
            $stream = Storage::disk('s3')->readStream($item->storage_path);
            if ($stream !== false) {
                return response()->stream(function () use ($stream) {
                    fpassthru($stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }, 200, $headers);
            }
        }

        if ($item->storage_path && Storage::disk('local')->exists($item->storage_path)) {
            return response()->file(Storage::disk('local')->path($item->storage_path), $headers);
        }

        abort(404, 'Statement file not found in cloud or local storage.');
    }

    public function downloadStatement(int $importId)
    {
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

    public function rescanOcr(Request $request, int $importId)
    {
        @set_time_limit(300);
        $import = BankImport::findOrFail($importId);

        try {
            $this->verification->rescanWithOcr($import);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'OCR Scan error: ' . $e->getMessage());
        }

        return back()->with('success', 'Deep OCR re-scan completed. Rows and system balances have been re-extracted and reconciled.');
    }

    public function verifyImport(Request $request, int $importId)
    {
        $data = $request->validate(['verification_note' => 'nullable|string|max:2000']);
        $import = BankImport::findOrFail($importId);
        try {
            $this->verification->verify($import, auth()->id(), $data['verification_note'] ?? null);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }
        return back()->with('success', 'Statement verified. Its rows can now be reconciled.');
    }

    public function updateEntry(Request $request, int $entryId)
    {
        $data = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|between:-999999999,999999999',
            'reason' => 'required|string|max:1000',
        ]);
        $entry = DB::table('bank_entries')->find($entryId);
        abort_unless($entry, 404);
        abort_if(($entry->reconciliation_status ?? 'draft') === 'approved', 409, 'Approved reconciliations cannot be edited.');
        $import = BankImport::findOrFail($entry->bank_import_id);

        DB::transaction(function () use ($entry, $data, $import) {
            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id' => $entry->id,
                'author_id' => auth()->id(),
                'action' => 'manual_edit',
                'reason' => $data['reason'],
                'original_values' => json_encode([
                    'transaction_date' => $entry->transaction_date,
                    'description' => $entry->description,
                    'amount' => $entry->amount,
                ]),
                'corrected_values' => json_encode([
                    'transaction_date' => $data['transaction_date'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                ]),
                'created_at' => now(),
            ]);

            DB::table('bank_entries')->where('id', $entry->id)->update([
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'is_manually_corrected' => 1,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

            // Statement edits reopen verification: the accountant must confirm
            // the corrected rows again (matches BankReconciliationPageController).
            $this->verification->reopen($import);
            $this->verification->refresh($import);
        });

        return back()->with('success', 'Row updated and re-verified against the PDF balance.');
    }

    public function storeEntry(Request $request, int $importId)
    {
        $data = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|between:-999999999,999999999',
            'reason' => 'required|string|max:1000',
        ]);
        $import = BankImport::findOrFail($importId);

        DB::transaction(function () use ($import, $data) {
            $entryId = DB::table('bank_entries')->insertGetId([
                'client_id' => $import->client_id,
                'bank_account_id' => $import->bank_account_id,
                'bank_import_id' => $import->id,
                'currency' => 'GBP',
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'status' => 'needs_review',
                'is_manually_added' => 1,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'original_values' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id' => $entryId,
                'author_id' => auth()->id(),
                'action' => 'manual_insert',
                'reason' => $data['reason'],
                'original_values' => null,
                'corrected_values' => json_encode($data),
                'created_at' => now(),
            ]);

            DB::table('bank_imports')->where('id', $import->id)->increment('transaction_count');
            // Statement edits reopen verification (see updateEntry).
            $this->verification->reopen($import);
            $this->verification->refresh($import);
        });

        return back()->with('success', 'Missing row added and balances recalculated.');
    }

    public function destroyEntry(Request $request, int $entryId)
    {
        $entry = DB::table('bank_entries')->find($entryId);
        abort_unless($entry, 404);
        abort_if(($entry->reconciliation_status ?? 'draft') === 'approved', 409, 'Approved reconciliations cannot be removed.');
        $import = BankImport::findOrFail($entry->bank_import_id);

        DB::transaction(function () use ($entry, $import) {
            DB::table('bank_entry_revisions')->insert([
                'bank_entry_id' => $entry->id,
                'author_id' => auth()->id(),
                'action' => 'manual_deletion',
                'reason' => request('reason', 'Removed false positive extracted line'),
                'original_values' => json_encode($entry),
                'corrected_values' => null,
                'created_at' => now(),
            ]);

            DB::table('bank_entries')->where('id', $entry->id)->delete();
            DB::table('bank_imports')->where('id', $import->id)->decrement('transaction_count');
            // Statement edits reopen verification (see updateEntry).
            $this->verification->reopen($import);
            $this->verification->refresh($import);
        });

        return back()->with('success', 'Row removed from statement.');
    }

    /**
     * Permanently delete an uploaded statement together with everything that
     * was extracted from it (rows, OCR pages, exceptions, revisions, matches,
     * splits, settlements) and its stored PDF.
     *
     * Statements that already carry approved or ledger-posted rows are refused:
     * deleting those would silently remove accounting entries, so the
     * reconciliation has to be reopened/reversed first.
     */
    public function destroyImport(int $importId)
    {
        $import = BankImport::findOrFail($importId);
        $entryIds = DB::table('bank_entries')->where('bank_import_id', $importId)->pluck('id')->all();

        if ($entryIds) {
            $locked = 0;
            if (Schema::hasColumn('bank_entries', 'journal_posted_at')) {
                $locked = DB::table('bank_entries')->whereIn('id', $entryIds)
                    ->where(function ($inner) {
                        $inner->whereNotNull('journal_posted_at')
                            ->orWhere('reconciliation_status', 'approved');
                    })
                    ->count();

                // A posted internal transfer on the other statement points here too;
                // removing this side would leave that journal without its pair.
                $locked += DB::table('bank_entries')->whereIn('linked_bank_entry_id', $entryIds)
                    ->where(function ($q) {
                        $q->whereNotNull('journal_posted_at')->orWhere('reconciliation_status', 'approved');
                    })
                    ->count();
            }

            if ($locked > 0) {
                return back()->with('error', 'This statement contains approved or ledger-posted transactions, so it cannot be deleted. Reopen or reverse those rows in Bank Reconciliation first.');
            }

            if (Schema::hasTable('transactions') && DB::table('transactions')->whereIn('bank_entry_id', $entryIds)->exists()) {
                return back()->with('error', 'This statement still has ledger postings linked to its rows. Reverse them in Bank Reconciliation before deleting the statement.');
            }
        }

        $storagePath = $import->storage_path;

        DB::transaction(function () use ($import, $entryIds) {
            if ($entryIds) {
                // Detach internal-transfer counterparts that live in other statements.
                DB::table('bank_entries')->whereIn('linked_bank_entry_id', $entryIds)
                    ->update(['linked_bank_entry_id' => null, 'updated_at' => now()]);

                // Children before parents (same order as AccountingDataCleaner).
                foreach ([
                    'bank_match_audits',
                    'bank_review_notes',
                    'bank_allocation_lines',
                    'bank_receipt_requests',
                    'bank_settlements',
                    'bank_entry_revisions',
                ] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('bank_entry_id', $entryIds)->delete();
                    }
                }
            }

            foreach (['bank_statement_exceptions', 'bank_statement_pages'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where('bank_import_id', $import->id)->delete();
                }
            }

            DB::table('bank_entries')->where('bank_import_id', $import->id)->delete();
            DB::table('bank_imports')->where('id', $import->id)->delete();
        });

        // File cleanup is best effort: an orphaned PDF is harmless, a failed
        // delete should not roll back the database work.
        if ($storagePath) {
            foreach (['s3', 'local'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($storagePath)) {
                        Storage::disk($disk)->delete($storagePath);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return back()->with('success', 'Bank statement "' . $import->filename . '" and its extracted data were deleted.');
    }

    public function verifyEntry(int $entryId, Request $request)
    {
        $entry = DB::table('bank_entries')->find($entryId);
        abort_unless($entry, 404);
        DB::table('bank_entries')->where('id', $entryId)->update([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'updated_at' => now(),
        ]);
        if ($entry->bank_import_id) {
            $import = BankImport::find($entry->bank_import_id);
            if ($import) {
                $this->verification->refresh($import);
            }
        }
        return back()->with('success', 'Row verified.');
    }

    public function verifyAllRows(Request $request, int $importId)
    {
        $import = BankImport::findOrFail($importId);
        $updated = DB::table('bank_entries')->where('bank_import_id', $importId)
            ->whereNull('verified_at')
            ->update([
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
        $this->verification->refresh($import);
        return back()->with('success', $updated . ' row(s) marked verified.');
    }

    public function resolveException(Request $request, int $exceptionId)
    {
        $data = $request->validate(['resolution_note' => 'required|string|max:1000']);
        $ex = DB::table('bank_statement_exceptions')->find($exceptionId);
        abort_unless($ex, 404);
        DB::table('bank_statement_exceptions')->where('id', $exceptionId)->update([
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_note' => $data['resolution_note'],
            'updated_at' => now(),
        ]);
        $import = BankImport::findOrFail($ex->bank_import_id);
        $this->verification->refresh($import);
        return back()->with('success', 'Exception marked resolved.');
    }

    /** Organised S3 folder, identical to the app upload path. */
    private function getStatementDirectory($client, int $importId): string
    {
        $clientName   = $this->sanitizeDirName($client->name ?? 'unknown_client');
        $businessName = $this->sanitizeDirName($client->business_name ?? $client->name ?? 'unknown_business');
        $year         = now()->format('Y');

        return "images/bank_statements/{$clientName}/{$businessName}/{$year}/{$importId}";
    }

    private function sanitizeDirName(?string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name ?? 'unknown');
    }

    public function approveImport(Request $request, int $importId)
    {
        $data = $request->validate(['client_id' => 'required|integer|exists:clients,id']);
        $client = Client::findOrFail($data['client_id']);
        $import = BankImport::findOrFail($importId);
        if ($import->verification_status !== BankStatementVerificationService::VERIFIED) {
            return back()->with('error', 'Verify this statement before approving its transactions.');
        }

        $approved = 0;
        $skipped = 0;
        $posted = 0;

        DB::transaction(function () use ($client, $importId, &$approved, &$skipped, &$posted) {
            $entries = DB::table('bank_entries')->where('client_id', $client->id)
                ->where('bank_import_id', $importId)->lockForUpdate()->get();

            foreach ($entries as $entry) {
                $isApproved = ($entry->reconciliation_status ?? 'draft') === 'approved';
                if ($isApproved || !in_array($entry->status, ['matched', 'categorized', 'split'])) {
                    $skipped++;
                    continue;
                }
                try {
                    $this->posting->assertReadyForApproval($entry);
                } catch (ValidationException $exception) {
                    $skipped++;
                    continue;
                }
                $change = [
                    'reconciliation_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('bank_entries')->where('id', $entry->id)->update($change);
                $posted += $this->posting->postEntry($entry, auth()->id());
                DB::table('bank_match_audits')->insert([
                    'bank_entry_id' => $entry->id,
                    'actor_id' => auth()->id(),
                    'action' => 'approve',
                    'old_values' => json_encode($entry),
                    'new_values' => json_encode($change),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $approved++;
            }
        });

        return back()->with('success', $approved . ' transaction(s) approved' . ($posted ? ' and ' . $posted . ' ledger entries posted' : '') . '.' . ($skipped ? ' ' . $skipped . ' skipped.' : ''));
    }
}
