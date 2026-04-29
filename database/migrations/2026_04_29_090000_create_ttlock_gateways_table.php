<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttlock_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ttlock_gateway_id')->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('lock_count')->default(0);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttlock_gateways');
    }
};
