<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if the column already exists. This deploy runs migrate
        // through an unusual path (no SSH), so guard against a double-add.
        if (Schema::hasColumn('booking_lockers', 'opened_at')) {
            return;
        }

        Schema::table('booking_lockers', function (Blueprint $table) {
            // When the CUSTOMER'S OWN PIN opened this locker, per TTLock unlock
            // records (matched by passcode). NULL = not opened by the customer.
            //
            // Replaces the old heuristic `locker.last_used_at >= check_in`, which
            // flagged ANY unlock on the physical lock (admin remote-unlock, master
            // code, gateway, a different PIN) as "opened" — producing false
            // "opened / in use" flags for bookings whose customer never showed.
            $table->timestamp('opened_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('booking_lockers', 'opened_at')) {
            return;
        }

        Schema::table('booking_lockers', function (Blueprint $table) {
            $table->dropColumn('opened_at');
        });
    }
};
