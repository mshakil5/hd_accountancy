<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the Bank Reconciliation workspace.
 *
 * Builds, for one business (client 271 — Mohammed Yunas, Taxi Driver):
 *   - 2 registered bank accounts (HSBC current + Monzo savings)
 *   - Uploaded statement imports (one per bank)
 *   - Receipts with details (so some bank lines can match to them)
 *   - Bank transactions across every reconciliation state:
 *       matched / needs_review / unmatched / split / categorized,
 *       and approval states draft / approved / rejected.
 *
 * Idempotent: re-running deletes the previous demo rows for this client
 * first, so you can seed, wipe, and re-seed freely during testing.
 *
 * Run with:  php artisan db:seed --class=BankReconciliationDemoSeeder
 */
class BankReconciliationDemoSeeder extends Seeder
{
    /**
     * Business we attach all demo data to.
     * Override at run time with:  php artisan db:seed --class=BankReconciliationDemoSeeder --client=487
     */
    private int $clientId = 271;

    /** Global account heads (from ChartOfAccountsExcelSeeder) we reuse. */
    private int $headIncome      = 29;  // 101 Sales revenue
    private int $headMotor       = 57;  // 316 Motor Expenses
    private int $headInsurance   = 52;  // 311 Insurance
    private int $headRepair      = 63;  // 322 Repair & Maintenance
    private int $headTravel      = 68;  // 327 Travel & Subsistence
    private int $headBankCharges = 44;  // 303 Bank Charges
    private int $headTelephone   = 66;  // 325 Telephone & Internet

    public function run(): void
    {
        // Override target business with:  BANK_DEMO_CLIENT=487 php artisan db:seed --class=BankReconciliationDemoSeeder
        if ($env = env('BANK_DEMO_CLIENT')) {
            $this->clientId = (int) $env;
        }
        $clientId = $this->clientId;
        $now = now();

        // ---- Clean any previous demo rows for this client (idempotent) ----
        $entryIds = DB::table('bank_entries')->where('client_id', $clientId)->pluck('id');
        DB::table('bank_allocation_lines')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_review_notes')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_settlements')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_match_audits')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('transactions')->whereIn('bank_entry_id', $entryIds)->delete();
        DB::table('bank_entries')->where('client_id', $clientId)->delete();
        DB::table('bank_imports')->where('client_id', $clientId)->delete();
        DB::table('reconciliation_bank_accounts')->where('client_id', $clientId)->delete();
        // Demo receipts are tagged with supplier 'DEMO-' so they are easy to spot/remove.
        $demoReceiptIds = DB::table('receipts')
            ->where('client_id', $clientId)
            ->where('supplier', 'like', 'DEMO-%')
            ->pluck('id');
        DB::table('receipt_details')->whereIn('receipt_id', $demoReceiptIds)->delete();
        DB::table('receipts')->whereIn('id', $demoReceiptIds)->delete();

