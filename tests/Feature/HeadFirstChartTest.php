<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountHeadController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\ReceiptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Head-first chart of accounts:
//   1. the Account Head is created first (no type required),
//   2. its Account Type is then selected or created underneath it
//      (global scope or the head's client scope).
// Untyped heads are hidden from receipt/bank pickers until assigned.
class HeadFirstChartTest extends TestCase
{
    protected AccountHeadController $hc;
    protected AccountTypeController $tc;
    protected ReceiptController $rc;
    protected int $credA = 1;
    protected static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->hc = new AccountHeadController();
        $this->tc = new AccountTypeController();
        $this->rc = new ReceiptController();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function uid(): string
    {
        return 'H' . (++self::$seq) . substr((string) (microtime(true) * 1000), -5);
    }

    public function test_head_is_created_first_without_a_type(): void
    {
        $code = 980;
        $r = $this->hc->store(new Request([
            'code' => $code,
            'name' => 'Head First ' . $this->uid(),
        ]))->getData(true);

        $this->assertEquals(300, $r['status']);
        $this->assertNotNull($r['id']);
        $this->assertNull(DB::table('account_heads')->where('id', $r['id'])->value('account_type_id'));
    }

    public function test_untyped_head_is_hidden_from_receipt_picker(): void
    {
        $r = $this->hc->store(new Request([
            'code' => 981,
            'name' => 'Untyped Hidden ' . $this->uid(),
        ]))->getData(true);
        $this->assertEquals(300, $r['status']);

        $rows = collect($this->rc->getAccountHeads(new Request([]))->getData(true));
        $this->assertFalse($rows->contains('id', $r['id']), 'Untyped heads must not be offered on receipts');
    }

    public function test_assignable_types_are_shared_plus_own(): void
    {
        $r = $this->hc->store(new Request([
            'code' => 982,
            'name' => 'Assignable ' . $this->uid(),
        ]))->getData(true);
        $headId = $r['id'];

        $list = collect($this->hc->assignableTypes(new Request(['head_id' => $headId]))->getData(true));
        $this->assertNotEmpty($list);
        // A new head can only be given shared (no-head) types — seeder types qualify.
        foreach ($list as $t) {
            $this->assertNull($t['under_head'], 'Only shared types may be offered to a brand-new head');
        }

        // Assigning works. Per-client heads are exempt from the Excel code
        // ranges (the seeded global ranges 101-604 are fully occupied), so
        // exercise the happy path on a client-scoped head taking a shared type.
        $clientHead = $this->hc->store(new Request([
            'code' => 987,
            'name' => 'Client Owner ' . $this->uid(),
            'client_credential_id' => $this->credA,
        ]))->getData(true);
        $this->assertEquals(300, $clientHead['status']);

        $sharedTypeId = DB::table('account_types')->whereNull('account_head_id')->where('is_active', 1)->value('id');
        $a = $this->hc->assignType(new Request(['account_type_id' => $sharedTypeId]), $clientHead['id'])->getData(true);
        $this->assertEquals(300, $a['status']);
        $this->assertEquals($sharedTypeId, DB::table('account_heads')->where('id', $clientHead['id'])->value('account_type_id'));
    }

    public function test_type_created_under_head_requires_head_and_auto_assigns(): void
    {
        // A type without a head is rejected.
        $noHead = $this->tc->store(new Request([
            'name' => 'Headless Type ' . $this->uid(),
            'category' => 'expense',
            'normal_balance' => 'debit',
        ]))->getData(true);
        $this->assertEquals(303, $noHead['status']);

        // Create the head first, then the type underneath it.
        $head = $this->hc->store(new Request([
            'code' => 983,
            'name' => 'Type Under ' . $this->uid(),
        ]))->getData(true);
        $this->assertEquals(300, $head['status']);
        $this->assertNull(DB::table('account_heads')->where('id', $head['id'])->value('account_type_id'));

        $typeName = 'Under Type ' . $this->uid();
        $t = $this->tc->store(new Request([
            'account_head_id' => $head['id'],
            'name' => $typeName,
            'category' => 'expense',
            'normal_balance' => 'debit',
        ]))->getData(true);
        $this->assertEquals(300, $t['status']);

        $row = DB::table('account_types')->where('id', $t['id'])->first();
        $this->assertEquals($head['id'], $row->account_head_id);
        // Global head -> global (shared) type scope.
        $this->assertNull($row->client_credential_id);
        // The previously untyped head adopted its new type automatically.
        $this->assertEquals($t['id'], DB::table('account_heads')->where('id', $head['id'])->value('account_type_id'));
    }

    public function test_type_under_client_head_is_client_scoped(): void
    {
        $head = $this->hc->store(new Request([
            'code' => 984,
            'name' => 'Client Head ' . $this->uid(),
            'client_credential_id' => $this->credA,
        ]))->getData(true);
        $this->assertEquals(300, $head['status']);

        $t = $this->tc->store(new Request([
            'account_head_id' => $head['id'],
            'name' => 'Client Type ' . $this->uid(),
            'category' => 'expense',
            'normal_balance' => 'debit',
        ]))->getData(true);
        $this->assertEquals(300, $t['status']);

        $row = DB::table('account_types')->where('id', $t['id'])->first();
        // Scope follows the head: per-client head -> that client's type.
        $this->assertEquals($this->credA, (int) $row->client_credential_id);
    }

    public function test_global_head_rejects_out_of_range_code_on_assign(): void
    {
        $r = $this->hc->store(new Request([
            'code' => 999,
            'name' => 'Range Guard ' . $this->uid(),
        ]))->getData(true);
        $this->assertEquals(300, $r['status']); // no type yet -> range deferred

        // Assign the 'Turnover' type (101-107): code 999 is out of range.
        $turnoverId = DB::table('account_types')->where('name', 'Turnover')->value('id');
        $a = $this->hc->assignType(new Request(['account_type_id' => $turnoverId]), $r['id'])->getData(true);
        $this->assertEquals(303, $a['status']);
        $this->assertNull(DB::table('account_heads')->where('id', $r['id'])->value('account_type_id'));
    }

    public function test_type_cannot_be_assigned_to_two_heads(): void
    {
        $headA = $this->hc->store(new Request(['code' => 985, 'name' => 'Owner A ' . $this->uid()]))->getData(true);
        $headB = $this->hc->store(new Request(['code' => 986, 'name' => 'Owner B ' . $this->uid()]))->getData(true);

        $t = $this->tc->store(new Request([
            'account_head_id' => $headA['id'],
            'name' => 'Owned Type ' . $this->uid(),
            'category' => 'expense',
            'normal_balance' => 'debit',
        ]))->getData(true);
        $this->assertEquals(300, $t['status']);

        // Head B may not take a type that lives under head A.
        $a = $this->hc->assignType(new Request(['account_type_id' => $t['id']]), $headB['id'])->getData(true);
        $this->assertEquals(303, $a['status']);
        // Head A already adopted it during creation.
        $this->assertEquals($t['id'], DB::table('account_heads')->where('id', $headA['id'])->value('account_type_id'));
    }
}
