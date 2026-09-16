<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE oauth_access_tokens MODIFY token VARCHAR(100) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE oauth_access_tokens MODIFY token VARCHAR(100) NOT NULL');
    }
};
