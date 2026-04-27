@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $poiName = $locale === 'sr' ? ($poi['name_sr'] ?? $poi['name']) : $poi['name'];
    $title = $locale === 'sr'
        ? 'Garderoba blizu '.$poiName.' — Belgrade Luggage Locker'
        : 'Luggage Storage Near '.$poiName.' — Belgrade Luggage Locker';
@endphp

@section('title', $title)
@section('meta_description', $poi['description'])

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-xs text-[#A0A0A0] mb-4">
            <a href="{{ route(($locale === 'sr' ? 'sr.' : '').'home') }}" class="hover:text-[#F59E0B]">{{ __('Home') }}</a>
            <span class="mx-2">·</span>
            <span>{{ $locale === 'sr' ? 'Blizu' : 'Near' }} {{ $poiName }}</span>
        </nav>

        <h1 class="text-4xl font-bold mb-4">
            {{ $locale === 'sr' ? 'Garderoba blizu ' : 'Luggage storage near ' }}{{ $poiName }}
        </h1>
        <p class="text-lg text-[#A0A0A0] mb-10">{{ $poi['description'] }}</p>

        <h2 class="text-2xl font-bold mb-6">{{ $locale === 'sr' ? 'Najbliže lokacije' : 'Nearest locations' }}</h2>

        <div class="space-y-4">
            @forelse($locations as $loc)
                <a href="{{ route(($locale === 'sr' ? 'sr.' : '').'locations.show', $loc->slug) }}"
                   class="card flex items-start justify-between gap-4 hover:border-[#F59E0B] transition">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">{{ $loc->name }}</h3>
                        <p class="text-sm text-[#A0A0A0] mt-1">{{ $loc->address }}, {{ $loc->city }}</p>
                        <div class="text-xs text-[#A0A0A0] mt-2">
                            {{ $loc->is_24h ? '24/7' : ($loc->opening_time.' – '.$loc->closing_time) }}
                        </div>
                    </div>
                    @if(isset($loc->distance_km))
                        <div class="text-right shrink-0">
                            <div class="text-[#F59E0B] font-bold">{{ number_format($loc->distance_km, 1) }} km</div>
                            <div class="text-xs text-[#A0A0A0]">{{ $locale === 'sr' ? 'vazdušna linija' : 'as the crow flies' }}</div>
                        </div>
                    @endif
                </a>
            @empty
                <p class="text-[#A0A0A0]">{{ __('No active locations yet.') }}</p>
            @endforelse
        </div>

        <div class="mt-12 card">
            <h3 class="text-lg font-semibold text-white mb-2">{{ $locale === 'sr' ? 'Kako rezervisati' : 'How to book' }}</h3>
            <p class="text-sm text-[#A0A0A0]">
                {{ $locale === 'sr'
                    ? 'Izaberi lokaciju, rezerviši online za 60 sekundi i plati gotovinom po dolasku.'
                    : 'Pick a location, book online in 60 seconds, and pay cash on arrival.' }}
            </p>
        </div>
    </div>
</section>
@endsection
