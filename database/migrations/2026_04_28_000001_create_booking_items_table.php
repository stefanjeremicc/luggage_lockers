<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item booking lines: a single booking can now contain multiple items, each
 * with its own locker size, quantity, duration, time window, and price. Existing
 * bookings are backfilled as a single item using the legacy aggregate columns
 * (locker_size, locker_qty, duration_label, check_in, check_out, price_eur).
 *
 * The legacy columns on `bookings` stay for backward compat — they hold the
 * "primary" item summary so old admin views keep rendering. Source of truth
 * for new code is `booking_items`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('locker_size', ['standard', 'large']);
            $table->unsignedInteger('qty');
            $table->string('duration_key', 32);
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->decimal('unit_price_eur', 8, 2);
            $table->decimal('line_total_eur', 8, 2);
            $table->timestamps();

            $table->index(['booking_id']);
            $table->index(['locker_size', 'check_in', 'check_out']);
        });

        Schema::table('booking_lockers', function (Blueprint $table) {
            $table->foreignId('booking_item_id')->nullable()->after('booking_id')
                ->constrained('booking_items')->nullOnDelete();
        });

        // Backfill: one item per existing booking, mirroring legacy aggregate columns.
        $now = now();
        DB::table('bookings')->orderBy('id')->chunkById(200, function ($bookings) use ($now) {
            foreach ($bookings as $b) {
                $qty = max(1, (int) $b->locker_qty);
                $unit = $qty > 0 ? round((float) $b->price_eur / $qty, 2) : (float) $b->price_eur;

                $itemId = DB::table('booking_items')->insertGetId([
                    'booking_id' => $b->id,
                    'locker_size' => $b->locker_size,
                    'qty' => $qty,
                    'duration_key' => $b->duration_label ?: '6h',
                    'check_in' => $b->check_in,
                    'check_out' => $b->check_out,
                    'unit_price_eur' => $unit,
                    'line_total_eur' => $b->price_eur,
                    'created_at' => $b->created_at ?? $now,
                    'updated_at' => $b->updated_at ?? $now,
                ]);

                DB::table('booking_lockers')
                    ->where('booking_id', $b->id)
                    ->update(['booking_item_id' => $itemId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_lockers', function (Blueprint $table) {
            $table->dropForeign(['booking_item_id']);
            $table->dropColumn('booking_item_id');
        });
        Schema::dropIfExists('booking_items');
    }
};
