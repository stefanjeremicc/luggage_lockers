@extends('layouts.public')

@section('title', 'Booking Confirmed — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-16 h-16 rounded-full bg-[#10B981]/20 flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>

        <h1 class="text-3xl font-bold text-[#10B981]">{{ __('Booking Confirmed!') }}</h1>
        <p class="text-[#A0A0A0] mt-3">{{ __('You will receive your PIN code via email/WhatsApp shortly.') }}</p>

        <div class="card mt-8">
            <div class="py-4 text-sm space-y-3">
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Location') }}</span>
                    <span>{{ $booking->location->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Address') }}</span>
                    <span>{{ $booking->location->address }}, Belgrade</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Check-in') }}</span>
                    <span>{{ $booking->check_in->format('d M Y, h:i A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Check-out') }}</span>
                    <span>{{ $booking->check_out->format('d M Y, h:i A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Lockers') }}</span>
                    <span>{{ $booking->locker_qty }} x {{ ucfirst($booking->locker_size->value) }}</span>
                </div>
                @if($booking->lockers->count())
                <div class="flex justify-between">
                    <span class="text-[#A0A0A0]">{{ __('Locker Numbers') }}</span>
                    <span>{{ $booking->lockers->pluck('number')->join(', ') }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-lg border-t border-[#2A2A2A] pt-3">
                    <span>{{ __('Total') }}</span>
                    <span class="text-[#F59E0B]">&euro;{{ number_format($booking->total_eur, 2) }}</span>
                </div>
                <p class="text-xs text-[#A0A0A0]">{{ __('Pay cash on arrival') }}</p>
            </div>

            <div class="border-t border-[#2A2A2A] pt-4 flex flex-col sm:flex-row gap-3">
                @if($booking->location->google_maps_url)
                <a href="{{ $booking->location->google_maps_url }}" target="_blank" class="btn-primary flex-1 text-center">{{ __('Get Directions') }}</a>
                @endif
            </div>
        </div>

        <div class="mt-6 text-sm text-[#A0A0A0]">
            <p>{{ __('Confirmation sent to') }} {{ $booking->customer->email }}</p>
        </div>

        <div class="mt-4">
            <a href="{{ route($lp . 'booking.cancel', ['uuid' => $booking->uuid]) }}" class="text-sm text-[#A0A0A0] hover:text-[#EF4444] transition">{{ __('Need to cancel? Click here') }}</a>
        </div>
    </div>
</section>
@endsection
