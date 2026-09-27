<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-shot role-permission setup (replaces the three granular
     * migrations, so live deploy is a single migrate):
     *
     * 1. Widen roles.permission to TEXT (28 IDs are already ~132 chars;
     *    VARCHAR(255) would eventually overflow and break role saves).
     * 2. Grant Accounting permissions 21-27 to every role
     *    (21 Receipts, 22 Tax Rates, 23 Account Types,
     *     24 Chart of Accounts, 25 Profit & Loss,
     *     26 Trial Balance, 27 Balance Sheet).
     * 3. Grant Client Credentials (28) to every role holding
     *    Client Entry (7) or Client Manage (8), preserving the
     *    visibility it had when bundled with those.
     *
     * Safe to re-run: permission merges are de-duplicated.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `roles` MODIFY `permission` TEXT NULL');

        $accounting = ['21', '22', '23', '24', '25', '26', '27'];

        foreach (DB::table('roles')->select('id', 'permission')->get() as $role) {
            $current = array_map('strval', (array) (json_decode($role->permission ?? '[]', true) ?: []));

            $merged = array_values(array_unique(array_merge($current, $accounting)));

            if (in_array('7', $merged, true) || in_array('8', $merged, true)) {
                if (!in_array('28', $merged, true)) {
                    $merged[] = '28';
                }
            }

            DB::table('roles')->where('id', $role->id)->update([
                'permission' => json_encode(array_values($merged)),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $added = ['21', '22', '23', '24', '25', '26', '27', '28'];

        foreach (DB::table('roles')->select('id', 'permission')->get() as $role) {
            $current = array_map('strval', (array) (json_decode($role->permission ?? '[]', true) ?: []));
            $remaining = array_values(array_diff($current, $added));

            DB::table('roles')->where('id', $role->id)->update([
                'permission' => json_encode($remaining),
            ]);
        }

        DB::statement('ALTER TABLE `roles` MODIFY `permission` VARCHAR(255) NULL');
    }
};
