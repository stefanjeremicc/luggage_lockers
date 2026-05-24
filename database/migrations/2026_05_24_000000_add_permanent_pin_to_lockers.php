<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            // Permanent keypad PIN set directly on the physical lock (TTLock app).
            // Used as a fallback when the TTLock monthly API quota is exhausted:
            // we hand out this code instead of registering a timed one. Stored as
            // digits only (no trailing "#", which is added at display/email time).
            $table->string('permanent_pin', 12)->nullable()->after('ttlock_lock_id');
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropColumn('permanent_pin');
        });
    }
};
