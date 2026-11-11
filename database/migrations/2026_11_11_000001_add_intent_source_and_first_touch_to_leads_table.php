<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two production edge cases (review 10 November 2026):
     *
     * 1. An explicit Ad ID mapping is the authoritative campaign route. The
     *    pre-filled WhatsApp text must not overwrite it, so the origin of the
     *    intent is recorded in `intent_source`.
     *
     * 2. First-touch acquisition must survive a later click on a different
     *    advert by the same person. The original referral is kept in the
     *    immutable `original_*` columns; later clicks land in `latest_touch_*`
     *    so cost-per-lead / campaign reporting keeps using the first touch.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('intent_source', 20)->nullable()->after('intent');

            $table->string('original_ad_id')->nullable()->after('conversation_step');
            $table->string('original_campaign_id')->nullable()->after('original_ad_id');
            $table->string('original_click_id')->nullable()->after('original_campaign_id');
            $table->string('original_source_url', 1024)->nullable()->after('original_click_id');

            $table->string('latest_touch_ad_id')->nullable()->after('original_source_url');
            $table->string('latest_touch_click_id')->nullable()->after('latest_touch_ad_id');
            $table->timestamp('latest_touch_at')->nullable()->after('latest_touch_click_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'intent_source',
                'original_ad_id',
                'original_campaign_id',
                'original_click_id',
                'original_source_url',
                'latest_touch_ad_id',
                'latest_touch_click_id',
                'latest_touch_at',
            ]);
        });
    }
};
