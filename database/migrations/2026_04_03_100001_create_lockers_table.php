<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('ttlock_lock_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('number', 20);
            $table->enum('size', ['standard', 'large']);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'offline'])->default('available');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->boolean('is_online')->default(true);
            $table->json('dimensions_cm')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'size', 'is_active']);
            $table->index('ttlock_lock_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockers');
    }
};
