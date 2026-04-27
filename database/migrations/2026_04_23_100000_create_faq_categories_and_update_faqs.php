<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_sr', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question_sr', 500)->nullable()->after('question');
            $table->text('answer_sr')->nullable()->after('answer');
            $table->foreignId('faq_category_id')
                ->nullable()
                ->after('category')
                ->constrained('faq_categories')
                ->nullOnDelete();
        });

        // Backfill: create a category row for each distinct non-null category string,
        // then link existing FAQs to it.
        $existing = DB::table('faqs')->whereNotNull('category')->pluck('category')->unique();
        $idByName = [];
        $sort = 0;
        foreach ($existing as $name) {
            $idByName[$name] = DB::table('faq_categories')->insertGetId([
                'name' => $name,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach ($idByName as $name => $id) {
            DB::table('faqs')->where('category', $name)->update(['faq_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faq_category_id');
            $table->dropColumn(['question_sr', 'answer_sr']);
        });
        Schema::dropIfExists('faq_categories');
    }
};
