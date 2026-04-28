<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('type', 20)->default('page')->after('locale');
            $table->json('sections')->nullable()->after('content');
            $table->foreignId('location_id')->nullable()->after('sections')->constrained('locations')->nullOnDelete();
            $table->string('canonical_url', 500)->nullable()->after('og_image');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['type', 'sections', 'location_id', 'canonical_url', 'og_title', 'og_description']);
        });
    }
};
