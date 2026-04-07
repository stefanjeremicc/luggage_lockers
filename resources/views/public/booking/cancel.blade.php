@extends('layouts.public')

@section('title', 'Cancel Booking — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24" x-data="{ reason: '', submitting: false, cancelled: false }">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div x-show="!cancelled">
            <h1 class="text-3xl font-bold mb-4">{{ __('Cancel Booking') }}</h1>
            <p class="text-[#A0A0A0] mb-8">{{ __('Are you sure you want to cancel your booking at') }} {{ $booking->location->name }}?</p>

            <div class="card mb-6">
                <div class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-[#A0A0A0]">{{ __('Check-in') }}</span>
                        <span>{{ $booking->check_in->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#A0A0A0]">{{ __('Lockers') }}</span>
                        <span>{{ $booking->locker_qty }} x {{ ucfirst($booking->locker_size->value) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#A0A0A0]">{{ __('Total') }}</span>
                        <span>&euro;{{ number_format($booking->total_eur, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm mb-2">{{ __('Reason for cancellation (optional)') }}</label>
                <textarea x-model="reason" class="input-field" rows="3" placeholder="{{ __('Tell us why...') }}"></textarea>
            </div>

            <div class="flex gap-4">
                <a href="{{ route($lp . 'booking.confirmation', ['uuid' => $booking->uuid]) }}" class="px-6 py-3 border border-[#2A2A2A] rounded-lg text-[#A0A0A0] hover:text-white transition">{{ __('Keep Booking') }}</a>
                <button @click="submitting = true; fetch('/api/bookings/{{ $booking->uuid }}/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ reason }) }).then(r => r.json()).then(() => { cancelled = true; submitting = false; })"
                        :disabled="submitting"
                        class="flex-1 px-6 py-3 bg-[#EF4444] text-white rounded-lg font-semibold hover:bg-red-600 transition">
                    <span x-show="!submitting">{{ __('Cancel Booking') }}</span>
                    <span x-show="submitting">{{ __('Cancelling...') }}</span>
                </button>
            </div>
        </div>

        <div x-show="cancelled" x-transition class="text-center">
            <h1 class="text-3xl font-bold mb-4">{{ __('Booking Cancelled') }}</h1>
            <p class="text-[#A0A0A0] mb-8">{{ __('Your booking has been cancelled. No charges will be made.') }}</p>
            <a href="{{ route($lp . 'home') }}" class="btn-primary">{{ __('Back to Home') }}</a>
        </div>
    </div>
</section>
@endsection
