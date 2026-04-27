@extends('layouts.public')

@section('title', 'Booking Confirmed — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Success header --}}
        <div class="text-center mb-10">
            <div class="w-16 h-16 rounded-full bg-[#10B981]/15 flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#10B981]">{{ __('Booking Confirmed!') }}</h1>
            <p class="text-[#A0A0A0] mt-2 text-sm">{{ __('You will receive your PIN code via email/WhatsApp shortly.') }}</p>
        </div>

        {{-- Booking details card --}}
        <div class="card !p-0 overflow-hidden mb-6">
            <div class="bg-[#111111] px-5 sm:px-6 py-4 border-b border-[#2A2A2A]">
                <p class="text-xs uppercase tracking-wider text-[#A0A0A0] font-semibold">{{ __('Booking Details') }}</p>
            </div>

            <div class="px-5 sm:px-6 py-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="text-sm">
                        <p class="text-white font-medium">{{ $booking->location->name }}</p>
                        <p class="text-[#A0A0A0] text-xs">{{ $booking->location->address }}, Belgrade</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-sm">
                        <p class="text-white font-medium">{{ $booking->check_in->format('D, d M Y') }}</p>
                        <p class="text-[#A0A0A0] text-xs">{{ $booking->check_in->format('h:i A') }} — {{ $booking->check_out->format('D, d M Y h:i A') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="text-sm">
                        <p class="text-white font-medium">{{ $booking->locker_qty }} x {{ ucfirst($booking->locker_size->value) }}</p>
                        @if($booking->lockers->count())
                            <p class="text-[#A0A0A0] text-xs">{{ __('Locker') }} #{{ $booking->lockers->pluck('number')->join(', #') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Total --}}
            <div class="border-t border-[#2A2A2A] px-5 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-[#A0A0A0]">{{ __('Total — pay on arrival') }}</p>
                        <p class="text-xl font-bold text-[#F59E0B] mt-0.5">&euro;{{ number_format($booking->total_eur, 2) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
            </div>

            {{-- Directions --}}
            @if($booking->location->google_maps_url)
            <div class="border-t border-[#2A2A2A] px-5 sm:px-6 py-4">
                <a href="{{ $booking->location->google_maps_url }}" target="_blank" class="btn-primary w-full text-center inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ __('Get Directions') }}
                </a>
            </div>
            @endif
        </div>

        {{-- Footer info --}}
        <div class="text-center space-y-3">
            <p class="text-sm text-[#A0A0A0]">{{ __('Confirmation sent to') }} <span class="text-white">{{ $booking->customer->email }}</span></p>
            <a href="{{ route($lp . 'booking.cancel', ['uuid' => $booking->uuid]) }}" class="text-xs text-[#A0A0A0] hover:text-[#EF4444] transition inline-block">{{ __('Need to cancel? Click here') }}</a>
        </div>
    </div>
</section>
@endsection
