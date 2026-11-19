<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\BankImport;
use App\Services\BankStatementVerificationService;
use Illuminate\Support\Facades\DB;

/**
 * Server-side rendering for the statement verification workspace.
 *
 * The page and its JSON fragments render from the same Blade partials, so a
 * correction repaints the affected panels from the database instead of
 * reloading the document — the same pattern the bank reconciliation workspace
 * uses (controller renders partial → JS swaps the returned HTML in place).
 */
trait RendersStatementVerification
{
    /** Everything the verification partials render from. */
    protected function verificationWorkspace(BankImport $import): array
    {
        $import->refresh();
        $service = app(BankStatementVerificationService::class);

        $entries = DB::table('bank_entries')->where('bank_import_id', $import->id)
            ->orderByRaw('COALESCE(source_page,1)')->orderByRaw('COALESCE(source_position,0)')->orderBy('id')->get();
        // Display oldest → newest with manually added rows interleaved by date.
        $entries = collect($service->sortChronologically($entries, $import->opening_balance !== null ? (float) $import->opening_balance : null));

        $pages = DB::table('bank_statement_pages')->where('bank_import_id', $import->id)->orderBy('page_no')->get();
        $exceptions = DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')->orderByDesc('id')->get();
        $revisions = DB::table('bank_entry_revisions')->whereIn('bank_entry_id', $entries->pluck('id'))
            ->latest('id')->limit(100)->get();

        $blocking = $service->blockingCount($import);
        $bank = DB::table('reconciliation_bank_accounts')->find($import->bank_account_id);
        $client = $import->client;

        $status = $import->verification_status;
        $statusLabel = match ($status) {
            'ready_for_reconciliation' => 'Ready for reconciliation',
            'needs_accountant_review' => 'Needs accountant review',
            'extraction_failed' => 'Extraction failed',
            'verified' => 'Statement verified',
            'pending' => 'Pending checks',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
        $statusClass = match ($status) {
            'verified' => 'success',
            'ready_for_reconciliation' => 'info',
            'needs_accountant_review' => 'warning',
            'extraction_failed' => 'danger',
            default => 'secondary',
        };

        return [
            'import' => $import,
            'entries' => $entries,
            'pages' => $pages,
            'exceptions' => $exceptions,
            'revisions' => $revisions,
            'blocking' => $blocking,
            'client' => $client,
            'bank' => $bank,
            'bankName' => $bank->bank_name ?? 'Unassigned bank',
            'bankLast' => $bank->account_last_four ?? null,
            'clientName' => $client->name ?? $import->client_id,
            'backUrl' => route('admin.bank-statements.index', array_filter([
                'client_credential_id' => $client->client_credential_id ?? null,
                'client_id' => $import->client_id,
            ])),
            'statusLabel' => $statusLabel,
            'statusClass' => $statusClass,
            'stats' => $this->verificationStats($import, $entries, $blocking),
        ];
    }

    /** The headline numbers, exactly as the panel shows them. */
    protected function verificationStats(BankImport $import, $entries, int $blocking): array
    {
        return [
            'rows' => (int) $entries->count(),
            'opening' => $import->opening_balance !== null ? (float) $import->opening_balance : null,
            'total' => (float) $entries->sum(fn ($entry) => (float) $entry->amount),
            'calculated' => $import->calculated_closing_balance !== null ? (float) $import->calculated_closing_balance : null,
            'closing' => $import->closing_balance !== null ? (float) $import->closing_balance : null,
            'difference' => $import->balance_difference !== null ? (float) $import->balance_difference : null,
            'blocking' => $blocking,
            'status' => $import->verification_status,
            'verified' => $import->verification_status === BankStatementVerificationService::VERIFIED,
        ];
    }

    /**
     * Response shape for the fragment endpoint and for every correction: the
     * re-rendered panels plus the counters, so the browser never reloads.
     */
    protected function verificationPayload(BankImport $import, string $message = '', ?int $entryId = null): array
    {
        $data = $this->verificationWorkspace($import);
        $render = fn (string $partial) => view('admin.accounting.partials.' . $partial, $data)->render();

        return [
            'success' => true,
            'message' => $message,
            'import_id' => $import->id,
            'entry_id' => $entryId,
            'rows_html' => $render('verification_rows'),
            'stats_html' => $render('verification_stats'),
            'status_html' => $render('verification_header_status'),
            'banner_html' => $render('verification_banner'),
            'exceptions_html' => $render('verification_exceptions'),
            'pages_html' => $render('verification_pages'),
            'revisions_html' => $render('verification_revisions'),
            'stats' => $data['stats'],
        ];
    }
}
