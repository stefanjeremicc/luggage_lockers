<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('channel')->default('email');
            $table->string('locale', 5)->default('en');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
