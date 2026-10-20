<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BankReconciliationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * TEMPORARY: end-to-end upload of real Monzo statements from files/.
 *
 * 1. digital statement  -> ready for reconciliation, rows reconcile to the PDF
 * 2. same statement to a SECOND bank account -> must not violate the unique index
 * 3. rasterised (image-only) statement -> OCR path, honest review status
 *
 * Everything runs inside a transaction that is rolled back in tearDown().
 */
class TempRealMonzoE2ETest extends TestCase
{
    private $client;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = DB::table('clients')->where('status', true)->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->first();
        $this->assertNotNull($this->client, 'no client fixture');
        $this->userId = (int) (DB::table('users')->value('id') ?? 0);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function makeBankAccount(): int
    {
        return (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id, 'bank_name' => 'Monzo', 'account_label' => 'Business',
            'account_last_four' => (string) rand(1000, 9999), 'currency' => 'GBP', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function upload(string $path, string $name, int $bankAccountId)
    {
        $request = Request::create('/api/businesses/' . $this->client->id . '/bank-imports', 'POST', [
            'bank_account_id' => $bankAccountId,
        ], [], ['file' => new UploadedFile($path, $name, 'application/pdf', null, true)]);
        $request->setUserResolver(fn () => User::find($this->userId));
        return app(BankReconciliationController::class)->upload($request, (int) $this->client->id);
    }

    public function test_digital_statement_is_ready(): void
    {
        $bankId = $this->makeBankAccount();
        $source = base_path('../files/documents_for_25-26_Tax_return/Monzo_bank_statement_2025-04-01-2025-04-30_5452.pdf');
        $this->assertFileExists($source);

        $response = $this->upload($source, 'monzo-apr.pdf', $bankId);
        $this->assertSame(201, $response->getStatusCode());

        $import = DB::table('bank_imports')->where('client_id', $this->client->id)
            ->where('filename', 'monzo-apr.pdf')->first();
        $rows = DB::table('bank_entries')->where('bank_import_id', $import->id)->count();
        $blocking = DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->whereNull('resolved_at')->where('severity', 'blocking')->count();
        $warnings = DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->whereNull('resolved_at')->where('severity', 'warning')->count();

        fwrite(STDERR, "\n=== DIGITAL MONZO ===\nstatus={$import->verification_status} rows={$rows} blocking={$blocking} warnings={$warnings}\n"
            . "opening={$import->opening_balance} calculated={$import->calculated_closing_balance} closing={$import->closing_balance} difference={$import->balance_difference}\n=== END ===\n");

        $this->assertSame('ready_for_reconciliation', $import->verification_status);
        $this->assertSame(73, $rows);
        $this->assertSame(0, $blocking);
        $this->assertSame(0, $warnings);
        $this->assertSame('0.00', number_format((float) $import->balance_difference, 2, '.', ''));
    }

    public function test_same_statement_to_a_second_bank_account_does_not_violate_the_index(): void
    {
        $first = $this->makeBankAccount();
        $second = $this->makeBankAccount();
        $source = base_path('../files/documents_for_25-26_Tax_return/Monzo_bank_statement_2025-04-01-2025-04-30_5452.pdf');

        $this->upload($source, 'monzo-a.pdf', $first);

        // Same statement, different bank account of the same client.
        $response = $this->upload($source, 'monzo-b.pdf', $second);
        $this->assertSame(201, $response->getStatusCode());

        $import = DB::table('bank_imports')->where('client_id', $this->client->id)
            ->where('filename', 'monzo-b.pdf')->first();
        $rows = DB::table('bank_entries')->where('bank_import_id', $import->id)->count();

        fwrite(STDERR, "\n=== SECOND ACCOUNT ===\nstatus={$import->verification_status} rows={$rows}\n=== END ===\n");

        $this->assertSame(73, $rows);
    }

    public function test_scanned_statement_is_reviewed_not_ready(): void
    {
        $bankId = $this->makeBankAccount();
        $source = sys_get_temp_dir() . '/monzo-scan.pdf';
        $this->assertFileExists($source);

        $response = $this->upload($source, 'monzo-scan.pdf', $bankId);
        $this->assertSame(201, $response->getStatusCode());

        $import = DB::table('bank_imports')->where('client_id', $this->client->id)
            ->where('filename', 'monzo-scan.pdf')->first();
        $rows = DB::table('bank_entries')->where('bank_import_id', $import->id)->count();
        $warnings = DB::table('bank_statement_exceptions')->where('bank_import_id', $import->id)
            ->whereNull('resolved_at')->where('severity', 'warning')->count();
        $pages = DB::table('bank_statement_pages')->where('bank_import_id', $import->id)->count();

        fwrite(STDERR, "\n=== SCANNED MONZO ===\nstatus={$import->verification_status} rows={$rows} warnings={$warnings} pages={$pages}\n=== END ===\n");

        $this->assertNotSame('ready_for_reconciliation', $import->verification_status);
        $this->assertSame(9, $pages);
        $this->assertGreaterThan(0, $warnings);
    }
}
