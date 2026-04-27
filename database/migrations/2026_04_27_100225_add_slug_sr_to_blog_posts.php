<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('slug_sr')->nullable()->after('slug');
            $table->index('slug_sr');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['slug_sr']);
            $table->dropColumn('slug_sr');
        });
    }
};
