<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestFixturesSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user for verification / actingAs
        DB::table('users')->insertOrIgnore([
            'id' => 1, 'first_name' => 'Admin', 'last_name' => 'User', 'email' => 'admin@test.com',
            'password' => bcrypt('secret'), 'type' => 1, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('users')->insertOrIgnore([
            'id' => 2, 'first_name' => 'Regular', 'last_name' => 'User', 'email' => 'user@test.com',
            'password' => bcrypt('secret'), 'type' => 0, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Client credentials (id 1 and 2 for head-first / accounting tests)
        DB::table('client_credentials')->insertOrIgnore([
            'id' => 1, 'email' => 'clientA@test.com', 'password' => bcrypt('secret'),
            'status' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_credentials')->insertOrIgnore([
            'id' => 2, 'email' => 'clientB@test.com', 'password' => bcrypt('secret'),
            'status' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Clients linked to credentials
        DB::table('clients')->insertOrIgnore([
            'id' => 1, 'client_credential_id' => 1, 'status' => 1,
            'name' => 'Client A', 'business_name' => 'Test Business A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clients')->insertOrIgnore([
            'id' => 2, 'client_credential_id' => 2, 'status' => 1,
            'name' => 'Client B', 'business_name' => 'Test Business B',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Company details needed for admin layout / frontend index
        DB::table('company_details')->insertOrIgnore([
            'id' => 1, 'company_name' => 'HD Accountancy', 'fav_icon' => 'logo.png',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
