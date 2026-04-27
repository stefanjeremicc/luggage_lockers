<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['category', 'locale']);
            $table->dropColumn(['category', 'locale']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['locale', 'is_active', 'sort_order']);
            $table->dropColumn(['category', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('category', 100)->nullable();
            $table->string('locale', 5)->default('en');
            $table->index(['category', 'locale']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('category', 100)->nullable();
            $table->string('locale', 5)->default('en');
            $table->index(['locale', 'is_active', 'sort_order']);
        });
    }
};
