<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen status column so we can record 'stub' (skipped — credentials missing)
        // and 'pending' alongside 'sent' / 'failed' / 'queued'.
        Schema::table('notification_log', function (Blueprint $table) {
            $table->string('status', 20)->default('queued')->change();
        });
    }

    public function down(): void
    {
        DB::table('notification_log')->whereNotIn('status', ['queued', 'sent', 'failed'])->update(['status' => 'sent']);
        Schema::table('notification_log', function (Blueprint $table) {
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued')->change();
        });
    }
};
