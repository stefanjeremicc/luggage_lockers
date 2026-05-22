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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class BookingManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['customer', 'location', 'lockers', 'items'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('booking_status', $request->input('status'));
        }
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->has('date_from')) {
            $query->whereDate('check_in', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('check_in', '<=', $request->input('date_to'));
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->paginate(20);

        // Decorate each booking with decrypted PINs (admin-only endpoint).
        $paginator->getCollection()->transform(function ($booking) {
            $booking->setAttribute('pins', $this->decryptedPins($booking->id));
            return $booking;
        });

        return response()->json($paginator);
    }

    private function decryptedPins(int $bookingId): array
    {
        return BookingLocker::with('locker:id,number,size')
            ->where('booking_id', $bookingId)
            ->get()
            ->map(function ($bl) {
                try {
                    $pin = Crypt::decryptString($bl->pin_code_encrypted);
                } catch (\Throwable) {
                    $pin = null;
                }
                return [
                    'locker_number' => $bl->locker?->number,
                    'size' => $bl->locker?->size?->value,
                    'pin' => $pin,
                    'ttlock_registered' => !empty($bl->ttlock_keyboard_pwd_id),
                ];
            })
            ->values()
            ->all();
    }

    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['customer', 'location', 'lockers', 'items', 'notificationLogs'])->findOrFail($id);
        $booking->setAttribute('pins', $this->decryptedPins($booking->id));
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

        // Push the new end time to TTLock for every locker in this booking so passcodes
        // remain valid for the extended window.
        foreach (BookingLocker::where('booking_id', $booking->id)->whereNotNull('ttlock_keyboard_pwd_id')->get() as $bl) {
            try {
                app(\App\Services\Lock\LockServiceInterface::class)->updateAccessCodeTime(
                    $bl->locker->ttlock_lock_id,
                    $bl->ttlock_keyboard_pwd_id,
                    $newCheckOut
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
