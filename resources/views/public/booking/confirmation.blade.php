@extends('layouts.public')

@php
    use App\Helpers\Dates;
    $tz = config('app.display_timezone');
    $checkIn = $booking->check_in->copy()->setTimezone($tz);
    $checkOut = $booking->check_out->copy()->setTimezone($tz);
    $city = \App\Helpers\SiteSettings::city();

    // Source of truth for the per-line breakdown is booking_items (one row per
    // size+duration). Old bookings backfilled at migration time also have items.
    $booking->loadMissing('items');
    $items = $booking->items;
    // All items share check_in/check_out today; once per-item durations land in
    // the UI this flag drives whether we render the date once or per line.
    $sameWindow = $items->count() <= 1 || $items->every(fn($it) =>
        $it->check_in->equalTo($items->first()->check_in)
        && $it->check_out->equalTo($items->first()->check_out)
    );

    // Security: PIN, entry door code, and specific locker number are intentionally NEVER
    // shown on this public page. They live only in the booking confirmation email.
    // The page is reachable by UUID alone and could be scraped or shared.
@endphp

@section('title', __('Booking Confirmed') . ' — ' . \App\Helpers\SiteSettings::siteName())

@push('schema')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<section class="py-8 sm:py-16 lg:py-24">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Success header --}}
        <div class="text-center mb-8">
            <div class="w-20 h-20 rounded-full bg-[#10B981]/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#10B981]">{{ __('Booking Confirmed!') }}</h1>
            <p class="text-[#A0A0A0] mt-3 text-sm leading-relaxed px-4">{{ __('You will receive your PIN code via email/WhatsApp shortly.') }}</p>
        </div>

        {{-- Email reminder banner (no codes shown — they're only in the email) --}}
        <div class="card !p-4 mb-6 flex items-start gap-3 border-[#F59E0B]/30 bg-[#F59E0B]/5">
            <svg class="w-5 h-5 text-[#F59E0B] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <div class="text-sm">
                <p class="text-white font-medium">{{ __('Check your inbox') }}</p>
                <p class="text-[#A0A0A0] text-xs mt-1 leading-relaxed">{{ __('We have sent your entry door code, locker PIN, and step-by-step instructions to your email and WhatsApp. They arrive within a minute.') }}</p>
            </div>
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
                        <p class="text-[#A0A0A0] text-xs">{{ $booking->location->address }}@if($city), {{ $city }}@endif</p>
                    </div>
                </div>

                @if($sameWindow)
                    {{-- All items share one window — render check-in/check-out once. --}}
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#10B981]/10 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div class="text-sm flex-1">
                            <p class="text-[10px] uppercase tracking-wider text-[#10B981] font-semibold">{{ __('Check-in') }}</p>
                            <p class="text-white font-medium mt-0.5">{{ Dates::dt($checkIn) }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#EF4444]/10 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-[#EF4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        </div>
                        <div class="text-sm flex-1">
                            <p class="text-[10px] uppercase tracking-wider text-[#EF4444] font-semibold">{{ __('Check-out') }}</p>
                            <p class="text-white font-medium mt-0.5">{{ Dates::dt($checkOut) }}</p>
                        </div>
                    </div>

                    {{-- Lockers — per-item breakdown with prices. --}}
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="text-sm flex-1 space-y-1.5">
                            <p class="text-[10px] uppercase tracking-wider text-[#F59E0B] font-semibold">{{ __('Lockers') }}</p>
                            @foreach($items as $it)
                                <div class="flex items-center justify-between">
                                    <p class="text-white font-medium">{{ $it->qty }} × {{ __(ucfirst($it->locker_size->value)) }}</p>
                                    <p class="text-[#A0A0A0] text-xs">&euro;{{ number_format($it->line_total_eur, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Mixed windows — render each item with its own date/time. --}}
                    <div class="space-y-3">
                        <p class="text-[10px] uppercase tracking-wider text-[#F59E0B] font-semibold">{{ __('Booking Details') }}</p>
                        @foreach($items as $it)
                            @php
                                $itIn = $it->check_in->copy()->setTimezone($tz);
                                $itOut = $it->check_out->copy()->setTimezone($tz);
                            @endphp
                            <div class="rounded-lg border border-[#2A2A2A] bg-[#0F0F0F] p-3 space-y-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-white font-medium text-sm">{{ $it->qty }} × {{ __(ucfirst($it->locker_size->value)) }}</p>
                                    <p class="text-[#F59E0B] font-semibold text-sm">&euro;{{ number_format($it->line_total_eur, 2) }}</p>
                                </div>
                                <p class="text-xs text-[#A0A0A0]">
                                    <span class="text-[#10B981]">{{ Dates::dt($itIn) }}</span>
                                    <span class="mx-1">→</span>
                                    <span class="text-[#EF4444]">{{ Dates::dt($itOut) }}</span>
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
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
            @php $cancelToken = hash_hmac('sha256', $booking->uuid.'|'.$booking->customer_id, config('app.key')); @endphp
            <a href="{{ route($lp . 'booking.cancel', ['uuid' => $booking->uuid]) }}?token={{ $cancelToken }}" class="text-xs text-[#A0A0A0] hover:text-[#EF4444] transition inline-block">{{ __('Need to cancel? Click here') }}</a>
        </div>
    </div>
</section>
@endsection
