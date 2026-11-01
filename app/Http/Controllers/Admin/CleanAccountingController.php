<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AccountingDataCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CleanAccountingController extends Controller
{
    public function __construct(private AccountingDataCleaner $cleaner)
    {
    }

    /**
     * Confirmation page: shows exactly what will be removed.
     */
    public function index(): View
    {
        return view('admin.clean_accounting.index', [
            'groups' => AccountingDataCleaner::GROUPS,
            'counts' => $this->cleaner->counts(),
        ]);
    }

    /**
     * Perform the clean-up. POST-only so it cannot be triggered by a link,
     * a browser prefetch or a crawler.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:CLEAN'],
        ], [
            'confirm.in' => 'Type CLEAN to confirm.',
        ]);

        $report = $this->cleaner->clean();

        $rows = array_sum($report['tables']);

        $message = "Accounting data cleaned: {$rows} rows removed, "
            . "{$report['files']['s3']} S3 files, {$report['files']['public']} public files and "
            . "{$report['files']['local']} local statement files deleted.";

        if ($report['errors'] !== []) {
            $message .= ' File cleanup warnings: ' . implode(' | ', $report['errors']);
        }

        return redirect()
            ->route('admin.clean-accounting.index')
            ->with('success', $message);
    }
}
