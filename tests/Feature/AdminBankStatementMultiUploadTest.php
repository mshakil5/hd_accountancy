<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankStatementPageController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The admin "Create Bank Statement" page must create one statement per PDF
 * when several files are selected at once.
 */
class AdminBankStatementMultiUploadTest extends TestCase
{
    protected object $client;
    protected int $userId;
    protected int $bankId;

    /** @var array<int,string> Files written during the test (not rolled back with the DB). */
    protected array $storedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('bank_imports') || !Schema::hasColumn('bank_imports', 'verification_status')) {
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

        Storage::fake('s3');

        $payload = [
            'client_id' => $this->client->id,
            'bank_name' => 'HSBC',
            'account_last_four' => '4321',
            'currency' => 'GBP',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('reconciliation_bank_accounts', 'account_label')) {
            $payload['account_label'] = 'Current';
        }
        $this->bankId = (int) DB::table('reconciliation_bank_accounts')->insertGetId($payload);
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        foreach ($this->storedFiles as $path) {
            Storage::disk('s3')->delete($path);
        }

        parent::tearDown();
    }

    protected function pdf(string $marker): string
    {
        return "%PDF-1.4\n% {$marker}\n%%EOF\n";
    }

    /** @param array<int,array{0:string,1:string}> $files [filename, contents] */
    protected function upload(array $files, ?string $singleFileName = null): array
    {
        $data = [
            'client_credential_id' => $this->client->client_credential_id,
            'client_id' => $this->client->id,
            'bank_account_id' => $this->bankId,
        ];

        $uploads = [];
        if ($singleFileName !== null) {
            $uploads['file'] = UploadedFile::fake()->createWithContent($singleFileName, $files[0][1]);
        } else {
            foreach ($files as [$name, $contents]) {
                $uploads['files'][] = UploadedFile::fake()->createWithContent($name, $contents);
            }
        }

        $request = Request::create('/admin/bank-statements/store', 'POST', $data, [], $uploads);
        $request->setUserResolver(fn () => $this->userId ? User::find($this->userId) : null);

        $response = app(BankStatementPageController::class)->store($request)->getData(true);

        foreach ($response['ids'] ?? [] as $id) {
            $path = DB::table('bank_imports')->where('id', $id)->value('storage_path');
            if ($path) {
                $this->storedFiles[] = $path;
            }
        }

        return $response;
    }

    public function test_each_selected_pdf_becomes_a_separate_statement(): void
    {
        $result = $this->upload([
            ['january.pdf', $this->pdf('january')],
            ['february.pdf', $this->pdf('february')],
            ['march.pdf', $this->pdf('march')],
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(3, $result['created']);
        $this->assertCount(3, $result['ids']);

        $imports = DB::table('bank_imports')->whereIn('id', $result['ids'])->orderBy('id')->get();
        $this->assertCount(3, $imports);
        $this->assertSame(
            ['january.pdf', 'february.pdf', 'march.pdf'],
            $imports->pluck('filename')->all()
        );
        $this->assertCount(3, $imports->pluck('sha256')->unique());
        $this->assertCount(3, $imports->pluck('storage_path')->unique());

        foreach ($imports as $import) {
            $this->assertSame('pending', $import->verification_status);
            $this->assertSame('pdf', $import->file_type);
            $this->assertTrue(Storage::disk('s3')->exists($import->storage_path));
        }

        $this->assertStringContainsString('client_credential_id=', $result['redirect']);
        $this->assertStringNotContainsString('/verification', $result['redirect']);
    }

    public function test_duplicate_files_are_skipped_not_created_twice(): void
    {
        $contents = $this->pdf('same-month');
        $result = $this->upload([
            ['same.pdf', $contents],
            ['copy.pdf', $contents],
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(['copy.pdf'], $result['skipped']);
        $this->assertSame(1, DB::table('bank_imports')->whereIn('id', $result['ids'])->count());
    }

    public function test_previously_imported_pdf_is_skipped_while_new_ones_are_created(): void
    {
        $old = $this->upload([['old.pdf', $this->pdf('old')]]);
        $this->storedFiles = [];

        $result = $this->upload([
            ['old.pdf', $this->pdf('old')],
            ['new.pdf', $this->pdf('new')],
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(['old.pdf'], $result['skipped']);
        $this->assertNotSame($old['id'], $result['id']);
    }

    public function test_single_file_upload_still_keeps_the_old_behaviour(): void
    {
        $result = $this->upload([['single.pdf', $this->pdf('single')]], 'single.pdf');

        $this->assertSame(200, $result['status']);
        $this->assertSame(1, $result['created']);
        $this->assertStringContainsString('/bank-statements/' . $result['id'] . '/verification', $result['redirect']);
    }

    public function test_an_already_imported_only_batch_is_reported_as_an_error(): void
    {
        $this->upload([['old.pdf', $this->pdf('old')]]);
        $this->storedFiles = [];

        $result = $this->upload([['old.pdf', $this->pdf('old')]]);

        $this->assertSame(303, $result['status']);
        $this->assertStringContainsString('Already imported', $result['message']);
    }
}
