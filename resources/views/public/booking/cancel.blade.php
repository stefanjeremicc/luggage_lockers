@extends('layouts.public')

@php
    use App\Helpers\Dates;
    $tz = config('app.display_timezone');
    $checkIn = $booking->check_in->copy()->setTimezone($tz);
    $checkOut = $booking->check_out->copy()->setTimezone($tz);
    $items = $booking->items;
    $sameWindow = $items->count() <= 1 || $items->every(fn($it) =>
        $it->check_in->equalTo($items->first()->check_in)
        && $it->check_out->equalTo($items->first()->check_out)
    );

    $status = $booking->booking_status->value;
    $isCancelled = $status === 'cancelled';
    // Once check_in is in the past we don't allow self-service cancel — a no-show
    // by then is operational, not a customer-initiated cancellation.
    $isPastCheckIn = $booking->check_in->isPast();
    $canCancel = $authorized && !$isCancelled && !$isPastCheckIn;

    $statusLabels = [
        'pending' => __('Pending'),
        'confirmed' => __('Confirmed'),
        'active' => __('Active'),
        'completed' => __('Completed'),
        'cancelled' => __('Cancelled'),
        'expired' => __('Expired'),
    ];
@endphp

@section('title', __('Cancel Booking') . ' — ' . \App\Helpers\SiteSettings::siteName())

