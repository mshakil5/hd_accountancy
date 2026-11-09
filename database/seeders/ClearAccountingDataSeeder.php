<?php

namespace Database\Seeders;

use App\Services\AccountingDataCleaner;
use Illuminate\Database\Seeder;

class ClearAccountingDataSeeder extends Seeder
{
    /**
     * Clear receipts, bank statements and reconciliation data (plus their
     * stored files). The chart of accounts is kept.
     *
     * The table list lives in AccountingDataCleaner so this seeder and the
     * admin "Clean Accounting Data" page can never drift apart.
     */
    public function run(): void
    {
        $report = app(AccountingDataCleaner::class)->clean();

        foreach ($report['tables'] as $table => $count) {
            $this->command->info("Table '{$table}': {$count} rows deleted.");
        }

        $this->command->info(
            "Files deleted — S3: {$report['files']['s3']}, "
            . "public: {$report['files']['public']}, "
            . "local: {$report['files']['local']}."
        );

        foreach ($report['errors'] as $error) {
            $this->command->warn($error);
        }

        $this->command->info('Accounting data cleared.');
    }
}
