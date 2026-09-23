<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingLocker;
use App\Models\Locker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingLockerOpenedTest extends TestCase
{
    use RefreshDatabase;

    private int $lockId = 555;

    private function makeBookingLocker(string $pin, string $status = 'confirmed'): int
    {
        DB::table('locations')->insert(['id' => 1, 'name' => 'T', 'slug' => 't', 'address' => 'A', 'lat' => 44.8, 'lng' => 20.4]);
        DB::table('customers')->insert(['id' => 1, 'uuid' => (string) Str::uuid(), 'full_name' => 'T', 'email' => 'c@example.com']);
        DB::table('lockers')->insert([
            'id' => 1, 'location_id' => 1, 'ttlock_lock_id' => $this->lockId,
            'uuid' => (string) Str::uuid(), 'number' => 'A1', 'size' => 'standard', 'status' => 'available',
        ]);
        DB::table('bookings')->insert([
            'id' => 1, 'uuid' => (string) Str::uuid(), 'customer_id' => 1, 'location_id' => 1,
            'locker_size' => 'standard', 'locker_qty' => 1,
            'check_in' => now()->subHour(), 'check_out' => now()->addDay(),
            'duration_label' => '24h', 'price_eur' => 5, 'total_eur' => 5,
            'booking_status' => $status,
        ]);

        return DB::table('booking_lockers')->insertGetId([
            'booking_id' => 1, 'locker_id' => 1,
            'pin_code_encrypted' => Crypt::encryptString($pin), 'assigned_at' => now(),
        ]);
    }

    public function test_migration_added_opened_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('booking_lockers', 'opened_at'));
    }

    public function test_customer_pin_unlock_stamps_opened_at(): void
    {
        $id = $this->makeBookingLocker('1234');
        BookingLocker::recordCustomerUnlock($this->lockId, '1234', now());
        $this->assertNotNull(BookingLocker::find($id)->opened_at, 'customer PIN unlock should stamp opened_at');
    }

    public function test_admin_or_other_unlock_does_not_stamp(): void
    {
        $id = $this->makeBookingLocker('1234');

        // A different code (master/staff/other booking) must NOT flag this booking.
        BookingLocker::recordCustomerUnlock($this->lockId, '9999', now());
        $this->assertNull(BookingLocker::find($id)->opened_at, 'non-matching passcode must not stamp');

        // A non-passcode unlock (admin remote / app / gateway) has no keyboardPwd.
        BookingLocker::recordCustomerUnlock($this->lockId, null, now());
        $this->assertNull(BookingLocker::find($id)->opened_at, 'passcode-less unlock must not stamp');
    }

    public function test_cancelled_booking_ignored(): void
    {
        $id = $this->makeBookingLocker('1234', 'cancelled');
        BookingLocker::recordCustomerUnlock($this->lockId, '1234', now());
        $this->assertNull(BookingLocker::find($id)->opened_at, 'cancelled booking must never flag as opened');
    }

    /**
     * Customers are invited to arrive early and the passcode is registered on the
     * lock with a ±TTLOCK_BUFFER_MINUTES buffer, so the keypad genuinely opens
     * before check_in. Those opens were being dropped — the booking was filtered
     * out before its passcode was compared — so the locker showed as never opened.
     */
    public function test_early_arrival_within_the_lock_buffer_is_stamped(): void
    {
        $id = $this->makeBookingLocker('1234');
        $checkIn = BookingLocker::find($id)->booking->check_in;

        // 15 minutes early — inside the 30-minute window the lock itself honours.
        $at = $checkIn->copy()->subMinutes(15);
        BookingLocker::recordCustomerUnlock($this->lockId, '1234', $at);

        $opened = BookingLocker::find($id)->opened_at;
        $this->assertNotNull($opened, 'an early arrival the lock accepted must still be recorded as opened');
        $this->assertSame($at->toDateTimeString(), $opened->toDateTimeString());
    }

    /** Beyond the lock's own buffer the passcode would not work, so nothing may be stamped. */
    public function test_unlock_long_before_check_in_is_not_stamped(): void
    {
        $id = $this->makeBookingLocker('1234');
        $checkIn = BookingLocker::find($id)->booking->check_in;

        BookingLocker::recordCustomerUnlock($this->lockId, '1234', $checkIn->copy()->subMinutes(120));

        $this->assertNull(
            BookingLocker::find($id)->opened_at,
            'an unlock far outside the passcode window must not be attributed to this booking'
        );
    }
}
