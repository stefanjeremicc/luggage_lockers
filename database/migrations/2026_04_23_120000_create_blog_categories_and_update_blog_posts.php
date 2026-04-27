<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_sr', 100)->nullable();
            $table->string('slug', 120)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('title_sr')->nullable()->after('title');
            $table->text('excerpt_sr')->nullable()->after('excerpt');
            $table->longText('content_sr')->nullable()->after('content');
            $table->string('meta_title_sr')->nullable()->after('meta_title');
            $table->string('meta_description_sr', 500)->nullable()->after('meta_description');
            $table->foreignId('blog_category_id')
                ->nullable()
                ->after('category')
                ->constrained('blog_categories')
                ->nullOnDelete();

            // Drop composite unique on (slug, locale); one post = one slug now.
            $table->dropUnique(['slug', 'locale']);
            $table->unique('slug');
        });

        $existing = DB::table('blog_posts')->whereNotNull('category')->pluck('category')->unique();
        $idByName = [];
        $sort = 0;
        foreach ($existing as $name) {
            $slug = \Illuminate\Support\Str::slug($name);
            $idByName[$name] = DB::table('blog_categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach ($idByName as $name => $id) {
            DB::table('blog_posts')->where('category', $name)->update(['blog_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blog_category_id');
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'locale']);
            $table->dropColumn(['title_sr', 'excerpt_sr', 'content_sr', 'meta_title_sr', 'meta_description_sr']);
        });
        Schema::dropIfExists('blog_categories');
    }
};