@push('schema')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<section class="py-10 sm:py-16 lg:py-24" x-data="{ reason: '', submitting: false, cancelled: false, error: '' }">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Already-cancelled state --}}
        @if($isCancelled)
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-[#A0A0A0]/15 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#A0A0A0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ __('Booking Cancelled') }}</h1>
                <p class="text-[#A0A0A0] text-sm">{{ __('This booking has already been cancelled.') }}</p>
            </div>
        @elseif($isPastCheckIn)
            <div class="text-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ __('Booking') }}</h1>
                <p class="text-[#A0A0A0] text-sm">{{ __('Self-service cancellation is no longer available — please contact us.') }}</p>
            </div>
        @elseif(!$authorized)
            <div class="text-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ __('Cancel Booking') }}</h1>
                <p class="text-[#A0A0A0] text-sm">{{ __('Use the cancel link from your confirmation email or WhatsApp message.') }}</p>
            </div>
        @else
            <div x-show="!cancelled">
                <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ __('Cancel Booking') }}</h1>
                <p class="text-[#A0A0A0] text-sm mb-6">{{ __('Are you sure you want to cancel your booking at') }} <span class="text-white">{{ $booking->location->name }}</span>?</p>
                <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg border border-[#EF4444]/50 bg-[#EF4444]/10 text-[#EF4444] text-sm" x-text="error"></div>
            </div>
        @endif

        {{-- Booking details — always visible --}}
        <div class="card !p-0 overflow-hidden mb-6">
            <div class="bg-[#111111] px-5 py-3 border-b border-[#2A2A2A] flex items-center justify-between">
                <p class="text-xs uppercase tracking-wider text-[#A0A0A0] font-semibold">{{ __('Booking Details') }}</p>
                <span class="text-[10px] uppercase tracking-wider px-2 py-1 rounded-full
                    @if($isCancelled) bg-[#A0A0A0]/15 text-[#A0A0A0]
                    @elseif($status === 'confirmed') bg-[#10B981]/15 text-[#10B981]
                    @elseif($status === 'active') bg-[#F59E0B]/15 text-[#F59E0B]
                    @else bg-[#2A2A2A] text-[#A0A0A0] @endif">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </div>

            <div class="px-5 py-4 text-sm space-y-3">
                <div class="flex justify-between gap-3">
                    <span class="text-[#A0A0A0] shrink-0">{{ __('Reference') }}</span>
                    <span class="font-mono text-xs text-white text-right break-all">{{ strtoupper(substr($booking->uuid, 0, 8)) }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[#A0A0A0] shrink-0">{{ __('Customer') }}</span>
                    <span class="text-white text-right">{{ $booking->customer->full_name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[#A0A0A0] shrink-0">{{ __('Location') }}</span>
                    <span class="text-white text-right">{{ $booking->location->name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[#A0A0A0] shrink-0">{{ __('Address') }}</span>
                    <span class="text-white/80 text-right text-xs">{{ $booking->location->address }}</span>
                </div>

                @if($sameWindow)
                    <div class="flex justify-between gap-3">
                        <span class="text-[#A0A0A0] shrink-0">{{ __('Check-in') }}</span>
                        <span class="text-white text-right">{{ Dates::dt($checkIn) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-[#A0A0A0] shrink-0">{{ __('Check-out') }}</span>
                        <span class="text-white text-right">{{ Dates::dt($checkOut) }}</span>
                    </div>
                @endif
            </div>

            {{-- Items --}}
            <div class="border-t border-[#2A2A2A] px-5 py-4 space-y-2">
                <p class="text-[10px] uppercase tracking-wider text-[#F59E0B] font-semibold mb-2">{{ __('Lockers') }}</p>
                @foreach($items as $it)
                    @php
                        $itIn = $it->check_in->copy()->setTimezone($tz);
                        $itOut = $it->check_out->copy()->setTimezone($tz);
                    @endphp
                    <div class="rounded-lg bg-[#0F0F0F] border border-[#2A2A2A] p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-white font-medium">{{ $it->qty }} × {{ __(ucfirst($it->locker_size->value)) }}</span>
                            <span class="text-[#F59E0B] font-semibold">&euro;{{ number_format($it->line_total_eur, 2) }}</span>
                        </div>
                        @if(!$sameWindow)
                            <p class="text-xs text-[#A0A0A0] mt-1">
                                <span class="text-[#10B981]">{{ Dates::dt($itIn) }}</span>
                                <span class="mx-1">→</span>
                                <span class="text-[#EF4444]">{{ Dates::dt($itOut) }}</span>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Total --}}
            <div class="border-t border-[#2A2A2A] px-5 py-4 flex items-center justify-between">
                <span class="text-sm text-[#A0A0A0]">{{ __('Total') }}</span>
                <span class="text-xl font-bold text-[#F59E0B]">&euro;{{ number_format($booking->total_eur, 2) }}</span>
            </div>
        </div>

        {{-- Action buttons — only visible when cancellation is actually possible --}}
        @if($canCancel)
            <div x-show="!cancelled">
                <div class="mb-5">
                    <label class="block text-xs uppercase tracking-wider text-[#A0A0A0] font-semibold mb-2">{{ __('Reason for cancellation (optional)') }}</label>
                    <textarea x-model="reason" class="input-field" rows="3" placeholder="{{ __('Tell us why...') }}"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route($lp . 'booking.confirmation', ['uuid' => $booking->uuid]) }}"
                       class="px-4 py-3 border border-[#2A2A2A] rounded-lg text-[#A0A0A0] hover:text-white hover:border-[#3A3A3A] transition text-center text-sm font-medium">
                        {{ __('Keep Booking') }}
                    </a>
                    <button type="button"
                            @click="submitting = true; error = ''; fetch('/api/bookings/{{ $booking->uuid }}/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ reason, token: '{{ $cancelToken }}' }) }).then(async r => { const d = await r.json().catch(() => ({})); if (r.ok) { cancelled = true; } else { error = d.message || '{{ __('Cancellation failed. Please try again.') }}'; } submitting = false; }).catch(() => { error = '{{ __('Network error. Please try again.') }}'; submitting = false; })"
                            :disabled="submitting"
                            class="px-4 py-3 bg-[#EF4444] text-white rounded-lg font-semibold hover:bg-red-600 transition text-sm disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!submitting">{{ __('Cancel Booking') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Cancelling...') }}</span>
                    </button>
                </div>
            </div>

            <div x-show="cancelled" x-cloak x-transition class="text-center mt-4">
                <div class="w-14 h-14 rounded-full bg-[#10B981]/15 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-[#10B981]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h2 class="text-xl font-bold mb-2">{{ __('Booking Cancelled') }}</h2>
                <p class="text-[#A0A0A0] text-sm mb-6">{{ __('Your booking has been cancelled. No charges will be made.') }}</p>
                <a href="{{ route($lp . 'home') }}" class="btn-primary inline-block">{{ __('Back to Home') }}</a>
            </div>
        @else
            {{-- Read-only footer for cancelled / past / unauthorized states --}}
            <div class="text-center">
                <a href="{{ route($lp . 'home') }}" class="btn-primary inline-block">{{ __('Back to Home') }}</a>
            </div>
        @endif
    </div>
</section>
@endsection
