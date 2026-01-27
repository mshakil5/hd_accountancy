<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('secondary_email')->nullable()->after('email');
            $table->string('registered_address_line1')->nullable()->after('address_line2');
            $table->string('registered_address_line2')->nullable()->after('registered_address_line1');
            $table->string('type_of_business')->nullable()->after('business_name');
            $table->string('company_name')->nullable()->after('type_of_business');
            $table->string('company_number')->nullable()->after('company_name');
            $table->string('trading_address_line1')->nullable()->after('trading_address');
            $table->string('trading_address_line2')->nullable()->after('trading_address_line1');
            $table->string('partnership_business_name')->nullable()->after('trading_address_line2');
            $table->string('partnership_trading_address_line1')->nullable()->after('partnership_business_name');
            $table->string('partnership_trading_address_line2')->nullable()->after('partnership_trading_address_line1');
            $table->integer('number_of_property')->nullable()->after('partnership_trading_address_line2');
            $table->text('property_address')->nullable()->after('number_of_property');
            $table->string('photo_id_saved')->nullable()->after('photo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('secondary_email');
            $table->dropColumn('registered_address_line1');
            $table->dropColumn('registered_address_line2');
            $table->dropColumn('type_of_business');
            $table->dropColumn('company_name');
            $table->dropColumn('company_number');
            $table->dropColumn('trading_address_line1');
            $table->dropColumn('trading_address_line2');
            $table->dropColumn('partnership_business_name');
            $table->dropColumn('partnership_trading_address_line1');
            $table->dropColumn('partnership_trading_address_line2');
            $table->dropColumn('number_of_property');
            $table->dropColumn('property_address');
            $table->dropColumn('photo_id_saved');
        });
    }
};
