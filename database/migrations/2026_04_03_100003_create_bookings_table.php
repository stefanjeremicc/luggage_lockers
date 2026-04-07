<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->enum('locker_size', ['standard', 'large']);
            $table->unsignedInteger('locker_qty')->default(1);
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->string('duration_label', 20);
            $table->decimal('price_eur', 8, 2);
            $table->decimal('price_rsd', 10, 2)->nullable();
            $table->decimal('service_fee_eur', 8, 2)->default(0.00);
            $table->decimal('total_eur', 8, 2);
            $table->enum('booking_status', ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'stripe'])->default('cash');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('booking_status');
            $table->index(['location_id', 'check_in', 'check_out']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
