<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_allocation_lines', function (Blueprint $t) {
            $t->decimal('tax_percent', 5, 2)->default(0)->after('amount');
            $t->decimal('tax_amount', 15, 2)->default(0)->after('tax_percent');
            $t->decimal('vat_amount', 15, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void {
        Schema::table('bank_allocation_lines', function (Blueprint $t) {
            $t->dropColumn(['tax_percent', 'tax_amount', 'vat_amount']);
        });
    }
};
