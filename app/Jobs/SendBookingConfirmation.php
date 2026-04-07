<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::with(['customer', 'location', 'lockers'])->findOrFail($this->bookingId);

        // Send email (no PIN in phase 1 — client manages PINs via TTLock app)
        try {
            Mail::send('emails.booking-confirmed', [
                'booking' => $booking,
            ], function ($message) use ($booking) {
                $message->to($booking->customer->email, $booking->customer->full_name)
                    ->subject('Your Belgrade Luggage Locker Booking is Confirmed');
            });

            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'email',
                'template' => 'booking_confirmed',
                'recipient' => $booking->customer->email,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'email',
                'template' => 'booking_confirmed',
                'recipient' => $booking->customer->email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);
        }

        // Send WhatsApp if opted in
        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            $this->sendWhatsApp($booking);
        }
    }

    private function sendWhatsApp(Booking $booking): void
    {
        $token = config('services.whatsapp.api_token');
        $phoneId = config('services.whatsapp.phone_id');

        if (!$token || !$phoneId) return;

        try {
            $phone = preg_replace('/[^0-9]/', '', $booking->customer->phone);

            \Illuminate\Support\Facades\Http::withToken($token)
                ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'body' => "Your locker is booked!\n\n"
                            . "Location: {$booking->location->name}\n"
                            . "Address: {$booking->location->address}\n"
                            . "Check-in: {$booking->check_in->format('d M Y, h:i A')}\n"
                            . "Check-out: {$booking->check_out->format('d M Y, h:i A')}\n"
                            . "Total: EUR {$booking->total_eur} — Pay cash on arrival\n\n"
                            . "You will receive your PIN code shortly.\n\n"
                            . "Directions: {$booking->location->google_maps_url}",
                    ],
                ]);

            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'whatsapp',
                'template' => 'booking_confirmed',
                'recipient' => $phone,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'whatsapp',
                'template' => 'booking_confirmed',
                'recipient' => $booking->customer->phone,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }
}
