<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->boolean('is_published_on_site')->default(true)->after('status');
            $table->unsignedInteger('site_sort_order')->default(0)->after('is_published_on_site');
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropColumn(['is_published_on_site', 'site_sort_order']);
        });
    }
};
