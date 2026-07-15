<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Jobs\CreateTTLockAccessCode;
use App\Jobs\DeleteTTLockAccessCode;
use App\Jobs\SendBookingConfirmation;
use App\Models\BookingLocker;
use App\Services\Booking\BookingService;
use App\Services\Lock\TtlockQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class BookingManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $now = now();
        $query = Booking::with(['customer', 'location', 'lockers', 'items']);

        // Time-based tab filter — computed from check_in/check_out so "active" is
        // always accurate without relying on a stale booking_status column.
        $tab = $request->input('tab', 'active');
        switch ($tab) {
            case 'completed': // Završeno — finished (completed + expired merged)
                $query->where('booking_status', '!=', BookingStatus::Cancelled)
                      ->where('check_out', '<', $now);
                break;
            case 'cancelled':
                $query->where('booking_status', BookingStatus::Cancelled);
                break;
            case 'all':
                break;
            case 'active': // Active & Upcoming — running now + not started yet (default)
            case 'upcoming': // legacy alias, treated the same
            default:
                // Active (check_in <= now) and upcoming (check_in > now) merged.
                // Ordered by check_in asc below → active rows (earlier check_in)
                // naturally sort ahead of upcoming ones.
                $query->where('booking_status', '!=', BookingStatus::Cancelled)
                      ->where('check_out', '>=', $now);
                break;
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // Sorting — an explicit column click overrides the per-tab default order.
        $dir = strtolower((string) $request->input('dir')) === 'desc' ? 'desc' : 'asc';
        $sort = $request->input('sort');
        $directCols = [
            'number' => 'booking_number', 'period' => 'check_in',
            'total' => 'total_eur', 'status' => 'booking_status',
            'payment' => 'payment_status', 'type' => 'locker_size',
        ];
        if (isset($directCols[$sort])) {
            $query->orderBy($directCols[$sort], $dir);
        } elseif ($sort === 'customer') {
            $query->leftJoin('customers', 'bookings.customer_id', '=', 'customers.id')
                  ->orderBy('customers.full_name', $dir)->select('bookings.*');
        } elseif ($sort === 'location') {
            $query->leftJoin('locations', 'bookings.location_id', '=', 'locations.id')
                  ->orderBy('locations.name', $dir)->select('bookings.*');
        } else {
            // Per-tab default order. Within each day we order by booking
            // CREATION time (newest first) — not by the check-in clock time.
            $nowStr = $now->toDateTimeString();
            if ($tab === 'all') {
                $query->orderByDesc('created_at');
            } elseif ($tab === 'completed') {
                // Most recently finished day first; newest-booked first within a day.
                $query->orderByRaw('DATE(check_out) desc')->orderByDesc('created_at');
            } elseif ($tab === 'cancelled') {
                $query->orderByRaw('DATE(cancelled_at) desc')->orderByDesc('created_at');
            } else {
                // Active & Upcoming: all ACTIVE first, then UPCOMING; by day
                // (today, tomorrow, …); newest-booked first within each day.
                $query->orderByRaw('(check_in <= ?) desc', [$nowStr]) // active before upcoming
                      ->orderByRaw('DATE(check_in) asc')               // by day
                      ->orderByDesc('created_at');                     // newest booking first
            }
        }

        $perPageRaw = $request->input('per_page', 20);
        $perPage = ($perPageRaw === 'all') ? 100000 : max(1, min(1000, (int) $perPageRaw));
        $paginator = $query->paginate($perPage);

        // Decorate: decrypted PINs + a time-computed display status.
        $paginator->getCollection()->transform(function ($booking) use ($now) {
            $booking->setAttribute('pins', $this->decryptedPins($booking));
            $booking->setAttribute('display_status', $this->displayStatus($booking, $now));
            return $booking;
        });

        return response()->json($paginator);
    }

    /** Effective status from time (cancelled is explicit; the rest is computed). */
    private function displayStatus(Booking $b, $now): string
    {
        if ($b->booking_status === BookingStatus::Cancelled) return 'cancelled';
        if ($b->check_out && $b->check_out < $now) return 'completed';
        if ($b->check_in && $b->check_in <= $now) return 'active';
        return 'confirmed';
    }

    private function decryptedPins(Booking $booking): array
    {
        // "opened" reflects the CUSTOMER'S OWN PIN opening this locker, stamped on
        // booking_lockers.opened_at by the TTLock webhook / SyncUnlockRecords job
        // (matched by passcode). Admin remote-unlock, gateway/app opens and
        // master/staff codes no longer count — they carry no matching customer PIN.
        return BookingLocker::with('locker:id,number,size')
            ->where('booking_id', $booking->id)
            ->get()
            ->map(function ($bl) {
                try {
                    $pin = Crypt::decryptString($bl->pin_code_encrypted);
                } catch (\Throwable) {
                    $pin = null;
                }
                return [
                    'booking_locker_id' => $bl->id,
                    'locker_number' => $bl->locker?->number,
                    'size' => $bl->locker?->size?->value,
                    'pin' => $pin,
                    'ttlock_registered' => !empty($bl->ttlock_keyboard_pwd_id),
                    'opened' => !is_null($bl->opened_at),
                    'opened_at' => $bl->opened_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['customer', 'location', 'lockers', 'items', 'notificationLogs'])->findOrFail($id);
        $booking->setAttribute('pins', $this->decryptedPins($booking));
        return response()->json($booking);
    }

    public function previewNotification(int $id, int $logId)
    {
        $log = \App\Models\NotificationLog::where('booking_id', $id)->findOrFail($logId);
        $payload = $log->payload ?: [];

        if ($log->channel === 'email' && !empty($payload['body_html'])) {
            return response($payload['body_html'])->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if ($log->channel === 'whatsapp' && !empty($payload['body_text'])) {
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>WhatsApp preview</title>'.
                '<style>body{background:#0A0A0A;color:#fff;font-family:system-ui;padding:24px}'.
                '.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:20px;max-width:480px;margin:0 auto;white-space:pre-wrap;line-height:1.6}'.
                '.meta{color:#A0A0A0;font-size:13px;margin-bottom:12px;text-align:center}'.
                '</style></head><body>'.
                '<p class="meta">📱 WhatsApp · '.e($log->recipient).' · '.e($log->template).'</p>'.
                '<div class="card">'.e($payload['body_text']).'</div>'.
                '</body></html>';
            return response($body)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response('<p style="font-family:system-ui;padding:24px">No preview available — this notification was logged before email/WhatsApp body capture was enabled.</p>', 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function reissuePin(int $id, BookingService $service): JsonResponse
    {
        $booking = Booking::with('lockers')->findOrFail($id);
        $reissued = 0;

        // Permanent-PIN mode (TTLock quota exhausted): re-set each locker's
        // permanent code locally, no TTLock calls, nothing deleted.
        if (TtlockQuota::isExceeded()) {
            foreach (BookingLocker::with('locker')->where('booking_id', $booking->id)->get() as $bl) {
                $pp = $bl->locker?->permanent_pin;
                if ($pp) {
                    $bl->update(['pin_code_encrypted' => Crypt::encryptString($pp), 'ttlock_keyboard_pwd_id' => null]);
                    $reissued++;
                }
            }
            $fresh = $booking->fresh(['customer', 'location', 'bookingLockers.locker']);
            try { \App\Services\Notification\BookingNotifier::sendPinReissued($fresh); } catch (\Throwable $e) {}
            try { \App\Services\Notification\BookingNotifier::sendPinReissuedAdmin($fresh); } catch (\Throwable $e) {}
            return response()->json(['message' => "Re-sent permanent PIN for {$reissued} locker(s) (TTLock quota mode)."]);
        }

        foreach (BookingLocker::where('booking_id', $booking->id)->get() as $bl) {
            // 1. Delete the old TTLock keyboard password BEFORE overwriting it locally —
            //    otherwise it stays valid on the lock indefinitely (security hole).
            if ($bl->ttlock_keyboard_pwd_id) {
                \App\Jobs\DeleteTTLockAccessCode::dispatchSync($bl->id);
            }
            // 2. Generate + persist new PIN, clear stale TTLock id so the create-job
            //    knows to register fresh.
            $pin = $service->generatePin();
            $bl->update([
                'pin_code_encrypted' => Crypt::encryptString($pin),
                'ttlock_keyboard_pwd_id' => null,
            ]);
            // 3. Register the new PIN on the lock synchronously so the email we
            //    send right after carries the live PIN, not the stale one.
            try {
                \App\Jobs\CreateTTLockAccessCode::dispatchSync($bl->id);
            } catch (\Throwable $e) {
                \Log::error('Sync TTLock create during reissue failed; falling back to queue', [
                    'booking_locker_id' => $bl->id, 'error' => $e->getMessage(),
                ]);
                \App\Jobs\CreateTTLockAccessCode::dispatch($bl->id);
            }
            $reissued++;
        }

        // 4. Dispatch the customer-facing "your PIN has changed" email so they
        //    know the previous code is dead. Synchronous so admin can verify
        //    the send in the same admin click round-trip.
        $fresh = $booking->fresh(['customer', 'location', 'bookingLockers.locker']);
        try {
            \App\Services\Notification\BookingNotifier::sendPinReissued($fresh);
        } catch (\Throwable $e) {
            \Log::error('pin_reissued send failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        // 5. Admin-side compact "PIN reissued" notification (separate from the
        //    customer-facing email so admin gets the operational facts, not the
        //    styled card).
        try {
            \App\Services\Notification\BookingNotifier::sendPinReissuedAdmin($fresh);
        } catch (\Throwable $e) {
            \Log::error('pin_reissued_admin send failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => "Re-issued PIN for {$reissued} locker(s). Customer has been notified."]);
    }

    public function markPaid(int $id): JsonResponse
    {
        // Toggle: paid <-> pending. Dashboard revenue counts payment_status='paid',
        // so flipping here updates the stats automatically.
        $booking = Booking::findOrFail($id);
        $isPaid = $booking->payment_status === PaymentStatus::Paid;
        $booking->update([
            'payment_status' => $isPaid ? PaymentStatus::Pending : PaymentStatus::Paid,
            'paid_at' => $isPaid ? null : now(),
        ]);
        return response()->json([
            'message' => $isPaid ? 'Marked as unpaid' : 'Marked as paid',
            'payment_status' => $booking->payment_status->value,
        ]);
    }

    public function extend(Request $request, int $id): JsonResponse
    {
        $request->validate(['duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month']);

        $booking = Booking::with('lockers')->findOrFail($id);
        $service = app(\App\Services\Booking\AvailabilityService::class);
        $newCheckOut = $service->calculateCheckOut($booking->check_out, $request->input('duration'));

        // Conflict check: any other active booking on the same locker(s) overlapping the
        // extension window must block this. Without this an admin can quietly double-book.
        $lockerIds = $booking->lockers->pluck('id');
        $conflict = \App\Models\BookingLocker::whereIn('locker_id', $lockerIds)
            ->where('booking_id', '!=', $booking->id)
            ->whereHas('booking', function ($q) use ($booking, $newCheckOut) {
                $q->whereIn('booking_status', [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Active])
                    ->where('check_in', '<', $newCheckOut)
                    ->where('check_out', '>', $booking->check_out);
            })->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Cannot extend — another booking is scheduled on this locker during the extended window.',
            ], 409);
        }

        $booking->update(['check_out' => $newCheckOut]);

        // Mirror the new end on every booking_item so per-item windows stay in sync
        // with the booking-level shift. Without this, mixed-duration bookings would
        // diverge between booking.check_out and item.check_out.
        $booking->items()->update(['check_out' => $newCheckOut]);

        // Push the new end time to TTLock for every locker in this booking so passcodes
        // remain valid for the extended window.
        foreach (BookingLocker::with('bookingItem')->where('booking_id', $booking->id)->whereNotNull('ttlock_keyboard_pwd_id')->get() as $bl) {
            $itemEnd = $bl->bookingItem?->check_out ?: $newCheckOut;
            try {
                app(\App\Services\Lock\LockServiceInterface::class)->updateAccessCodeTime(
                    $bl->locker->ttlock_lock_id,
                    $bl->ttlock_keyboard_pwd_id,
                    $itemEnd
                );
            } catch (\Throwable $e) {
                \Log::warning('Failed to extend TTLock passcode end-date', ['booking_locker' => $bl->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'Booking extended', 'new_check_out' => $newCheckOut]);
    }

    public function destroy(int $id, BookingService $service): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $service->cancel($booking, 'Cancelled by admin', 'admin');
        return response()->json(['message' => 'Booking cancelled']);
    }

    /**
     * Admin edit for a booking: times, customer, pricing, notes, attribution.
     * Status-changing flows (cancel / extend / reissue PIN / mark paid) stay as
     * their own dedicated actions.
     */
    public function edit(Request $request, int $id, BookingService $service): JsonResponse
    {
        // Phase A scope: customer (name/email/phone) + times + duration_label +
        // notes. Other fields (pricing, attribution, locker/location reassign)
        // are intentionally NOT exposed here yet — they require more careful UX
        // and availability/TTLock handling.
        $data = $request->validate([
            'check_in'           => 'nullable|string',
            'check_out'          => 'nullable|string',
            'duration_label'     => 'nullable|string|max:50',
            'customer'           => 'nullable|array',
            'customer.full_name' => 'nullable|string|max:120',
            'customer.email'     => 'nullable|email|max:160',
            'customer.phone'     => 'nullable|string|max:40',
            'notes'              => 'nullable|string|max:5000',
            'notify'             => 'sometimes|boolean',
        ]);

        $booking = Booking::with('customer', 'items', 'bookingLockers.locker')->findOrFail($id);

        try {
            $result = $service->edit($booking, $data, $data['notify'] ?? false, 'admin');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $count = count($result['changes']);
        return response()->json([
            'message' => $count
                ? "Booking updated ({$count} field(s) changed)" . (($data['notify'] ?? false) ? ' — customer notified.' : '.')
                : 'No changes.',
            'changes' => array_keys($result['changes']),
        ]);
    }

    /**
     * Remove specific locker(s) from a booking, keeping the rest active. If the
     * admin selects every locker, this performs a full cancellation instead.
     * The actual logic lives in BookingService::removeLockers() (transactional,
     * permanent/one-time aware, scoped strictly to this booking).
     */
    public function removeLockers(Request $request, int $id, BookingService $service): JsonResponse
    {
        $data = $request->validate([
            'booking_locker_ids'   => 'required|array|min:1',
            'booking_locker_ids.*' => 'integer',
            'notify'               => 'sometimes|boolean',
        ]);

        $booking = Booking::findOrFail($id);

        try {
            $result = $service->removeLockers(
                $booking,
                $data['booking_locker_ids'],
                'admin',
                $data['notify'] ?? true
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $message = $result['action'] === 'cancelled'
            ? 'All lockers removed — booking cancelled.'
            : "Removed {$result['removed']} locker(s). The remaining lockers stay active.";

        return response()->json(['message' => $message, 'action' => $result['action']]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $deletable = [
            BookingStatus::Cancelled->value,
            BookingStatus::Completed->value,
            BookingStatus::Expired->value,
        ];
        $current = $booking->booking_status instanceof BookingStatus
            ? $booking->booking_status->value
            : $booking->booking_status;

        if (!in_array($current, $deletable, true)) {
            return response()->json([
                'message' => 'Only cancelled, completed or expired bookings can be deleted. Cancel it first.',
            ], 422);
        }

        // Sweep any TTLock passwords that might still be live before we throw away the
        // pivot rows that hold their keyboard_pwd_id references.
        foreach (BookingLocker::where('booking_id', $booking->id)->whereNotNull('ttlock_keyboard_pwd_id')->pluck('id') as $blId) {
            DeleteTTLockAccessCode::dispatch($blId);
        }

        $booking->notificationLogs()->delete();
        $booking->lockers()->detach();
        $booking->delete();

        return response()->json(['message' => 'Booking deleted']);
    }

    public function resendConfirmation(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        // Run synchronously so the admin's "Resent" toast is truthful — if the
        // SMTP call fails the response carries the error instead of silently
        // queueing a job nobody watches.
        try {
            SendBookingConfirmation::dispatchSync($booking->id);
            return response()->json(['message' => 'Confirmation resent to '.$booking->customer->email]);
        } catch (\Throwable $e) {
            \Log::error('Resend confirmation failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Resend failed: '.$e->getMessage()], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $bookings = Booking::with(['customer', 'location'])
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get()
            ->map(fn($b) => [
                'uuid' => $b->uuid,
                'customer' => $b->customer->full_name,
                'email' => $b->customer->email,
                'location' => $b->location->name,
                'locker_size' => $b->locker_size->value,
                'locker_qty' => $b->locker_qty,
                'check_in' => $b->check_in->copy()->setTimezone(config('app.display_timezone'))->format('Y-m-d H:i'),
                'check_out' => $b->check_out->copy()->setTimezone(config('app.display_timezone'))->format('Y-m-d H:i'),
                'total_eur' => $b->total_eur,
                'booking_status' => $b->booking_status->value,
                'payment_status' => $b->payment_status->value,
                'created_at' => $b->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json($bookings);
    }
}
