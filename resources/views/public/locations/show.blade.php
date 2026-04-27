@extends('layouts.public')

@section('title', $location->meta_title ?? "Luggage Storage {$location->name} Belgrade")
@section('meta_description', $location->meta_description ?? "Secure luggage storage at {$location->name}, {$location->address}, Belgrade. 24/7 smart lockers. Book online from €5.")

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    .leaflet-container { background: #0A0A0A; font-family: inherit; border-radius: 1rem; }
    .leaflet-control-attribution { background: rgba(15,15,15,0.85) !important; color: #6B7280 !important; font-size: 10px !important; }
    .leaflet-control-attribution a { color: #A0A0A0 !important; }
    .leaflet-control-zoom a { background: #1A1A1A !important; color: #F59E0B !important; border-color: #2A2A2A !important; }
    .leaflet-control-zoom a:hover { background: #2A2A2A !important; }
</style>
@endsection

@section('content')
@php
    $description = app()->getLocale() === 'sr' ? ($location->description_sr ?: $location->description) : $location->description;
    $heroImage = $location->image_url ?: "/images/locations/{$location->slug}.webp";
@endphp
<section class="py-8 lg:py-14">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-[#A0A0A0] mb-6">
            <a href="{{ route($lp . 'home') }}" class="hover:text-white transition">{{ __('Home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route($lp . 'locations.index') }}" class="hover:text-white transition">{{ __('Locations') }}</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ $location->name }}</span>
        </nav>

        {{-- Hero --}}
        <div class="card p-0 overflow-hidden mb-8">
            <div class="relative h-64 sm:h-96">
                <img src="{{ $heroImage }}" alt="{{ $location->name }}"
                     class="w-full h-full object-cover"
                     onerror="this.style.background='linear-gradient(135deg,#1A1A1A,#0A0A0A)';this.style.display='block';this.removeAttribute('src');">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0A0A0A] via-[#0A0A0A]/40 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
                    <h1 class="text-3xl sm:text-5xl font-bold leading-tight">{{ $location->name }}</h1>
                    <p class="text-[#E0E0E0] mt-2 flex items-center gap-2 text-sm sm:text-base">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $location->address }}, {{ $location->city }}</span>
                    </p>
                    <div class="mt-4 flex items-center gap-2 flex-wrap">
                        @if($location->is_24h)
                        <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1.5 rounded-full font-medium border border-[#10B981]/30">{{ __('Open 24/7') }}</span>
                        @else
                        <span class="text-xs bg-[#F59E0B]/20 text-[#F59E0B] px-3 py-1.5 rounded-full font-medium border border-[#F59E0B]/30">
                            {{ $location->opening_time }} – {{ $location->closing_time }}
                        </span>
                        @endif
                        <span class="text-xs bg-[#1A1A1A]/80 backdrop-blur text-white px-3 py-1.5 rounded-full font-medium border border-[#2A2A2A]">
                            {{ $location->lockers_count }} {{ __('lockers') }}
                        </span>
                        @if($location->available_count > 0)
                        <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1.5 rounded-full font-medium border border-[#10B981]/30">
                            {{ $location->available_count }} {{ __('available now') }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Main column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Description --}}
                @if($description)
                <div class="card">
                    <h2 class="text-lg font-semibold mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('About this location') }}
                    </h2>
                    <div class="prose prose-invert max-w-none text-[#D0D0D0] leading-relaxed">{!! $description !!}</div>
                </div>
                @endif

                {{-- Map --}}
                <div class="card p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#2A2A2A] flex items-center justify-between">
                        <h2 class="font-semibold flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            {{ __('Find us') }}
                        </h2>
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->lat }},{{ $location->lng }}"
                           target="_blank" rel="noopener"
                           class="text-xs text-[#F59E0B] hover:underline flex items-center gap-1">
                            {{ __('Get directions') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                    <div id="locationMap" style="height: 360px;"></div>
                </div>

                {{-- Sizes --}}
                @if($sizes->count())
                <div class="card">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        {{ __('Available locker sizes') }}
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($sizes->has('standard'))
                        <div class="bg-[#111] border border-[#2A2A2A] rounded-xl p-4 flex items-center gap-3 hover:border-[#F59E0B]/40 transition">
                            <img src="/images/lockers/standard.webp" alt="Standard" class="w-16 h-16 rounded-lg object-cover shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold">{{ __('Standard') }}</p>
                                    <span class="text-[10px] uppercase tracking-wide bg-cyan-500/20 text-cyan-400 px-1.5 py-0.5 rounded">{{ $sizes['standard'] }}× {{ __('here') }}</span>
                                </div>
                                <p class="text-xs text-[#A0A0A0] mt-1">40×35×55 cm</p>
                                <p class="text-xs text-[#F59E0B] font-medium mt-0.5">{{ __('from') }} €5/6h</p>
                            </div>
                        </div>
                        @endif
                        @if($sizes->has('large'))
                        <div class="bg-[#111] border border-[#2A2A2A] rounded-xl p-4 flex items-center gap-3 hover:border-[#F59E0B]/40 transition">
                            <img src="/images/lockers/large.webp" alt="Large" class="w-16 h-16 rounded-lg object-cover shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold">{{ __('Large') }}</p>
                                    <span class="text-[10px] uppercase tracking-wide bg-fuchsia-500/20 text-fuchsia-400 px-1.5 py-0.5 rounded">{{ $sizes['large'] }}× {{ __('here') }}</span>
                                </div>
                                <p class="text-xs text-[#A0A0A0] mt-1">60×45×75 cm</p>
                                <p class="text-xs text-[#F59E0B] font-medium mt-0.5">{{ __('from') }} €10/6h</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Contact card --}}
                <div class="card sticky top-24">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#F59E0B] mb-4">{{ __('Contact') }}</h2>
                    <div class="space-y-3">
                        @if($location->phone)
                        <a href="tel:{{ $location->phone }}" class="flex items-center gap-3 p-3 rounded-lg bg-[#111] border border-[#2A2A2A] hover:border-[#F59E0B]/40 transition group">
                            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
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
                            <div class="w-10 h-10 rounded-lg bg-[#10B981]/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-[#10B981]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">WhatsApp</p>
                                <p class="text-sm font-medium text-white truncate">{{ $location->whatsapp }}</p>
                            </div>
                        </a>
                        @endif
                        @if($location->email)
                        <a href="mailto:{{ $location->email }}" class="flex items-center gap-3 p-3 rounded-lg bg-[#111] border border-[#2A2A2A] hover:border-[#F59E0B]/40 transition">
                            <div class="w-10 h-10 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-[#6B7280]">Email</p>
                                <p class="text-sm font-medium text-white truncate">{{ $location->email }}</p>
                            </div>
                        </a>
                        @endif
                    </div>

                    <div class="mt-5 pt-5 border-t border-[#2A2A2A]">
                        <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary w-full text-center block">
                            {{ __('Book a Locker Here') }}
                        </a>
                        <p class="text-xs text-[#A0A0A0] text-center mt-2">{{ __('From €5 — instant confirmation') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nearby --}}
        @if($nearbyLocations->count())
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">{{ __('Other locations nearby') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($nearbyLocations as $nearby)
                <a href="{{ route($lp . 'locations.show', $nearby->slug) }}" class="card hover:border-[#F59E0B] transition group block">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-lg group-hover:text-[#F59E0B] transition">{{ $nearby->name }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-[#F59E0B]/15 text-[#F59E0B]">{{ number_format($nearby->distance_km, 1) }} km</span>
                    </div>
                    <p class="text-sm text-[#A0A0A0]">{{ $nearby->address }}</p>
                    <p class="text-xs text-[#6B7280] mt-2">{{ $nearby->lockers_count }} {{ __('lockers available') }}</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

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
        html: '<div style="background:#F59E0B;border:3px solid #0A0A0A;width:22px;height:22px;border-radius:50%;box-shadow:0 0 0 4px rgba(245,158,11,0.35);"></div>',
        className: '', iconSize: [22, 22], iconAnchor: [11, 11],
    });
    L.marker([lat, lng], { icon }).addTo(map)
        .bindPopup('<div style="color:#fff;background:#1A1A1A;padding:6px 10px;border-radius:6px;font-weight:600;">{{ addslashes($location->name) }}</div>', { closeButton: false });
});
</script>
@endsection
