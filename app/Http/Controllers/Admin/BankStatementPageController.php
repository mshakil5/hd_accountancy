<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankImport;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Services\BankLedgerPostingService;
use App\Services\BankStatementVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $credentialId  = (int)$request->query('client_credential_id', 0);
        $businessId    = (int)$request->query('client_id', 0);
        $bankAccountId = (int)$request->query('bank_account_id', 0);

        $businessIds = $credentialId
            ? Client::where('client_credential_id', $credentialId)->where('status', 1)->pluck('id')->all()
            : [];

        if ($businessId) {
            $businessIds = in_array($businessId, $businessIds, true) ? [$businessId] : [];
        }

        $bankAccounts = $businessIds
            ? DB::table('reconciliation_bank_accounts')->whereIn('client_id', $businessIds)->orderBy('bank_name')->get()
            : collect();

        if ($bankAccountId && !$bankAccounts->contains('id', $bankAccountId)) {
            abort(422, 'Choose a bank account belonging to the selected business.');
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

        return view('admin.accounting.bank_statements', compact(
            'credentialId',
            'businessId',
            'bankAccountId',
            'bankAccounts',
            'imports',
            'importStats'
        ));
    }



    public function verification(int $importId)
    {
        $import = BankImport::findOrFail($importId);
        $this->verification->refresh($import);
        $import->refresh();

        $entries = DB::table('bank_entries')->where('bank_import_id', $importId)
            ->orderByRaw('COALESCE(source_page,1)')->orderByRaw('COALESCE(source_position,0)')->orderBy('id')->get();
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

    public function streamStatement(int $importId)
    {
        $item = BankImport::findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path), 404);
        return response()->file(Storage::disk('local')->path($item->storage_path), ['Content-Type' => 'application/pdf']);
    }

    public function downloadStatement(int $importId)
    {
        $item = BankImport::findOrFail($importId);
        abort_unless($item->storage_path && Storage::disk('local')->exists($item->storage_path), 404);
        return Storage::disk('local')->download($item->storage_path, basename($item->filename));
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
                'change_type' => 'manual_edit',
                'reason' => $data['reason'],
                'previous_values' => json_encode([
                    'transaction_date' => $entry->transaction_date,
                    'description' => $entry->description,
                    'amount' => $entry->amount,
                ]),
                'new_values' => json_encode([
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
                'change_type' => 'manual_insert',
                'reason' => $data['reason'],
                'previous_values' => null,
                'new_values' => json_encode($data),
                'created_at' => now(),
            ]);

            DB::table('bank_imports')->where('id', $import->id)->increment('transaction_count');
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
                'change_type' => 'manual_deletion',
                'reason' => request('reason', 'Removed false positive extracted line'),
                'previous_values' => json_encode($entry),
                'new_values' => null,
                'created_at' => now(),
            ]);

            DB::table('bank_entries')->where('id', $entry->id)->delete();
            DB::table('bank_imports')->where('id', $import->id)->decrement('transaction_count');
            $this->verification->refresh($import);
        });

        return back()->with('success', 'Row removed from statement.');
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

    public function verifyAllRowsForClient(Request $request)
    {
        $data = $request->validate(['client_id' => 'required|integer|exists:clients,id']);
        $client = Client::findOrFail($data['client_id']);
        $updated = DB::table('bank_entries')->where('client_id', $client->id)
            ->whereNull('verified_at')
            ->update([
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
        $imports = BankImport::where('client_id', $client->id)->get();
        foreach ($imports as $imp) {
            $this->verification->refresh($imp);
        }
        return back()->with('success', $updated . ' row(s) marked verified across all statements for ' . $client->name . '.');
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
