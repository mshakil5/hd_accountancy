<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bank_statement_exceptions', function (Blueprint $t) {
            $t->timestamp('acknowledged_at')->nullable()->after('resolution');
            $t->unsignedBigInteger('acknowledged_by')->nullable()->after('acknowledged_at');
            $t->text('acknowledgement_note')->nullable()->after('acknowledged_by');
        });
        Schema::table('bank_imports', function (Blueprint $t) {
            $t->string('balance_source', 10)->nullable()->after('calculated_closing_balance');
            $t->timestamp('warnings_acknowledged_at')->nullable()->after('verification_note');
            $t->unsignedBigInteger('warnings_acknowledged_by')->nullable();
            $t->text('warnings_acknowledgement_note')->nullable();
        });
    }
    public function down(): void {
        Schema::table('bank_statement_exceptions', function (Blueprint $t) {
            $t->dropColumn(['acknowledged_at','acknowledged_by','acknowledgement_note']);
        });
        Schema::table('bank_imports', function (Blueprint $t) {
            $t->dropColumn(['balance_source','warnings_acknowledged_at','warnings_acknowledged_by','warnings_acknowledgement_note']);
        });
    }
};
