@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $name = $location->nameFor($locale);
    $address = $location->addressFor($locale);
    $city = $location->cityFor($locale);
    $description = $location->descriptionFor($locale);
    $heroImage = $location->image_url ?: "/images/locations/{$location->slug}.webp";
    $metaTitle = $location->metaTitleFor($locale) ?: ($name . ' — ' . __('Luggage Storage Belgrade'));
    $metaDescription = $location->metaDescriptionFor($locale) ?: __('Secure 24/7 luggage storage at :name, :address. Smart lockers, instant booking from €:price.', [
        'name' => $name, 'address' => $address, 'price' => 5,
    ]);
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDescription)

@section('head')
@if($location->og_image)
<meta property="og:image" content="{{ url($location->og_image) }}">
<meta name="twitter:image" content="{{ url($location->og_image) }}">
@endif
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .leaflet-container { background: #0A0A0A; font-family: inherit; }
    .leaflet-control-attribution { background: rgba(15,15,15,0.85) !important; color: #6B7280 !important; font-size: 10px !important; }
    .leaflet-control-attribution a { color: #A0A0A0 !important; }
    .leaflet-control-zoom a { background: #1A1A1A !important; color: #F59E0B !important; border-color: #2A2A2A !important; }
    .leaflet-control-zoom a:hover { background: #2A2A2A !important; }
</style>
@endsection

@section('content')
{{-- Breadcrumb --}}
<div class="border-b border-[#1A1A1A]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-sm text-[#A0A0A0]">
        <a href="{{ route($lp . 'home') }}" class="hover:text-white transition">{{ __('Home') }}</a>
        <span class="mx-2 text-[#3A3A3A]">/</span>
        <a href="{{ route($lp . 'locations.index') }}" class="hover:text-white transition">{{ __('Locations') }}</a>
        <span class="mx-2 text-[#3A3A3A]">/</span>
        <span class="text-white">{{ $name }}</span>
    </div>
</div>

{{-- Hero --}}
<section class="relative">
    <div class="absolute inset-0 h-[420px] sm:h-[480px]">
        <img src="{{ $heroImage }}" alt="{{ $name }}" class="w-full h-full object-cover"
             onerror="this.style.display='none';this.parentElement.style.background='linear-gradient(135deg,#1A1A1A,#0A0A0A)';">
        <div class="absolute inset-0 bg-gradient-to-t from-[#0A0A0A] via-[#0A0A0A]/70 to-[#0A0A0A]/30"></div>
    </div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 sm:pt-32 pb-10">
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @if($location->is_24h)
                <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1.5 rounded-full font-medium border border-[#10B981]/30 backdrop-blur">{{ __('Open 24/7') }}</span>
                @else
                <span class="text-xs bg-[#F59E0B]/20 text-[#F59E0B] px-3 py-1.5 rounded-full font-medium border border-[#F59E0B]/30 backdrop-blur">
                    {{ \Illuminate\Support\Str::of($location->opening_time)->limit(5, '') }} – {{ \Illuminate\Support\Str::of($location->closing_time)->limit(5, '') }}
                </span>
                @endif
                @if($location->lockers_count > 0)
                <span class="text-xs bg-black/50 text-white px-3 py-1.5 rounded-full font-medium border border-[#2A2A2A] backdrop-blur">
                    {{ $location->lockers_count }} {{ trans_choice('{1} :count locker|[2,*] :count lockers', $location->lockers_count, ['count' => $location->lockers_count]) }}
                </span>
                @endif
                @if($location->available_count > 0)
                <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1.5 rounded-full font-medium border border-[#10B981]/30 backdrop-blur">
                    {{ $location->available_count }} {{ __('available now') }}
                </span>
                @endif
            </div>
            <h1 class="text-4xl sm:text-6xl font-bold leading-tight">{{ $name }}</h1>
            <p class="text-[#E0E0E0] mt-3 flex items-center gap-2 text-base sm:text-lg">
                <svg class="w-5 h-5 shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ $address }}{{ $city ? ', ' . $city : '' }}</span>
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary inline-flex items-center gap-2">
                    {{ __('Book a Locker Here') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->lat }},{{ $location->lng }}"
                   target="_blank" rel="noopener"
                   class="btn-outline inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    {{ __('Get directions') }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Quick info strip --}}
<section class="bg-[#0F0F0F] border-y border-[#1A1A1A]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Hours') }}</p>
                <p class="text-sm font-medium">
                    @if($location->is_24h)
                        {{ __('24/7') }}
                    @else
                        {{ \Illuminate\Support\Str::of($location->opening_time)->limit(5, '') }} – {{ \Illuminate\Support\Str::of($location->closing_time)->limit(5, '') }}
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Security') }}</p>
                <p class="text-sm font-medium">{{ __('Smart locks + CCTV') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Payment') }}</p>
                <p class="text-sm font-medium">{{ __('Cash on arrival') }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Booking') }}</p>
                <p class="text-sm font-medium">{{ __('Instant — 60 seconds') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- Main content --}}
<section class="py-12 lg:py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main column --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Map (prominent) --}}
                <div class="card p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#2A2A2A] flex items-center justify-between">
                        <h2 class="font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            {{ __('Find us on the map') }}
                        </h2>
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->lat }},{{ $location->lng }}"
                           target="_blank" rel="noopener"
                           class="text-xs text-[#F59E0B] hover:underline flex items-center gap-1">
                            {{ __('Open in Google Maps') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                    <div id="locationMap" style="height: 420px;"></div>
                </div>

                {{-- About --}}
                @if($description)
                <div class="card">
                    <h2 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('About this location') }}
                    </h2>
                    <div class="prose prose-invert max-w-none text-[#D0D0D0] leading-relaxed">{!! $description !!}</div>
                </div>
                @endif

                {{-- Locker sizes --}}
                @if($sizes->count())
                <div class="card">
                    <h2 class="text-lg font-semibold mb-1 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        {{ __('Available locker sizes') }}
                    </h2>
                    <p class="text-sm text-[#A0A0A0] mb-5">{{ __('Pick the size that fits your luggage. Pay cash on arrival.') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach(['standard', 'large'] as $sizeKey)
                            @if($sizeSummary->has($sizeKey) && $sizeSummary[$sizeKey])
                                @php $info = $sizeSummary[$sizeKey]; @endphp
                                <div class="bg-[#111] border border-[#2A2A2A] rounded-xl overflow-hidden hover:border-[#F59E0B]/40 transition">
                                    <div class="aspect-[4/3] bg-[#0A0A0A] overflow-hidden">
                                        <img src="{{ $info->image }}" alt="{{ __(ucfirst($sizeKey)) }}" class="w-full h-full object-cover"
                                             onerror="this.style.display='none'">
                                    </div>
                                    <div class="p-4 space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-semibold text-base">{{ __(ucfirst($sizeKey)) }}</p>
                                            <span class="text-[10px] uppercase tracking-wide font-semibold {{ $sizeKey === 'standard' ? 'bg-cyan-500/20 text-cyan-400' : 'bg-fuchsia-500/20 text-fuchsia-400' }} px-2 py-0.5 rounded">
                                                {{ $info->count }} {{ __('here') }}
                                            </span>
                                        </div>
                                        @if($info->dimensions && is_array($info->dimensions))
                                        <p class="text-xs text-[#A0A0A0]">
                                            {{ $info->dimensions['width'] ?? '?' }}×{{ $info->dimensions['depth'] ?? '?' }}×{{ $info->dimensions['height'] ?? '?' }} cm
                                        </p>
                                        @endif
                                        @if($info->from_price)
                                        <p class="text-sm text-[#F59E0B] font-semibold">{{ __('from') }} €{{ $info->from_price }} / 6h</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-6">
                {{-- Booking CTA --}}
                <div class="card sticky top-24 bg-gradient-to-br from-[#1A1A1A] to-[#111] border-[#F59E0B]/20">
                    <p class="text-xs uppercase tracking-wide text-[#F59E0B] font-semibold">{{ __('Reserve in seconds') }}</p>
                    <h3 class="text-xl font-bold mt-2">{{ __('Drop your bags now') }}</h3>
                    <p class="text-sm text-[#A0A0A0] mt-2">{{ __('Book online, get a PIN, walk in. No paperwork.') }}</p>
                    <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary w-full text-center block mt-5">
                        {{ __('Book a Locker Here') }}
                    </a>
                    <p class="text-xs text-[#A0A0A0] text-center mt-3">{{ __('Pay cash on arrival') }}</p>
                </div>

                {{-- Contact --}}
                @if($location->phone || $location->whatsapp || $location->email)
                <div class="card">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#F59E0B] mb-4">{{ __('Contact this location') }}</h3>
                    <div class="space-y-2.5">
                        @if($location->phone)
                        <a href="tel:{{ $location->phone }}" class="flex items-center gap-3 p-3 rounded-lg bg-[#111] border border-[#2A2A2A] hover:border-[#F59E0B]/40 transition group">
                            <div class="w-9 h-9 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Phone') }}</p>
                                <p class="text-sm font-medium text-white truncate">{{ $location->phone }}</p>
                            </div>
                        </a>
                        @endif
                        @if($location->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $location->whatsapp) }}" target="_blank" rel="noopener"
                            class="flex items-center gap-3 p-3 rounded-lg bg-[#111] border border-[#2A2A2A] hover:border-[#10B981]/40 transition">
                            <div class="w-9 h-9 rounded-lg bg-[#10B981]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#10B981]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">WhatsApp</p>
                                <p class="text-sm font-medium text-white truncate">{{ $location->whatsapp }}</p>
                            </div>
                        </a>
                        @endif
                        @if($location->email)
                        <a href="mailto:{{ $location->email }}" class="flex items-center gap-3 p-3 rounded-lg bg-[#111] border border-[#2A2A2A] hover:border-[#F59E0B]/40 transition">
                            <div class="w-9 h-9 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">{{ __('Email') }}</p>
                                <p class="text-sm font-medium text-white truncate">{{ $location->email }}</p>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Address card --}}
                <div class="card">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-[#F59E0B] mb-3">{{ __('Address') }}</h3>
                    <p class="text-sm leading-relaxed">{{ $address }}<br>{{ $city }}</p>
                    @if($location->google_maps_url)
                    <a href="{{ $location->google_maps_url }}" target="_blank" rel="noopener"
                        class="text-xs text-[#F59E0B] hover:underline mt-3 inline-flex items-center gap-1">
                        {{ __('View on Google Maps') }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    @endif
                </div>
            </aside>
        </div>

        {{-- Nearby --}}
        @if($nearbyLocations->count())
        <div class="mt-16">
            <h2 class="text-2xl font-bold mb-6">{{ __('Other locations nearby') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($nearbyLocations as $nearby)
                <a href="{{ route($lp . 'locations.show', $nearby->slug) }}" class="card hover:border-[#F59E0B] transition group block">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-lg group-hover:text-[#F59E0B] transition">{{ $nearby->nameFor($locale) }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-[#F59E0B]/15 text-[#F59E0B]">{{ number_format($nearby->distance_km, 1) }} km</span>
                    </div>
                    <p class="text-sm text-[#A0A0A0]">{{ $nearby->addressFor($locale) }}</p>
                    <p class="text-xs text-[#6B7280] mt-2">{{ $nearby->lockers_count }} {{ __('lockers available') }}</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined' || !document.getElementById('locationMap')) return;
    const lat = {{ $location->lat }};
    const lng = {{ $location->lng }};
    const map = L.map('locationMap', { center: [lat, lng], zoom: 16, scrollWheelZoom: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 20,
    }).addTo(map);
    const icon = L.divIcon({
        html: '<div style="width:32px;height:32px;background:#F59E0B;border-radius:50%;border:3px solid #0A0A0A;box-shadow:0 2px 8px rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>',
        className: '', iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -36],
    });
    L.marker([lat, lng], { icon }).addTo(map)
        .bindPopup(
            '<div class="popup-name">{{ addslashes($name) }}</div>' +
            '<div class="popup-address">{{ addslashes($location->address) }}, {{ $location->city }}</div>' +
            '<div class="popup-badge">{{ __('Open 24/7') }}</div>',
            { autoClose: false, closeOnClick: false, closeButton: false }
        ).openPopup();
});
</script>
@endpush
@endsection
