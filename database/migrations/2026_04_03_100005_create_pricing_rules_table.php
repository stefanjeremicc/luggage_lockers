<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('locker_size', ['standard', 'large']);
            $table->string('duration_key', 20);
            $table->decimal('price_eur', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'locker_size', 'duration_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
