<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BankReconciliationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The upload / replace pipeline the mobile app depends on.
 *
 * These tests exist because the controller used to upload the PDF but never
 * parse it: `bank_imports.transaction_count` stayed 0 and the app's statement
 * screen had no rows to show. The PDFs here are generated in-memory (real
 * statements contain customer data) and contain a readable ledger so the whole
 * pipeline â€” extract â†’ store â†’ verify â€” can be asserted end to end.
 */
class BankStatementUploadTest extends TestCase
{
    protected object $client;
    protected int $userId;
    protected int $bankId;

    /** @var array<int,string> Files written during the test (not rolled back with the DB). */
    protected array $storedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('bank_imports')
            || !Schema::hasColumn('bank_imports', 'verification_status')
            || !Schema::hasColumn('bank_entries', 'source_page')) {
            $this->markTestSkipped('Run `php artisan migrate` to enable bank statement upload tests.');
        }

        $this->client = DB::table('clients')->where('status', true)->whereNotNull('client_credential_id')->first()
            ?: DB::table('clients')->where('status', true)->first();
        if (!$this->client) {
            $this->markTestSkipped('No client fixture available.');
        }

        DB::beginTransaction();

        $this->userId = (int) (DB::table('users')->value('id') ?? 0);
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $this->actingAs($user);
            }
        }

        $this->bankId = $this->makeBank('HSBC', '4321');
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        foreach ($this->storedFiles as $path) {
            Storage::disk('local')->delete($path);
        }

        parent::tearDown();
    }

    // --------------------------------------------------------------- helpers

    protected function makeBank(string $name, string $lastFour = '1234'): int
    {
        return (int) DB::table('reconciliation_bank_accounts')->insertGetId([
            'client_id' => $this->client->id,
            'bank_name' => $name,
            'account_label' => 'Current',
            'account_last_four' => $lastFour,
            'currency' => 'GBP',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Build a small but structurally valid PDF (with a cross-reference table)
     * whose content stream contains one text line per entry.
     */
    protected function pdf(array $lines): string
    {
        $content = "BT\n/F1 11 Tf\n50 800 Td\n14 TL\n";
        foreach ($lines as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj\nT*\n";
        }
        $content .= "ET\n";

        $objects = [
            1 => '<</Type /Catalog /Pages 2 0 R>>',
            2 => '<</Type /Pages /Kids [3 0 R] /Count 1>>',
            3 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources <</Font <</F1 5 0 R>>>>>>',
            4 => "<</Length " . strlen($content) . ">>\nstream\n" . $content . 'endstream',
            5 => '<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<</Size {$size} /Root 1 0 R>>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    /** A three-row statement whose balances add up (opening 1000 â†’ closing 1100). */
    protected function threeRowPdf(): string
    {
        return $this->pdf([
            'ACME BUSINESS ACCOUNT',
            'Sort code: 12-34-56   Account number: 12345678',
            'Statement period: 01/06/2024 to 30/06/2024',
            'Opening balance 1000.00',
            '15/06/2024 ACME LTD INVOICE 100 -100.00 900.00',
            '16/06/2024 CUSTOMER PAYMENT +250.00 1150.00',
            '18/06/2024 OFFICE SUPPLIES -50.00 1100.00',
            'Closing balance 1100.00',
        ]);
    }

    /** A replacement statement with two different rows (opening 500 â†’ closing 380). */
    protected function replacementPdf(): string
    {
        return $this->pdf([
            'ACME BUSINESS ACCOUNT',
            'Sort code: 12-34-56   Account number: 12345678',
            'Statement period: 01/07/2024 to 31/07/2024',
            'Opening balance 500.00',
            '02/07/2024 TRAVEL EXPENSE -120.00 380.00',
            '09/07/2024 CLIENT PAYMENT 0.00 380.00',
            'Closing balance 380.00',
        ]);
    }

    protected function upload(string $pdf, string $filename = 'statement.pdf', ?int $bankId = null): \Illuminate\Http\JsonResponse
    {
        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-imports',
            'POST',
            ['bank_account_id' => $bankId ?? $this->bankId],
            [],
            ['file' => UploadedFile::fake()->createWithContent($filename, $pdf)]
        );
        $request->setUserResolver(fn () => User::find($this->userId));

        $response = app(BankReconciliationController::class)->upload($request, (int) $this->client->id);
        $this->rememberStoredFile($response->getData(true)['import_id'] ?? null);

        return $response;
    }

    protected function rememberStoredFile(?int $importId): void
    {
        if (!$importId) {
            return;
        }
        $path = DB::table('bank_imports')->where('id', $importId)->value('storage_path');
        if ($path) {
            $this->storedFiles[] = $path;
        }
    }

    // ----------------------------------------------------------------- tests

    /** The upload actually parses the PDF and stores every transaction. */
    public function test_upload_stores_the_parsed_transactions(): void
    {
        $response = $this->upload($this->threeRowPdf());
        $this->assertSame(201, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame(3, $payload['count']);
        $this->assertSame(0, $payload['duplicates_skipped']);
        $this->assertSame('ready_for_reconciliation', $payload['verification_status']);
        $this->assertSame(1, $payload['pages_total']);
        $this->assertEqualsWithDelta(1000.00, (float) $payload['opening_balance'], 0.001);
        $this->assertEqualsWithDelta(1100.00, (float) $payload['closing_balance'], 0.001);

        $import = DB::table('bank_imports')->find($payload['import_id']);
        $this->assertNotNull($import);
        $this->assertSame(3, (int) $import->transaction_count, 'transaction_count must match the stored rows.');
        $this->assertSame('pdf', $import->file_type);
        $this->assertSame('ready_for_reconciliation', $import->verification_status);
        $this->assertSame(1, (int) $import->pages_total);

        $entries = DB::table('bank_entries')
            ->where('bank_import_id', $import->id)
            ->orderBy('transaction_date')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['2024-06-15', '2024-06-16', '2024-06-18'], $entries->pluck('transaction_date')->all());
        $this->assertEqualsWithDelta(-100.00, (float) $entries[0]->amount, 0.001);
        $this->assertEqualsWithDelta(250.00, (float) $entries[1]->amount, 0.001);
        $this->assertEqualsWithDelta(-50.00, (float) $entries[2]->amount, 0.001);

        foreach ($entries as $entry) {
            $this->assertSame('needs_review', $entry->status, 'Fresh PDF rows must wait for accountant review.');
            $this->assertSame('text', $entry->extraction_source);
            $this->assertSame(1, (int) $entry->source_page);
            $this->assertNotNull($entry->line_fingerprint);
            $this->assertSame((int) $this->bankId, (int) $entry->bank_account_id);
            $this->assertSame((int) $this->client->id, (int) $entry->client_id);
        }
    }

    /** The app reads these fields to render a statement row â€” keep them stable. */
    public function test_imports_endpoint_returns_the_fields_the_app_uses(): void
    {
        $this->upload($this->threeRowPdf());
        $importId = (int) DB::table('bank_imports')->where('client_id', $this->client->id)->max('id');

        $request = Request::create('/api/businesses/' . $this->client->id . '/bank-imports', 'GET');
        $request->setUserResolver(fn () => User::find($this->userId));
        $payload = app(BankReconciliationController::class)
            ->imports($request, (int) $this->client->id)
            ->getData(true);

        $this->assertArrayHasKey('data', $payload);
        $row = collect($payload['data'])->firstWhere('id', $importId);
        $this->assertNotNull($row, 'Uploaded statement missing from the list.');

        foreach (['filename', 'file_type', 'processing_status', 'verification_status', 'transaction_count',
                  'pages_total', 'total_amount', 'approved_count', 'status_counts', 'bank_name',
                  'bank_account_id', 'created_at'] as $key) {
            $this->assertArrayHasKey($key, $row, "bank-imports is missing `{$key}`.");
        }
        $this->assertSame(3, (int) $row['transaction_count']);
        $this->assertEqualsWithDelta(100.00, (float) $row['total_amount'], 0.001);
        $this->assertSame(3, (int) ($row['status_counts']['needs_review'] ?? 0));
    }

    /** The detail screen loads rows through bank-entries?import_id=. */
    public function test_entries_endpoint_returns_rows_for_one_import(): void
    {
        $this->upload($this->threeRowPdf());
        $otherId = (int) DB::table('bank_imports')->where('client_id', $this->client->id)->max('id');

        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-entries',
            'GET',
            ['import_id' => $otherId, 'per_page' => 200]
        );
        $request->setUserResolver(fn () => User::find($this->userId));
        $payload = app(BankReconciliationController::class)
            ->entries($request, (int) $this->client->id)
            ->getData(true);

        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(3, $payload['data']);
        $this->assertSame(3, (int) $payload['total']);
        foreach ($payload['data'] as $row) {
            $this->assertSame($otherId, (int) $row['bank_import_id']);
            $this->assertArrayHasKey('description', $row);
            $this->assertArrayHasKey('transaction_date', $row);
            $this->assertArrayHasKey('amount', $row);
        }
    }

    /** Overlapping uploads must not duplicate lines, and the same file twice is refused. */
    public function test_the_same_pdf_cannot_be_imported_twice(): void
    {
        $first = $this->upload($this->threeRowPdf());
        $this->assertSame(201, $first->getStatusCode());
        $importId = (int) $first->getData(true)['import_id'];

        try {
            $this->upload($this->threeRowPdf());
            $this->fail('A duplicate statement should have been rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already imported', $e->getMessage());
        }

        $this->assertSame(1, DB::table('bank_imports')->where('client_id', $this->client->id)
            ->where('id', $importId)->count());
        $this->assertSame(3, DB::table('bank_entries')->where('bank_import_id', $importId)->count());
    }

    /** A second, different statement for the same account only adds its new lines. */
    public function test_a_second_statement_skips_lines_already_on_the_account(): void
    {
        $this->upload($this->threeRowPdf());

        // Same ledger, but two new rows at the end â€” only those two are new.
        $overlapping = $this->pdf([
            'ACME BUSINESS ACCOUNT',
            'Sort code: 12-34-56   Account number: 12345678',
            'Opening balance 1000.00',
            '15/06/2024 ACME LTD INVOICE 100 -100.00 900.00',
            '16/06/2024 CUSTOMER PAYMENT +250.00 1150.00',
            '18/06/2024 OFFICE SUPPLIES -50.00 1100.00',
            '20/06/2024 BANK CHARGE -10.00 1090.00',
            '22/06/2024 REFUND +5.00 1095.00',
            'Closing balance 1095.00',
        ]);

        $payload = $this->upload($overlapping)->getData(true);
        $this->assertSame(2, $payload['count'], 'Only the unseen lines should be stored.');
        $this->assertSame(3, $payload['duplicates_skipped']);
        $this->assertSame(5, DB::table('bank_entries')
            ->where('client_id', $this->client->id)
            ->where('bank_account_id', $this->bankId)
            ->count());
    }

    /** Replacing the PDF re-parses it: old rows go, new rows are stored and verified. */
    public function test_replacing_the_pdf_replaces_the_transactions(): void
    {
        $importId = (int) $this->upload($this->threeRowPdf())->getData(true)['import_id'];
        $oldPath = DB::table('bank_imports')->where('id', $importId)->value('storage_path');

        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-imports/' . $importId . '/replace',
            'POST',
            [],
            [],
            ['file' => UploadedFile::fake()->createWithContent('corrected.pdf', $this->replacementPdf())]
        );
        $request->setUserResolver(fn () => User::find($this->userId));

        $response = app(BankReconciliationController::class)
            ->replaceStatement($request, (int) $this->client->id, $importId);
        $this->assertSame(200, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame(2, $payload['count']);
        $this->assertSame('ready_for_reconciliation', $payload['verification_status']);

        $import = DB::table('bank_imports')->find($importId);
        $this->assertSame('corrected.pdf', $import->filename);
        $this->assertSame(2, (int) $import->transaction_count);
        $this->assertNotSame($oldPath, $import->storage_path, 'The replaced file should be stored fresh.');
        $this->storedFiles[] = $import->storage_path;
        $this->assertFalse(Storage::disk('local')->exists($oldPath), 'The superseded PDF should be deleted.');

        $entries = DB::table('bank_entries')->where('bank_import_id', $importId)->orderBy('transaction_date')->get();
        $this->assertCount(2, $entries);
        $this->assertSame(['2024-07-02', '2024-07-09'], $entries->pluck('transaction_date')->all());
        $this->assertEqualsWithDelta(-120.00, (float) $entries[0]->amount, 0.001);
        $this->assertSame('needs_review', $entries[0]->status);
        $this->assertSame(0, DB::table('bank_entries')->where('bank_import_id', $importId)
            ->where('transaction_date', '2024-06-15')->count(), 'Rows from the old PDF must be gone.');
    }

    /** Once a row is reconciled the document is locked, exactly as the UI promises. */
    public function test_replacing_is_refused_once_a_row_is_reconciled(): void
    {
        $importId = (int) $this->upload($this->threeRowPdf())->getData(true)['import_id'];
        DB::table('bank_entries')->where('bank_import_id', $importId)->limit(1)->update(['status' => 'matched']);

        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-imports/' . $importId . '/replace',
            'POST',
            [],
            [],
            ['file' => UploadedFile::fake()->createWithContent('corrected.pdf', $this->replacementPdf())]
        );
        $request->setUserResolver(fn () => User::find($this->userId));

        $this->expectException(ValidationException::class);
        try {
            app(BankReconciliationController::class)->replaceStatement($request, (int) $this->client->id, $importId);
        } finally {
            // Nothing new should have been stored.
            $this->assertSame(3, DB::table('bank_entries')->where('bank_import_id', $importId)->count());
        }
    }

    /** A replacement PDF that cannot be read must not wipe the existing rows. */
    public function test_replacing_with_an_unreadable_pdf_is_refused(): void
    {
        $importId = (int) $this->upload($this->threeRowPdf())->getData(true)['import_id'];
        $originalPath = DB::table('bank_imports')->where('id', $importId)->value('storage_path');
        $originalHash = DB::table('bank_imports')->where('id', $importId)->value('sha256');

        $unreadable = $this->pdf([
            'ACME BUSINESS ACCOUNT',
            'This replacement has no transaction table at all.',
        ]);

        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-imports/' . $importId . '/replace',
            'POST',
            [],
            [],
            ['file' => UploadedFile::fake()->createWithContent('unreadable.pdf', $unreadable)]
        );
        $request->setUserResolver(fn () => User::find($this->userId));

        try {
            app(BankReconciliationController::class)->replaceStatement($request, (int) $this->client->id, $importId);
            $this->fail('An unreadable replacement should have been refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('left unchanged', $e->getMessage());
        }

        $this->assertSame(3, DB::table('bank_entries')->where('bank_import_id', $importId)->count());
        $this->assertSame(3, (int) DB::table('bank_imports')->where('id', $importId)->value('transaction_count'));
        $this->assertSame($originalPath, DB::table('bank_imports')->where('id', $importId)->value('storage_path'));
        $this->assertSame($originalHash, DB::table('bank_imports')->where('id', $importId)->value('sha256'));
    }

    /** Moving a statement moves its rows with it. */
    public function test_changing_the_bank_account_moves_the_rows(): void
    {
        $importId = (int) $this->upload($this->threeRowPdf())->getData(true)['import_id'];
        $otherBank = $this->makeBank('Barclays', '9876');

        $request = Request::create(
            '/api/businesses/' . $this->client->id . '/bank-imports/' . $importId . '/bank-account',
            'PUT',
            ['bank_account_id' => $otherBank]
        );
        $request->setUserResolver(fn () => User::find($this->userId));

        $response = app(BankReconciliationController::class)
            ->changeBankAccount($request, (int) $this->client->id, $importId);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame($otherBank, (int) DB::table('bank_imports')->where('id', $importId)->value('bank_account_id'));
        $this->assertSame(3, DB::table('bank_entries')->where('bank_import_id', $importId)
            ->where('bank_account_id', $otherBank)->count());
    }

    /** A PDF with no readable transactions is stored but flagged, not silently "done". */
    public function test_a_pdf_without_transactions_is_flagged_for_review(): void
    {
        $pdf = $this->pdf([
            'ACME BUSINESS ACCOUNT',
            'This document has no transaction table at all.',
        ]);

        $response = $this->upload($pdf, 'scan.pdf');
        $this->assertSame(201, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame(0, $payload['count']);
        $this->assertSame('extraction_failed', $payload['verification_status']);
        $this->assertSame(0, DB::table('bank_entries')->where('bank_import_id', $payload['import_id'])->count());
        $this->assertSame('manual_extraction_required', DB::table('bank_imports')
            ->where('id', $payload['import_id'])->value('processing_status'));
    }

    /** An upload with more than 10,000 lines is refused before anything is stored. */
    public function test_a_statement_over_the_row_limit_is_refused(): void
    {
        $lines = ['ACME BUSINESS ACCOUNT', 'Opening balance 0.00'];
        for ($i = 0; $i < 10001; $i++) {
            $lines[] = '15/06/2024 ROW ' . $i . ' -1.00';
        }
        $lines[] = 'Closing balance -10001.00';

        $this->expectException(ValidationException::class);
        try {
            $this->upload($this->pdf($lines), 'huge.pdf');
        } finally {
            $this->assertSame(0, DB::table('bank_imports')
                ->where('client_id', $this->client->id)
                ->where('filename', 'huge.pdf')
                ->count());
        }
    }
}
