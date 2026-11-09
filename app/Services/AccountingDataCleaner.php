<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Wipes accounting data for a clean slate.
 *
 * Rows are deleted child-first so no foreign key is left pointing at a deleted
 * parent. The chart of accounts (account_heads) and the fixed account_types
 * and account_sub_types masters are kept: they are structural and are
 * re-seeded by the migrations.
 *
 * Everything runs inside one transaction, so a failure leaves the database
 * untouched. Stored files are removed afterwards; an orphaned file is harmless.
 */
class AccountingDataCleaner
{
    /**
     * Authoritative delete order (children before parents).
     */
    public const TABLES = [
        'bank_match_audits',
        'bank_review_notes',
        'bank_allocation_lines',
        'bank_receipt_requests',
        'bank_settlements',
        'bank_statement_exceptions',
        'bank_statement_pages',
        'bank_entry_revisions',
        'receipt_details',
        'receipt_files',
        'transactions',
        'bank_entries',
        'bank_imports',
        'reconciliation_bank_accounts',
        'receipts',
    ];

    /**
     * Display grouping for the confirmation page.
     */
    public const GROUPS = [
        'Receipts' => ['receipts', 'receipt_files', 'receipt_details'],
        'Transactions' => ['transactions'],
        'Bank statements' => [
            'bank_imports',
            'bank_statement_pages',
            'bank_statement_exceptions',
            'bank_entry_revisions',
        ],
        'Bank reconciliation' => [
            'reconciliation_bank_accounts',
            'bank_entries',
            'bank_match_audits',
            'bank_review_notes',
            'bank_allocation_lines',
            'bank_receipt_requests',
            'bank_settlements',
        ],
    ];

    /**
     * Row counts for every table, keyed by table name.
     */
    public function counts(): array
    {
        $counts = [];

        foreach (self::TABLES as $table) {
            $counts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        return $counts;
    }

    /**
     * Delete every accounting row and then the files that belonged to them.
     *
     * @return array{tables: array<string, int>, files: array{s3: int, public: int, local: int}, errors: array<int, string>}
     */
    public function clean(): array
    {
        $report = [
            'tables' => [],
            'files' => ['s3' => 0, 'public' => 0, 'local' => 0],
            'errors' => [],
        ];

        // Paths must be read before their rows are deleted.
        $receiptPaths = Schema::hasTable('receipt_files')
            ? DB::table('receipt_files')->pluck('file_path')->filter()->all()
            : [];

        $statementPaths = Schema::hasTable('bank_imports')
            ? DB::table('bank_imports')->pluck('storage_path')->filter()->all()
            : [];

        DB::transaction(function () use (&$report) {
            foreach (self::TABLES as $table) {
                $report['tables'][$table] = Schema::hasTable($table)
                    ? DB::table($table)->delete()
                    : 0;
            }
        });

        $this->deleteStoredFiles($receiptPaths, $statementPaths, $report);

        return $report;
    }

    /**
     * Best-effort file cleanup: S3 receipt files, public receipt files and
     * locally stored statement PDFs.
     */
    protected function deleteStoredFiles(array $receiptPaths, array $statementPaths, array &$report): void
    {
        try {
            $disk = Storage::disk('s3');

            foreach (array_merge($receiptPaths, $statementPaths) as $path) {
                if ($disk->exists($path) && $disk->delete($path)) {
                    $report['files']['s3']++;
                }
            }

            // Catch files left behind by receipt rows or statements that were already removed.
            foreach (['images/receipts', 'receipts', 'images/bank_statements', 'bank_statements'] as $directory) {
                foreach ($disk->allFiles($directory) as $path) {
                    if ($disk->delete($path)) {
                        $report['files']['s3']++;
                    }
                }
                $disk->deleteDirectory($directory);
            }
        } catch (Throwable $e) {
            $report['errors'][] = 'S3: ' . $e->getMessage();
        }

        // The admin receipt uploader writes to public/images/receipts.
        try {
            foreach ($receiptPaths as $path) {
                $absolute = public_path($path);

                if (File::isFile($absolute)) {
                    File::delete($absolute);
                    $report['files']['public']++;
                }
            }

            $receiptDirectory = public_path('images/receipts');

            if (File::isDirectory($receiptDirectory)) {
                $report['files']['public'] += count(File::allFiles($receiptDirectory));
                File::deleteDirectory($receiptDirectory);
            }
        } catch (Throwable $e) {
            $report['errors'][] = 'Public storage: ' . $e->getMessage();
        }

        try {
            $disk = Storage::disk('local');

            foreach ($statementPaths as $path) {
                if ($disk->exists($path) && $disk->delete($path)) {
                    $report['files']['local']++;
                }
            }

            foreach (['bank-statements', 'bank_imports', 'images/bank_statements', 'bank_statements'] as $directory) {
                foreach ($disk->allFiles($directory) as $path) {
                    if ($disk->delete($path)) {
                        $report['files']['local']++;
                    }
                }
                $disk->deleteDirectory($directory);
            }
        } catch (Throwable $e) {
            $report['errors'][] = 'Local storage: ' . $e->getMessage();
        }
    }
}
