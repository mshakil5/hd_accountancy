<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearAccountingDataSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('s3'); // uses .env AWS_BUCKET (hd-ac), region, key/secret
        $bucket = env('AWS_BUCKET', 'hd-ac');

        // 1) Confirm bucket access
        $this->command->info("S3 disk: s3, bucket env: {$bucket}");

        // 2) Real S3 deletions — receipts/ and bank_imports/
        $paths = ['receipts/', 'bank_imports/'];
        foreach ($paths as $path) {
            try {
                $files = $disk->allFiles($path);
                $deleted = 0;
                foreach ($files as $file) {
                    $disk->delete($file);
                    $deleted++;
                    $this->command->info("Deleted S3: {$file}");
                }
                $this->command->info("S3 path '{$path}': {$deleted} files deleted.");
            } catch (\Exception $e) {
                $this->command->error("S3 delete error for '{$path}': " . $e->getMessage());
            }
        }

        // 3) DB deletions (destructive) — related accounting data
        $this->command->info("Deleting DB rows in ms_hd_accountancy...");

        $tables = [
            'bank_match_audits',
            'bank_review_notes',
            'bank_allocation_lines',
            'receipt_details',
            'receipt_files',
            'receipts',
            'transactions', // journal/debit-credit linked
            'bank_entries',
            'bank_imports',
            'reconciliation_bank_accounts',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->command->info("Table '{$table}': {$count} rows truncated.");
            } catch (\Exception $e) {
                $this->command->warn("Table '{$table}': " . $e->getMessage());
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Confirm DB state
        $counts = [
            'receipts' => DB::table('receipts')->count(),
            'bank_entries' => DB::table('bank_entries')->count(),
            'bank_allocation_lines' => DB::table('bank_allocation_lines')->count(),
            'bank_review_notes' => DB::table('bank_review_notes')->count(),
            'bank_match_audits' => DB::table('bank_match_audits')->count(),
        ];
        $this->command->table(['table', 'count_after'], array_map(
            fn($table, $c) => [$table, $c],
            array_keys($counts), $counts
        ));

        $this->command->info("Seeder complete. DB cleared + S3 receipts/ and bank_imports/ deleted.");
    }
}
