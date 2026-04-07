<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('oauth_provider', 20)->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('locale', 5)->default('en');
            $table->boolean('whatsapp_opt_in')->default(false);
            $table->timestamps();

            $table->unique('email');
            $table->index(['oauth_provider', 'oauth_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