        // ---- 1. Bank accounts ----
        $hsbc = DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $clientId, 'bank_name' => 'HSBC',
            'account_label' => 'Business Current', 'account_last_four' => '1234',
            'currency' => 'GBP', 'active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $monzo = DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $clientId, 'bank_name' => 'Monzo',
            'account_label' => 'Tax Savings', 'account_last_four' => '9876',
            'currency' => 'GBP', 'active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // ---- 2. Receipts (so matched bank lines have something real to link to) ----
        $receipts = [
            // [supplier,           net,    vat,    total,  head,               invoice_date, method, paid]
            ['DEMO-Shell Fuel',    90.00,  18.00, 108.00, $this->headMotor,    '2026-09-03', 'card', 1],
            ['DEMO-Kwik Fit',      240.00, 48.00, 288.00, $this->headRepair,   '2026-09-08', 'card', 1],
            ['DEMO-Admiral Ins',   310.00, 0.00,  310.00, $this->headInsurance,'2026-09-12', 'bank', 1],
            ['DEMO-Trainline',      42.50,  8.50,  51.00, $this->headTravel,   '2026-09-18', 'card', 1],
            ['DEMO-BP Fuel',        65.00, 13.00,  78.00, $this->headMotor,    '2026-09-22', 'card', 1],
        ];
        $receiptIds = [];
        foreach ($receipts as $i => $r) {
            [$supplier, $net, $vat, $total, $head, $date, $method, $paid] = $r;
            $rid = DB::table('receipts')->insertGetId([
                'client_id' => $clientId,
                'receipt_number' => 'RCT-DEMO-' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT),
                'receipt_date' => $date, 'supplier' => $supplier,
                'notes' => 'Demo receipt for bank reconciliation testing.',
                'status' => 'ready', 'created_by' => 1, 'updated_by' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('receipt_details')->insert([
                'receipt_id' => $rid, 'account_head_id' => $head,
                'invoice_date' => $date, 'invoice_number' => 'INV-' . ($i + 101),
                'net_amount' => $net, 'tax_amount' => $vat, 'vat_amount' => 0.00,
                'total_amount' => $total, 'paid' => $paid, 'payment_method' => $method,
                'description' => $supplier, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $receiptIds[] = $rid;
        }

        // ---- 2b. UNMATCHED receipts whose amounts match open bank rows ----
        // These are deliberately NOT linked to any bank entry, so the "Match" tab
        // shows real suggestions and you can test the Match -> Approve flow.
        // [supplier, total, head, invoice_date, bank row it matches]
        $suggestible = [
            ['DEMO-Tesco Express',   23.40, $this->headMotor,    '2026-09-14'], // TESCO EXPRESS 2210
            ['DEMO-Vodafone Mobile', 41.00, $this->headTelephone,'2026-09-05'], // DD VODAFONE MOBILE
            ['DEMO-Uber Payout',    412.00, $this->headIncome,   '2026-09-01'], // UBER PAYOUT WEEK 35
            ['DEMO-Stripe Payout',  940.00, $this->headIncome,   '2026-09-02'], // STRIPE PAYOUT SEP
            ['DEMO-Bolt Payout',    268.50, $this->headIncome,   '2026-09-29'], // BOLT PAYOUT WEEK 39
        ];
        foreach ($suggestible as $i => $s) {
            [$supplier, $total, $head, $date] = $s;
            $rid = DB::table('receipts')->insertGetId([
                'client_id' => $clientId,
                'receipt_number' => 'RCT-DEMO-S' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT),
                'receipt_date' => $date, 'supplier' => $supplier,
                'notes' => 'Demo suggestion receipt (unmatched) for bank reconciliation testing.',
                'status' => 'ready', 'created_by' => 1, 'updated_by' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('receipt_details')->insert([
                'receipt_id' => $rid, 'account_head_id' => $head,
                'invoice_date' => $date, 'invoice_number' => 'INV-S' . ($i + 1),
                'net_amount' => $total, 'tax_amount' => 0.00, 'vat_amount' => 0.00,
                'total_amount' => $total, 'paid' => 1, 'payment_method' => 'card',
                'description' => $supplier, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- 3. Statement imports (one uploaded file per bank) ----
        $importHsbc = DB::table('bank_imports')->insertGetId([
            'client_id' => $clientId, 'bank_account_id' => $hsbc,
            'filename' => 'hsbc_statement_sep2026.csv', 'sha256' => sha1('hsbc-demo-' . $clientId),
            'created_by' => 1, 'transaction_count' => 10,
            'file_type' => 'csv', 'processing_status' => 'parsed',
            'storage_path' => null, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $importMonzo = DB::table('bank_imports')->insertGetId([
            'client_id' => $clientId, 'bank_account_id' => $monzo,
            'filename' => 'monzo_statement_sep2026.csv', 'sha256' => sha1('monzo-demo-' . $clientId),
            'created_by' => 1, 'transaction_count' => 4,
            'file_type' => 'csv', 'processing_status' => 'parsed',
            'storage_path' => null, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // ---- 4. Bank transactions across every state ----
        // Each: bank_account, import, date, description, reference, amount, status,
        //       reconciliation_status, receipt_id (nullable), head (nullable)
        $entries = [
            // --- HSBC: matched to the receipts above (approved) ---
            [$hsbc, $importHsbc, '2026-09-03', 'SHELL OIL 4471 LEEDS',      'CARD 108.00',  -108.00, 'matched',       'approved', $receiptIds[0], $this->headMotor],
            [$hsbc, $importHsbc, '2026-09-08', 'KWIK FIT LEEDS KIRKSTALL',  'CARD 288.00',  -288.00, 'matched',       'approved', $receiptIds[1], $this->headRepair],
            [$hsbc, $importHsbc, '2026-09-12', 'ADMIRAL INSURANCE DD',      'DD 310.00',    -310.00, 'matched',       'approved', $receiptIds[2], $this->headInsurance],

            // --- HSBC: matched but still pending approval (draft) ---
            [$hsbc, $importHsbc, '2026-09-18', 'TRAINLINE TICKET',          'CARD 51.00',    -51.00, 'matched',       'draft',   $receiptIds[3], $this->headTravel],
            [$hsbc, $importHsbc, '2026-09-22', 'BP PETROLEUM 8821',         'CARD 78.00',    -78.00, 'matched',       'draft',   $receiptIds[4], $this->headMotor],

            // --- HSBC: needs_review (fuzzy-matched candidate, awaiting accountant) ---
            [$hsbc, $importHsbc, '2026-09-14', 'TESCO EXPRESS 2210',        'CARD 23.40',    -23.40, 'needs_review',  'draft',   null, null],

            // --- HSBC: unmatched (money out, no receipt yet) ---
            [$hsbc, $importHsbc, '2026-09-05', 'DD VODAFONE MOBILE',        'DD 41.00',      -41.00, 'unmatched',     'draft',   null, $this->headTelephone],
            [$hsbc, $importHsbc, '2026-09-09', 'HSBC PACKAGING FEE',        'BANK CHG',       -4.50, 'unmatched',     'draft',   null, $this->headBankCharges],

            // --- HSBC: income (money in, categorized) ---
            [$hsbc, $importHsbc, '2026-09-01', 'UBER PAYOUT WEEK 35',       'FPS IN 412.00', 412.00, 'categorized',   'draft',   null, $this->headIncome],
            [$hsbc, $importHsbc, '2026-09-29', 'BOLT PAYOUT WEEK 39',       'FPS IN 268.50', 268.50, 'categorized',   'draft',   null, $this->headIncome],

            // --- Monzo: one rejected, one unmatched, two categorized ---
            [$monzo, $importMonzo, '2026-09-02', 'STRIPE PAYOUT SEP',        'FPS IN 940.00', 940.00, 'categorized',   'draft',   null, $this->headIncome],
            [$monzo, $importMonzo, '2026-09-11', 'UNKNOWN TRANSFER 55',      'FPS OUT 150.00', -150.00, 'needs_review', 'rejected', null, null],
            [$monzo, $importMonzo, '2026-09-16', 'MONZO 3.25 AER INTEREST',  'INT 3.25',        3.25, 'categorized',   'draft',   null, $this->headIncome],
            [$monzo, $importMonzo, '2026-09-25', 'CASH WITHDRAWAL ATM',      'ATM 60.00',      -60.00, 'unmatched',     'draft',   null, null],
        ];

        foreach ($entries as $e) {
            [$acct, $imp, $date, $desc, $ref, $amt, $status, $approval, $rid, $head] = $e;
            $change = [
                'client_id' => $clientId, 'bank_account_id' => $acct, 'bank_import_id' => $imp,
                'transaction_date' => $date, 'description' => $desc, 'reference' => $ref,
                'amount' => $amt, 'currency' => 'GBP', 'status' => $status,
                'reconciliation_status' => $approval,
                'receipt_id' => $rid, 'account_head_id' => $head,
                'matched_by' => $rid ? 1 : null, 'matched_at' => $rid ? $now : null,
                'created_at' => $now, 'updated_at' => $now,
            ];
            if ($approval === 'approved') {
                $change['approved_by'] = 1;
                $change['approved_at'] = $now;
            }
            if ($approval === 'rejected') {
                $change['review_note'] = 'Demo: could not identify this transfer — returned for review.';
            }
            DB::table('bank_entries')->insert($change);
        }

        $this->command?->info("Demo seeded for client {$clientId}: 2 banks, "
            . count($receipts) . ' receipts, 2 statements, ' . count($entries) . ' transactions.');
    }
}
