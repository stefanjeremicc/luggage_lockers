@extends('layouts.public')

@section('title', __('Contact Us') . ' — Belgrade Luggage Locker')

@section('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-center mb-4">{{ __('Contact Us') }}</h1>
        <p class="text-center text-[#A0A0A0] mb-12">{{ __('Have a question? We\'re here to help.') }}</p>

        {{-- Contact Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">
            {{-- Phone --}}
            <div class="card text-center">
                <div class="w-12 h-12 rounded-full bg-[#F59E0B]/20 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">{{ __('Phone') }}</h3>
                <a href="tel:0642941503" class="text-[#A0A0A0] mt-2 block hover:text-[#F59E0B] transition-colors">
                    0642941503
                </a>
            </div>

            {{-- WhatsApp --}}
            <div class="card text-center">
                <div class="w-12 h-12 rounded-full bg-[#F59E0B]/20 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-[#F59E0B]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">{{ __('WhatsApp') }}</h3>
                <a href="https://wa.me/381653322319" target="_blank" class="text-[#A0A0A0] mt-2 block hover:text-[#F59E0B] transition-colors">
                    +381 65 332 2319
                </a>
            </div>

            {{-- Email --}}
            <div class="card text-center">
                <div class="w-12 h-12 rounded-full bg-[#F59E0B]/20 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">{{ __('Email') }}</h3>
                <a href="mailto:info@belgradeluggagelocker.com" class="text-[#A0A0A0] mt-2 block hover:text-[#F59E0B] transition-colors">
                    info@belgradeluggagelocker.com
                </a>
            </div>
        </div>

        {{-- Leaflet Map --}}
        <div class="mb-12">
            <div id="contact-map" class="h-96 rounded-lg border border-[#2A2A2A]"></div>
        </div>

        {{-- Location Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($locations as $location)
                <div class="card">
                    <h3 class="text-xl font-bold text-white mb-2">{{ $location->name }}</h3>
                    <p class="text-[#A0A0A0] mb-1">{{ $location->address }}, {{ $location->city }}</p>

                    @if($location->phone)
                        <p class="text-[#A0A0A0] mb-1">
                            {{ __('Phone') }}:
                            <a href="tel:{{ $location->phone }}" class="hover:text-[#F59E0B] transition-colors">{{ $location->phone }}</a>
                        </p>
                    @endif

                    @if($location->email)
                        <p class="text-[#A0A0A0] mb-1">
                            {{ __('Email') }}:
                            <a href="mailto:{{ $location->email }}" class="hover:text-[#F59E0B] transition-colors">{{ $location->email }}</a>
                        </p>
                    @endif

                    @if($location->is_24h)
                        <p class="text-[#F59E0B] text-sm mt-2">{{ __('Open 24 hours') }}</p>
                    @elseif($location->opening_time && $location->closing_time)
                        <p class="text-[#A0A0A0] text-sm mt-2">
                            {{ __('Hours') }}: {{ $location->opening_time }} &ndash; {{ $location->closing_time }}
                        </p>
                    @endif

                    @if($location->google_maps_url)
                        <a href="{{ $location->google_maps_url }}" target="_blank" rel="noopener"
                           class="btn-primary inline-block mt-4 text-center">
                            {{ __('Get Directions') }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('contact-map', {
                scrollWheelZoom: false
            }).setView([44.812, 20.460], 14);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                maxZoom: 19
            }).addTo(map);

            const markerIcon = L.divIcon({
                className: '',
                html: '<div style="width:32px;height:32px;background:#F59E0B;border-radius:50%;border:3px solid #0A0A0A;box-shadow:0 2px 8px rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -36]
            });

            const locations = @json($locations);

            // Fallback markers if no locations in DB
            const defaultMarkers = [
                {
                    lat: 44.8176,
                    lng: 20.4633,
                    name: 'City Center',
                    address: 'Kapetan Misina 2A, Belgrade',
                    google_maps_url: 'https://www.google.com/maps?q=44.8176,20.4633'
                },
                {
                    lat: 44.8076,
                    lng: 20.4644,
                    name: 'Kralja Milana',
                    address: 'Kralja Milana, Belgrade',
                    google_maps_url: 'https://www.google.com/maps?q=44.8076,20.4644'
                }
            ];

            const markerData = locations.length > 0
                ? locations.map(l => ({
                    lat: parseFloat(l.lat),
                    lng: parseFloat(l.lng),
                    name: l.name,
                    address: l.address + ', ' + l.city,
                    google_maps_url: l.google_maps_url || ('https://www.google.com/maps?q=' + l.lat + ',' + l.lng)
                }))
                : defaultMarkers;

            markerData.forEach(function (loc) {
                L.marker([loc.lat, loc.lng], { icon: markerIcon })
                    .addTo(map)
                    .bindPopup(
                        '<div class="popup-name">' + loc.name + '</div>' +
                        '<div class="popup-address">' + loc.address + '</div>' +
                        '<div class="popup-badge">Open 24/7</div>' +
                        '<div class="popup-actions">' +
                        '<a href="' + loc.google_maps_url + '" target="_blank" rel="noopener" class="popup-btn">{{ __("Get Directions") }}</a>' +
                        '</div>'
                    );
            });
        });
    </script>
@endsection
