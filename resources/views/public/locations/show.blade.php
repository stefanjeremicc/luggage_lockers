@extends('layouts.public')

@section('title', $location->meta_title ?? "Luggage Storage {$location->name} Belgrade")
@section('meta_description', $location->meta_description ?? "Secure luggage storage at {$location->name}, {$location->address}, Belgrade. 24/7 smart lockers. Book online from €5.")

@section('content')
<section class="py-8 lg:py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-[#A0A0A0] mb-8">
            <a href="{{ route($lp . 'home') }}" class="hover:text-white transition">{{ __('Home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route($lp . 'locations.index') }}" class="hover:text-white transition">{{ __('Locations') }}</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ $location->name }}</span>
        </nav>

        {{-- Hero Image --}}
        <div class="card p-0 overflow-hidden mb-8">
            <div class="relative h-56 sm:h-72">
                <img src="/images/locations/{{ $location->slug }}.webp" alt="{{ $location->name }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0A0A0A] via-[#0A0A0A]/40 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
                    <h1 class="text-3xl sm:text-4xl font-bold">{{ $location->name }}</h1>
                    <p class="text-[#A0A0A0] mt-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $location->address }}, {{ $location->city }}
                    </p>
                    <div class="mt-3 flex items-center gap-3">
                        @if($location->is_24h)
                        <span class="text-xs bg-[#10B981]/20 text-[#10B981] px-3 py-1 rounded-full font-medium">{{ __('Open 24/7') }}</span>
                        @endif
                        <span class="text-xs bg-[#F59E0B]/20 text-[#F59E0B] px-3 py-1 rounded-full font-medium">{{ $location->lockers_count }} {{ __('lockers available') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="card mb-8">
            <p class="text-[#A0A0A0] leading-relaxed">{{ app()->getLocale() === 'sr' ? $location->description_sr : $location->description }}</p>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div class="card">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ __('Contact') }}
                </h3>
                @if($location->phone)
                <a href="tel:{{ $location->phone }}" class="text-sm text-[#A0A0A0] hover:text-[#F59E0B] transition flex items-center gap-2 mb-2">
                    <span>{{ $location->phone }}</span>
                </a>
                @endif
                @if($location->whatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $location->whatsapp) }}" target="_blank" class="text-sm text-[#A0A0A0] hover:text-[#10B981] transition flex items-center gap-2 mb-2">
                    <span>WhatsApp: {{ $location->whatsapp }}</span>
                </a>
                @endif
                @if($location->email)
                <a href="mailto:{{ $location->email }}" class="text-sm text-[#A0A0A0] hover:text-[#F59E0B] transition">{{ $location->email }}</a>
                @endif
            </div>
            <div class="card">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    {{ __('Locker Sizes') }}
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="/images/lockers/standard.webp" alt="Standard" class="w-12 h-12 rounded-lg object-cover">
                        <div>
                            <p class="text-sm font-medium">{{ __('Standard') }}</p>
                            <p class="text-xs text-[#A0A0A0]">40&times;35&times;55 cm — {{ __('from') }} &euro;5/6h</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <img src="/images/lockers/large.webp" alt="Large" class="w-12 h-12 rounded-lg object-cover">
                        <div>
                            <p class="text-sm font-medium">{{ __('Large') }}</p>
                            <p class="text-xs text-[#A0A0A0]">60&times;45&times;75 cm — {{ __('from') }} &euro;10/6h</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($location->google_maps_url)
        <div class="mb-8">
            <a href="{{ $location->google_maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-[#F59E0B] hover:underline text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('View on Google Maps') }} &rarr;
            </a>
        </div>
        @endif

        <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary text-lg px-10 py-4 inline-flex">{{ __('Book a Locker') }}</a>
    </div>
</section>
@endsection
