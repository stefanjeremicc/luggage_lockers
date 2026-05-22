<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-base the display sequence so it starts at 1000 (1000, 1001, 1002 …).
     * Deterministic: re-numbers every existing booking by id order regardless of
     * whatever the initial backfill assigned. The real PK `id` is untouched.
     */
    public function up(): void
    {
        $n = 999;
        foreach (DB::table('bookings')->orderBy('id')->pluck('id') as $id) {
            DB::table('bookings')->where('id', $id)->update(['booking_number' => ++$n]);
        }
    }

    public function down(): void
    {
        // No-op: there is no meaningful "un-rebase".
    }
};
