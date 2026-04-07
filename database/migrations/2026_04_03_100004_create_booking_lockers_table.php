<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_lockers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locker_id')->constrained();
            $table->string('pin_code_encrypted')->nullable();
            $table->bigInteger('ttlock_keyboard_pwd_id')->nullable();
            $table->timestamp('assigned_at')->nullable();

            $table->unique(['booking_id', 'locker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_lockers');
    }
};
