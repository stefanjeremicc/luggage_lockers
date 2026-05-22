<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Derived first-touch marketing channel (google_ads, facebook,
            // organic, referral, direct, other). Indexed for dashboard rollups.
            $table->string('marketing_source', 20)->nullable()->after('user_agent');
            // Raw first-touch signals, kept for auditing / future re-classification.
            $table->string('utm_source', 100)->nullable()->after('marketing_source');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 150)->nullable()->after('utm_medium');
            $table->string('referrer', 500)->nullable()->after('utm_campaign');
            $table->string('landing_page', 500)->nullable()->after('referrer');

            $table->index('marketing_source');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['marketing_source']);
            $table->dropColumn([
                'marketing_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'referrer',
                'landing_page',
            ]);
        });
    }
};
