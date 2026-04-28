@extends('layouts.public')

@section('title', 'Luggage Storage Locations in Belgrade — Belgrade Luggage Locker')
@section('meta_description', 'Find our luggage storage locations in Belgrade. 2 convenient spots open 24/7 with smart lockers.')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-center">{{ __('Our Locations') }}</h1>
        <p class="text-center text-[#A0A0A0] mt-4 max-w-2xl mx-auto">{{ __('Choose a convenient luggage storage location in Belgrade. All locations offer 24/7 access with smart lock technology.') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
            @foreach($locations as $location)
            <div class="rounded-2xl border border-[#2A2A2A] overflow-hidden bg-[#1A1A1A]">
                <div class="location-card-image">
                    <img src="/images/locations/{{ $location->slug }}.webp" alt="{{ $location->name }}">
                </div>

                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xl font-bold">{{ $location->name }}</h2>
                        @if($location->is_24h)
                        <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1 rounded-full font-medium flex-shrink-0">24/7</span>
                        @endif
                    </div>

                    <p class="text-[#A0A0A0] text-sm flex items-center gap-1.5 mb-3">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $location->address }}, {{ $location->city }}
                    </p>

                    <p class="text-[#A0A0A0] text-sm leading-relaxed mb-5 line-clamp-3">{{ Str::limit(app()->getLocale() === 'sr' ? $location->description_sr : $location->description, 140) }}</p>

                    <div class="flex items-center gap-3">
                        <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary flex-1 text-center inline-flex items-center justify-center gap-2">{{ __('Book Now') }} <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
                        <a href="{{ route($lp . 'locations.show', ['slug' => $location->slug]) }}" class="btn-outline flex-1 text-center">{{ __('View Details') }}</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Full-width Map --}}
<section class="border-t border-[#2A2A2A]">
    <div id="locations-map" class="w-full h-[450px]"></div>
</section>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @php $mc = \App\Helpers\SiteSettings::mapCenter(); @endphp
    var map = L.map('locations-map', { scrollWheelZoom: false }).setView([{{ $mc['lat'] }}, {{ $mc['lng'] }}], {{ $mc['zoom'] }});

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19
    }).addTo(map);

    var markerIcon = L.divIcon({
        html: '<div style="width:32px;height:32px;background:#F59E0B;border-radius:50%;border:3px solid #0A0A0A;box-shadow:0 2px 8px rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -36],
        className: ''
    });

    @foreach($locations as $location)
    L.marker([{{ $location->lat }}, {{ $location->lng }}], {icon: markerIcon})
        .addTo(map)
        .bindPopup(
            '<div class="popup-name">{{ $location->name }}</div>' +
            '<div class="popup-address">{{ $location->address }}, {{ $location->city }}</div>' +
            '<div class="popup-badge">{{ __('Open 24/7') }}</div>' +
            '<div class="popup-actions">' +
            '<a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="popup-btn">{{ __('Book Now') }}</a>' +
            '<a href="{{ $location->google_maps_url }}" target="_blank" class="popup-link">{{ __('Directions') }} &rarr;</a>' +
            '</div>',
            { autoClose: false, closeOnClick: false }
        ).openPopup();
    @endforeach
});
</script>
@endsection
