<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reconciliation_bank_accounts', function(Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('bank_name', 100);
            $table->string('account_label', 100);
            $table->string('account_last_four', 4)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['client_id','active']);
        });
        Schema::table('bank_imports',function(Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('client_id')->constrained('reconciliation_bank_accounts');
            $table->index(['client_id','bank_account_id']);
        });
        Schema::table('bank_entries',function(Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('client_id')->constrained('reconciliation_bank_accounts');
            $table->index(['client_id','bank_account_id','transaction_date']);
        });
        Schema::table('bank_imports',function(Blueprint $table) {
            $table->dropUnique('bank_imports_client_id_sha256_unique');
            $table->unique(['bank_account_id','sha256'],'bank_imports_account_sha256_unique');
        });
        // Existing imports cannot reliably be attributed to a specific bank.
        // Keep them in a clearly marked legacy bucket for manual identification.
        foreach(DB::table('bank_imports')->select('client_id')->distinct()->pluck('client_id') as $clientId) {
            $id=DB::table('reconciliation_bank_accounts')->insertGetId([
                'client_id'=>$clientId,'bank_name'=>'Unknown historical bank',
                'account_label'=>'Legacy imports — verify account','active'=>false,
                'currency'=>'GBP','created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('bank_imports')->where('client_id',$clientId)->whereNull('bank_account_id')->update(['bank_account_id'=>$id]);
            DB::table('bank_entries')->where('client_id',$clientId)->whereNull('bank_account_id')->update(['bank_account_id'=>$id]);
        }
    }
    public function down(): void {
        Schema::table('bank_imports',function(Blueprint $table) {
            $table->dropUnique('bank_imports_account_sha256_unique');
            $table->unique(['client_id','sha256'],'bank_imports_client_id_sha256_unique');
        });
        Schema::table('bank_entries',function(Blueprint $table){$table->dropConstrainedForeignId('bank_account_id');});
        Schema::table('bank_imports',function(Blueprint $table){$table->dropConstrainedForeignId('bank_account_id');});
        Schema::dropIfExists('reconciliation_bank_accounts');
    }
};
