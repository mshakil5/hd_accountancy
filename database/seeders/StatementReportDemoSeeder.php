<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the statement-based Profit & Loss and Balance Sheet.
 *
 * Creates one self-contained business ("Demo Trading Ltd") with:
 *   - 2 bank accounts and verified statement imports
 *   - receipts with a real net/VAT/gross split
 *   - bank statement lines reconciled to those receipts (approved)
 *   - a partial application (one receipt used on two bank lines)
 *   - a split line (one bank line across two heads)
 *   - deliberately excluded rows (categorized-only, internal transfer, draft)
 *     so you can see they never reach the reports
 *
 * Data is spread over the last 12 months and the 12 months before that, so
 * both report columns (current + prior year) have figures.
 *
 * Idempotent: re-running wipes this demo business's rows first.
 *
 * Run with:  php artisan db:seed --class=StatementReportDemoSeeder
 */
class StatementReportDemoSeeder extends Seeder
{
    private int $clientId = 0;
    private int $credentialId = 0;
    private int $userId = 0;
    private int $hsbc = 0;
    private int $barclays = 0;
    private int $importHsbc = 0;
    private int $importBarclays = 0;
    private array $heads = [];
    private int $seq = 0;

    public function run(): void
    {
        $now = now();
        $this->userId = (int) (DB::table('users')->value('id') ?? 0);

        // ---- 1. The demo business (client credential + client) ----
        $this->credentialId = (int) (DB::table('client_credentials')
            ->where('email', 'demo@hd.local')
            ->value('id') ?? 0);

        if (!$this->credentialId) {
            $this->credentialId = (int) DB::table('client_credentials')->insertGetId([
                'email' => 'demo@hd.local',
                'password' => bcrypt('secret'),
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->clientId = (int) (DB::table('clients')
            ->where('client_credential_id', $this->credentialId)
            ->value('id') ?? 0);

        if (!$this->clientId) {
            $this->clientId = (int) DB::table('clients')->insertGetId([
                'client_credential_id' => $this->credentialId,
                'name' => 'Demo Trading',
                'last_name' => 'Ltd',
                'business_name' => 'Demo Trading Ltd',
                'email' => 'demo@hd.local',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->wipe();

        // ---- 2. Bank accounts + verified imports ----
        $this->hsbc = (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->clientId,
            'bank_name' => 'HSBC',
            'account_label' => 'Business Current',
            'account_last_four' => '1234',
            'currency' => 'GBP',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->barclays = (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->clientId,
            'bank_name' => 'Barclays',
            'account_label' => 'Savings',
            'account_last_four' => '5678',
            'currency' => 'GBP',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->importHsbc = $this->makeImport($this->hsbc, 'hsbc-demo.csv');
        $this->importBarclays = $this->makeImport($this->barclays, 'barclays-demo.csv');

        // ---- 3. Reconciled activity, current year and prior year ----
        // [monthsAgo, description, head code, gross, vat %, direction, method]
        $events = [
            // Revenue (money in)
            [1,  'Invoice payment — Acme Ltd',         '101', 12000.00, 20, 'in',  'bank'],
            [2,  'Invoice payment — Beta Services',    '101',  8500.00, 20, 'in',  'bank'],
            [3,  'Cash sales banked',                  '101',  3200.00,  0, 'in',  'cash'],
            [4,  'Consultancy fee — Delta Group',      '102',  4000.00, 20, 'in',  'bank'],
            [6,  'Invoice payment — Acme Ltd',         '101',  9600.00, 20, 'in',  'bank'],
            [8,  'Rental income received',             '105',  1800.00,  0, 'in',  'bank'],
            [10, 'Invoice payment — Omega Ltd',        '101',  7400.00, 20, 'in',  'bank'],

            // Cost of sales (money out)
            [1,  'Stock purchase — Booker',            '201',  6000.00, 20, 'out', 'bank'],
            [3,  'Stock purchase — Bidfood',           '201',  4200.00, 20, 'out', 'bank'],
            [5,  'Direct wages',                       '202',  4500.00,  0, 'out', 'bank'],
            [7,  'Subcontractor — J Smith',            '203',  2200.00, 20, 'out', 'bank'],
            [9,  'Stock purchase — Booker',            '201',  5100.00, 20, 'out', 'bank'],

            // Administrative costs (money out)
            [1,  'Accountancy fees',                   '301',  1200.00, 20, 'out', 'bank'],
            [2,  'Business insurance',                 '311',   900.00,  0, 'out', 'bank'],
            [2,  'Rent — Unit 4',                      '321',  2400.00, 20, 'out', 'bank'],
            [3,  'Telephone & internet',               '325',   180.00, 20, 'out', 'bank'],
            [4,  'Motor expenses — fuel',              '316',   450.00, 20, 'out', 'bank'],
            [4,  'Bank charges',                       '303',    35.00,  0, 'out', 'bank'],
            [5,  'IT software subscription',           '313',   240.00, 20, 'out', 'bank'],
            [6,  'Cleaning contract',                  '304',   300.00, 20, 'out', 'bank'],
            [7,  'Advertising — local paper',          '302',   650.00, 20, 'out', 'bank'],
            [8,  'Light, heat & power',                '315',   780.00, 20, 'out', 'bank'],
            [9,  'Printing & stationery',              '319',   160.00, 20, 'out', 'bank'],
            [10, 'Repairs & maintenance',              '322',   520.00, 20, 'out', 'bank'],
            [11, 'Water',                              '328',   210.00,  0, 'out', 'bank'],

            // Fixed assets (money out)
            [2,  'Office equipment — desks',           '402',  1800.00, 20, 'out', 'bank'],
            [6,  'Computer equipment — laptops',       '403',  2400.00, 20, 'out', 'bank'],

            // Liabilities / capital
            [1,  'Director loan introduced',           '509',  5000.00,  0, 'in',  'bank'],
            [3,  'Bank loan received',                 '551', 10000.00,  0, 'in',  'bank'],
            [5,  'Owner funds introduced',             '603',  7500.00,  0, 'in',  'bank'],
            [8,  'Owner drawings',                     '604',  1500.00,  0, 'out', 'bank'],
        ];

        foreach ($events as $event) {
            [$monthsAgo, $description, $code, $gross, $vatPercent, $direction, $method] = $event;
            $this->matchedLine($monthsAgo, $description, $code, $gross, $vatPercent, $direction, $method);
        }

        // Same activity a year earlier, so the comparative column is populated.
        foreach ($events as $event) {
            [$monthsAgo, $description, $code, $gross, $vatPercent, $direction, $method] = $event;
            $this->matchedLine(
                $monthsAgo + 12,
                $description . ' (prior year)',
                $code,
                round($gross * 0.85, 2),
                $vatPercent,
                $direction,
                $method
            );
        }

        // ---- 4. Partial application: one receipt used on two bank lines ----
        $partialReceipt = $this->receipt(1000.00, 20, '201', 'Part-payment supplier', 2);
        $lineA = $this->bankLine(2, 'Part payment — supplier (1 of 2)', -400.00, 'split');
        $lineB = $this->bankLine(2, 'Part payment — supplier (2 of 2)', -600.00, 'split');
        $this->allocate($lineA, $partialReceipt, '201', 400.00, 20);
        $this->allocate($lineB, $partialReceipt, '201', 600.00, 20);

        // ---- 5. Split line: one bank line across two heads ----
        $splitLine = $this->bankLine(3, 'Combined payment — fees and cleaning', -500.00, 'split');
        $feeReceipt = $this->receipt(300.00, 20, '301', 'Accountancy fees (split)', 3);
        $cleanReceipt = $this->receipt(200.00, 20, '304', 'Cleaning (split)', 3);
        $this->allocate($splitLine, $feeReceipt, '301', 300.00, 20);
        $this->allocate($splitLine, $cleanReceipt, '304', 200.00, 20);

        // ---- 6. Rows that must never reach the reports ----
        // Categorized only (no receipt).
        $categorized = $this->bankLine(1, 'Categorized only — no receipt', -250.00, 'categorized');
        DB::table('bank_entries')->where('id', $categorized)->update([
            'account_head_id' => $this->head('316'),
        ]);

        // Internal transfer pair.
        $out = $this->bankLine(1, 'Transfer to savings', -2000.00, 'split', $this->hsbc);
        $in = $this->bankLine(1, 'Transfer from current', 2000.00, 'split', $this->barclays);
        DB::table('bank_entries')->where('id', $out)->update([
            'transfer_type' => 'internal_transfer',
            'linked_bank_entry_id' => $in,
        ]);
        DB::table('bank_entries')->where('id', $in)->update([
            'transfer_type' => 'internal_transfer',
            'linked_bank_entry_id' => $out,
        ]);

        // Matched but still waiting for approval.
        $draftReceipt = $this->receipt(700.00, 20, '311', 'Draft — not approved', 1);
        $draft = $this->bankLine(1, 'Matched but not approved', -700.00, 'matched');
        DB::table('bank_entries')->where('id', $draft)->update([
            'receipt_id' => $draftReceipt,
            'account_head_id' => $this->head('311'),
            'reconciliation_status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->command?->info('Statement report demo seeded for "Demo Trading Ltd" (client #' . $this->clientId . ').');
        $this->command?->info('Open /admin/accounting/profit-loss, pick client "Demo Client" → business "Demo Trading Ltd".');
    }

    /** Remove this demo business's rows so the seeder can be re-run. */
    private function wipe(): void
    {
        $entryIds = DB::table('bank_entries')->where('client_id', $this->clientId)->pluck('id');
        DB::table('bank_allocation_lines')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_review_notes')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_match_audits')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('transactions')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_entries')->where('client_id', $this->clientId)->delete();
        DB::table('bank_imports')->where('client_id', $this->clientId)->delete();
        DB::table('reconciliation_bank_accounts')->where('client_id', $this->clientId)->delete();

        $receiptIds = DB::table('receipts')->where('client_id', $this->clientId)->pluck('id');
        DB::table('receipt_details')->whereIn('receipt_id', $receiptIds)->delete();
        DB::table('receipts')->whereIn('id', $receiptIds)->delete();
    }

    private function head(string $code): int
    {
        if (!isset($this->heads[$code])) {
            $id = DB::table('account_heads')
                ->where('code', $code)
                ->whereNull('client_credential_id')
                ->value('id');
            if (!$id) {
                throw new \RuntimeException('Missing chart head ' . $code . '. Run ChartOfAccountsExcelSeeder first.');
            }
            $this->heads[$code] = (int) $id;
        }
        return $this->heads[$code];
    }

    private function makeImport(int $bankAccountId, string $filename): int
    {
        return (int) DB::table('bank_imports')->insertGetId([
            'client_id' => $this->clientId,
            'bank_account_id' => $bankAccountId,
            'filename' => $filename,
            'sha256' => hash('sha256', $filename . $this->clientId . microtime()),
            'created_by' => $this->userId,
            'transaction_count' => 0,
            'file_type' => 'csv',
            'processing_status' => 'parsed',
            'verification_status' => 'verified',
            'verified_by' => $this->userId,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** A receipt with a real net/VAT/gross split. */
    private function receipt(float $gross, float $vatPercent, string $headCode, string $supplier, int $monthsAgo, string $method = 'bank'): int
    {
        $date = now()->subMonths($monthsAgo)->startOfMonth()->addDays(4)->toDateString();
        $net = round($gross / (1 + $vatPercent / 100), 2);
        $vat = round($gross - $net, 2);

        $id = (int) DB::table('receipts')->insertGetId([
            'client_id' => $this->clientId,
            'receipt_number' => 'DEMO-' . now()->format('Ymd') . '-' . str_pad((string) (++$this->seq), 4, '0', STR_PAD_LEFT),
            'receipt_date' => $date,
            'supplier' => $supplier,
            'status' => 'ready',
            'from_reconciliation' => true,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('receipt_details')->insert([
            'receipt_id' => $id,
            'account_head_id' => $this->head($headCode),
            'invoice_date' => $date,
            'net_amount' => $net,
            'vat_amount' => $vat,
            'tax_amount' => 0,
            'total_amount' => $gross,
            'paid' => 1,
            'payment_method' => $method,
            'description' => $supplier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /** A bank statement line. Negative = money out. */
    private function bankLine(int $monthsAgo, string $description, float $amount, string $status, ?int $bankAccountId = null): int
    {
        $bankAccountId = $bankAccountId ?: $this->hsbc;
        $date = now()->subMonths($monthsAgo)->startOfMonth()->addDays(6)->toDateString();
        $fingerprint = hash('sha256', $description . $amount . $date . (++$this->seq));

        return (int) DB::table('bank_entries')->insertGetId([
            'client_id' => $this->clientId,
            'bank_import_id' => $bankAccountId === $this->barclays ? $this->importBarclays : $this->importHsbc,
            'bank_account_id' => $bankAccountId,
            'transaction_date' => $date,
            'description' => $description,
            'reference' => 'DEMO' . str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT),
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'GBP',
            'status' => $status,
            'reconciliation_status' => 'approved',
            'approved_by' => $this->userId,
            'approved_at' => now(),
            'line_fingerprint' => $fingerprint,
            'line_occurrence' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** A bank line reconciled to a receipt (the only shape the reports read). */
    private function matchedLine(int $monthsAgo, string $description, string $headCode, float $gross, float $vatPercent, string $direction, string $method): void
    {
        $receiptId = $this->receipt($gross, $vatPercent, $headCode, $description, $monthsAgo, $method);
        $amount = $direction === 'in' ? $gross : -$gross;

        $entryId = $this->bankLine($monthsAgo, $description, $amount, 'matched');

        DB::table('bank_entries')->where('id', $entryId)->update([
            'receipt_id' => $receiptId,
            'account_head_id' => $this->head($headCode),
            'matched_by' => $this->userId,
            'matched_at' => now(),
        ]);
    }

    private function allocate(int $entryId, int $receiptId, string $headCode, float $amount, float $vatPercent): void
    {
        $net = round($amount / (1 + $vatPercent / 100), 2);
        $vat = round($amount - $net, 2);

        DB::table('bank_allocation_lines')->insert([
            'bank_entry_id' => $entryId,
            'account_head_id' => $this->head($headCode),
            'receipt_id' => $receiptId,
            'amount' => number_format($amount, 2, '.', ''),
            'tax_percent' => $vatPercent,
            'tax_amount' => 0,
            'vat_amount' => number_format($vat, 2, '.', ''),
            'memo' => 'Demo allocation',
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
