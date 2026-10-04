<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Proves the classic invoice calculates and shows everything properly,
// using fixture receipts (rolled back). Covers totals, amount in words,
// all data blocks, statuses, and edge cases.
class ReceiptBillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->be(User::first());
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    protected function makeClient(): Client
    {
        $c = Client::first();
        $c->update([
            'business_name' => 'Test Business Ltd',
            'company_name' => 'Test Company Ltd',
            'company_number' => '12345678',
            'address_line1' => '12 Test Street',
            'address_line2' => 'Test Area',
            'city' => 'London',
            'postcode' => 'E1 6AN',
            'country' => 'United Kingdom',
            'email' => 'test@example.com',
            'phone' => '020 7946 0000',
        ]);
        return $c->fresh();
    }

    protected function makeReceipt(Client $c, array $detail = [], array $over = []): Receipt
    {
        $r = Receipt::create(array_merge([
            'client_id' => $c->id,
            'receipt_number' => 'TEST-BILL-' . uniqid(),
            'receipt_date' => '2020-06-15',
            'status' => 'ready',
            'supplier' => 'Acme Supplies Ltd',
            'notes' => 'Test note line',
        ], $over));
        if ($detail !== null) {
            $head = AccountHead::where('code', '301')->whereNull('client_credential_id')->first();
            $r->detail()->create(array_merge([
                'account_head_id' => $head->id,
                'invoice_date' => '2020-06-15',
                'due_date' => '2020-07-15',
                'invoice_number' => 'INV-999',
                'net_amount' => 1234.56,
                'tax_amount' => 10,
                'vat_amount' => 246.91,
                'total_amount' => 1491.47,
                'paid' => true,
                'payment_method' => 'bank',
                'description' => 'Audit fee',
            ], $detail));
        }
        return $r->fresh()->load(['client', 'detail.accountHead.accountType', 'detail.accountHead.taxRate']);
    }

    protected function render(Receipt $r): string
    {
        return view('admin.receipt.bill', ['receipt' => $r])->render();
    }

    public function test_full_receipt_calculates_and_shows_everything(): void
    {
        $html = $this->render($this->makeReceipt($this->makeClient()));

        // refs + dates
        $this->assertStringContainsString('TEST-BILL-', $html);
        $this->assertStringContainsString('INV-999', $html);
        $this->assertStringContainsString('15 Jun 2020', $html);
        $this->assertStringContainsString('15 Jul 2020', $html);
        // totals math: 1234.56 + 10 + 246.91 = 1491.47
        $this->assertStringContainsString('£1,234.56', $html);
        $this->assertStringContainsString('£10.00', $html);
        $this->assertStringContainsString('£246.91', $html);
        $this->assertStringContainsString('£1,491.47', $html);
        // amount in words on the same row as totals
        $this->assertStringContainsString(
            'One thousand four hundred and ninety-one pounds and forty-seven pence only',
            $html
        );
        $this->assertMatchesRegularExpression('/inv-totalrow.*inv-words.*inv-totals/s', $html);
        // bill-to block
        foreach (['Test Business Ltd', 'Test Company Ltd', '12345678', '12 Test Street', 'London', 'E1 6AN', 'United Kingdom', 'test@example.com', '020 7946 0000'] as $s) {
            $this->assertStringContainsString($s, $html);
        }
        // account head + tax
        $this->assertStringContainsString('301', $html);
        $this->assertStringContainsString('Accountancy', $html);
        $this->assertStringContainsString('Audit fee', $html);
        // supplier + notes + payment + stamp
        $this->assertStringContainsString('Acme Supplies Ltd', $html);
        $this->assertStringContainsString('Test note line', $html);
        $this->assertStringContainsString('Paid — Bank', $html);
        // logo + signatures + footer
        $this->assertStringContainsString('assets/img/logo.png', $html);
        foreach (['Prepared by', 'Checked by', 'Received by'] as $s) {
            $this->assertStringContainsString($s, $html);
        }
    }

    public function test_amount_in_words_edges(): void
    {
        $c = $this->makeClient();
        $cases = [
            [0, 'Zero pounds only'],
            [1, 'One pound only'],
            [19.99, 'Nineteen pounds and ninety-nine pence only'],
            [105.05, 'One hundred and five pounds and five pence only'],
            [1234567.89, 'One million two hundred and thirty-four thousand five hundred and sixty-seven pounds and eighty-nine pence only'],
        ];
        foreach ($cases as [$total, $words]) {
            $r = $this->makeReceipt($c, ['net_amount' => $total, 'tax_amount' => 0, 'vat_amount' => 0, 'total_amount' => $total]);
            $html = $this->render($r);
            $this->assertStringContainsString($words, $html, "words for $total");
            $this->assertStringContainsString('£' . number_format($total, 2), $html, "total for $total");
        }
    }

    public function test_tax_vat_rows_hidden_when_zero(): void
    {
        $html = $this->render($this->makeReceipt($this->makeClient(), ['tax_amount' => 0, 'vat_amount' => 0]));
        $this->assertStringNotContainsString('<td>Tax</td>', $html);
        $this->assertStringNotContainsString('<td>VAT</td>', $html);
        $this->assertStringContainsString('<td>Subtotal</td>', $html);
    }

    public function test_statuses_and_unpaid(): void
    {
        $c = $this->makeClient();
        $cancelled = $this->render($this->makeReceipt($c, [], ['status' => 'cancelled', 'receipt_number' => 'TEST-BILL-C1']));
        $this->assertStringContainsString('Cancelled', $cancelled);

        $unpaid = $this->render($this->makeReceipt($c, ['paid' => false], ['receipt_number' => 'TEST-BILL-U1']));
        $this->assertStringContainsString('Unpaid', $unpaid);
    }

    public function test_no_detail_renders_zeros_gracefully(): void
    {
        $c = $this->makeClient();
        $r = Receipt::create(['client_id' => $c->id, 'receipt_number' => 'TEST-BILL-N1', 'receipt_date' => '2020-06-15', 'status' => 'pending']);
        $html = $this->render($r->fresh()->load(['client', 'detail']));
        $this->assertStringContainsString('No details added yet.', $html);
        $this->assertStringContainsString('£0.00', $html);
        $this->assertStringContainsString('Zero pounds only', $html);
    }
}
