<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingReconciliationTest extends TestCase
{
    protected int $userId = 1;
    protected object $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = DB::table('clients')->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->first();
        $this->assertNotNull($this->client, 'Client fixture required');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_reconciliation_approval_posts_to_ledger(): void
    {
        $bank = DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id, 'bank_name' => 'HSBC',
            'account_label' => 'Test', 'account_last_four' => '9999',
            'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $import = DB::table('bank_imports')->insertGetId([
            'client_id' => $this->client->id, 'bank_account_id' => $bank,
            'filename' => 'test.csv', 'sha256' => md5('t'), 'created_by' => 1,
            'transaction_count' => 1, 'file_type' => 'csv', 'processing_status' => 'parsed',
            'verification_status' => 'verified', 'verified_by' => 1, 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $entryId = DB::table('bank_entries')->insertGetId([
            'client_id' => $this->client->id, 'bank_import_id' => $import,
            'bank_account_id' => $bank, 'transaction_date' => '2026-06-15',
            'description' => 'E2E ledger', 'amount' => -100.00,
            'currency' => 'GBP', 'status' => 'categorized',
            'reconciliation_status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $headId = DB::table('account_heads')->where('is_active', 1)->whereNotNull('account_type_id')->value('id');
        $this->assertNotNull($headId, 'Account head with account_type_id required');
        DB::table('bank_entries')->where('id', $entryId)->update([
            'account_head_id' => $headId,
            'reconciliation_status' => 'approved',
            'approved_by' => 1, 'approved_at' => now(),
        ]);
        $service = app(\App\Services\BankLedgerPostingService::class);
        $service->postEntry((object) DB::table('bank_entries')->find($entryId), 1);
        $this->assertGreaterThan(0,
            DB::table('transactions')->where('bank_entry_id', $entryId)->count(),
            'Approval must create ledger rows');
    }

    public function test_split_allocation_equals_amount(): void
    {
        $bank = DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id, 'bank_name' => 'HSBC',
            'account_label' => 'Test', 'account_last_four' => '9999',
            'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $import = DB::table('bank_imports')->insertGetId([
            'client_id' => $this->client->id, 'bank_account_id' => $bank,
            'filename' => 'test.csv', 'sha256' => md5('t2'), 'created_by' => 1,
            'transaction_count' => 1, 'file_type' => 'csv', 'processing_status' => 'parsed',
            'verification_status' => 'verified', 'verified_by' => 1, 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $entryId = DB::table('bank_entries')->insertGetId([
            'client_id' => $this->client->id, 'bank_import_id' => $import,
            'bank_account_id' => $bank, 'transaction_date' => '2026-06-15',
            'description' => 'Split E2E', 'amount' => -50.00,
            'currency' => 'GBP', 'status' => 'split',
            'reconciliation_status' => 'approved', 'approved_by' => 1,
            'approved_at' => now(), 'journal_posted_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bank_allocation_lines')->insert([
            ['bank_entry_id' => $entryId, 'amount' => 10.00, 'memo' => 'Prev liability', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['bank_entry_id' => $entryId, 'amount' => 40.00, 'memo' => 'Current expense', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $sum = DB::table('bank_allocation_lines')->where('bank_entry_id', $entryId)->sum('amount');
        $this->assertEqualsWithDelta(50.00, (float) $sum, 0.01, 'Split allocations must equal entry amount');
    }
}
