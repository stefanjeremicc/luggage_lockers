<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttlock_api_log', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->json('request_params')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->integer('errcode')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('related_type', 50)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('endpoint');
            $table->index('errcode');
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttlock_api_log');
    }
};
