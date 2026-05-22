<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Display-only sequential number. Nullable + non-unique on purpose so
            // it can never block an insert; the real PK `id` stays authoritative.
            $table->unsignedInteger('booking_number')->nullable()->after('id');
        });

        // Backfill existing bookings: 1, 2, 3 … ordered by id (creation order).
        $n = 0;
        foreach (DB::table('bookings')->orderBy('id')->pluck('id') as $id) {
            DB::table('bookings')->where('id', $id)->update(['booking_number' => ++$n]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_number');
        });
    }
};
