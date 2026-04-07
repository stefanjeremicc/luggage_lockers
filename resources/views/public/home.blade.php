@extends('layouts.public')

@section('title', 'Belgrade Luggage Locker — 24/7 Secure Luggage Storage')
@section('meta_description', 'Secure 24/7 luggage storage in Belgrade city center. Smart lockers at 2 locations. Book online, pay cash on arrival. From €5.')

@section('content')
{{-- Hero --}}
<section class="relative min-h-[100svh] flex items-center justify-center overflow-hidden -mt-20">
    {{-- Background image --}}
    <div class="absolute inset-0">
        <img src="/images/hero-belgrade.webp" alt="" class="w-full h-full object-cover" loading="eager">
        <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/50 to-[#0A0A0A]"></div>
    </div>

    {{-- Content --}}
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-32 text-center">
        <div class="inline-flex items-center gap-2 bg-white/[0.08] backdrop-blur-sm border border-white/[0.12] rounded-full px-5 py-2 mb-8">
            <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
            <span class="text-sm text-white/80 font-medium tracking-wide">{{ __('Open 24/7 — 2 Locations') }}</span>
        </div>

        <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold leading-[1.08] tracking-tight">
            {{ __('Luggage Storage') }}<br>
            <span class="text-[#F59E0B]">{{ __('in Belgrade') }}</span>
        </h1>

        <p class="mt-6 text-lg sm:text-xl text-white/60 max-w-2xl mx-auto leading-relaxed">
            {{ __('Drop your bags, explore the city. 24/7 smart lockers at 2 locations. Book online in 60 seconds, pay cash on arrival.') }}
        </p>

        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-lg !px-8 !py-4 inline-flex items-center gap-2">
                {{ __('Book Now — From €5') }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#how-it-works" class="btn-outline text-lg !px-8 !py-4 !border-white/20">{{ __('How It Works') }}</a>
        </div>
    </div>

    {{-- Stats bar at bottom --}}
    <div class="absolute bottom-0 left-0 right-0 z-10 bg-[#111111]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-sm">
                <div class="flex items-center gap-2">
                    <div class="flex text-[#F59E0B]">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="text-[#A0A0A0]"><strong class="text-white">4.9</strong> on Google (70+ reviews)</span>
                </div>
                <div class="hidden sm:block w-px h-4 bg-[#2A2A2A]"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-[#A0A0A0]">{{ __('Secure & Monitored') }}</span>
                </div>
                <div class="hidden sm:block w-px h-4 bg-[#2A2A2A]"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-[#A0A0A0]">{{ __('Instant Booking') }}</span>
                </div>
                <div class="hidden sm:block w-px h-4 bg-[#2A2A2A]"></div>
                <div class="hidden sm:flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    <span class="text-[#A0A0A0]">{{ __('24/7 Access') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- How It Works --}}
<section id="how-it-works" class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-4">{{ __('How To Use Our Lockers') }}</h2>
        <p class="text-center text-[#A0A0A0] mb-12">{{ __('Simple, fast, and secure — in 4 easy steps') }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['step' => '1', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>', 'title' => __('Book Online or Scan QR'), 'desc' => __('Reserve your locker through our website. You\'ll receive a confirmation email with your locker number and access code.')],
                ['step' => '2', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>', 'title' => __('Arrive & Find Your Locker'), 'desc' => __('We\'re open 24/7 — come whenever it suits you. Locate the locker number from your confirmation email.')],
                ['step' => '3', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>', 'title' => __('Enter Your Code'), 'desc' => __('Use the keypad to enter your access code. The locker will unlock — store your luggage safely inside.')],
                ['step' => '4', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'title' => __('Done!'), 'desc' => __('Close the door and enjoy your day — your belongings are safe and secure.')],
            ] as $item)
            <div class="card text-center hover:border-[#F59E0B]/50 transition group">
                <div class="w-14 h-14 rounded-2xl bg-[#F59E0B]/10 text-[#F59E0B] flex items-center justify-center mx-auto group-hover:bg-[#F59E0B] group-hover:text-black transition-all duration-300">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                </div>
                <div class="mt-4 text-xs text-[#F59E0B] font-bold uppercase tracking-wider">{{ __('Step') }} {{ $item['step'] }}</div>
                <h3 class="mt-2 text-lg font-semibold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm text-[#A0A0A0]">{{ $item['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Locations --}}
<section class="bg-[#111111] py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-4">{{ __('Our Locations') }}</h2>
        <p class="text-center text-[#A0A0A0] mb-12">{{ __('2 convenient locations in Belgrade, open 24/7') }}</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach($locations as $location)
            <div class="rounded-2xl border border-[#2A2A2A] overflow-hidden bg-[#1A1A1A] hover:border-[#F59E0B] transition group">
                <div class="location-card-image">
                    <img src="/images/locations/{{ $location->slug }}.webp" alt="{{ $location->name }}" class="w-full h-full object-cover">
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xl font-bold group-hover:text-[#F59E0B] transition">{{ $location->name }}</h3>
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
                        <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary flex-1 text-center text-sm">{{ __('Book Now') }}</a>
                        <a href="{{ route($lp . 'locations.show', ['slug' => $location->slug]) }}" class="text-sm text-[#A0A0A0] hover:text-[#F59E0B] transition px-3">{{ __('View Details') }} &rarr;</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Pricing --}}
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-4">{{ __('Simple, Transparent Pricing') }}</h2>
        <p class="text-center text-[#A0A0A0] mb-12">{{ __('No hidden fees. Pay cash on arrival.') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {{-- Standard Locker --}}
            <div class="card overflow-hidden hover:border-[#F59E0B]/50 transition">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-[#111111]">
                        <img src="/images/lockers/standard.webp" alt="Standard Locker" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">{{ __('Standard Locker') }}</h3>
                        <p class="text-sm text-[#A0A0A0]">{{ __('1 suitcase & 1 bag') }}</p>
                        <p class="text-xs text-[#A0A0A0]">40 &times; 35 &times; 55 cm</p>
                    </div>
                </div>
                <div class="space-y-0 rounded-xl overflow-hidden border border-[#2A2A2A]">
                    @foreach([
                        ['Up to 6 hours', '€5'], ['24 hours', '€10'], ['2 days', '€18'],
                        ['3 days', '€25'], ['4 days', '€30'], ['5 days', '€35'],
                        ['1 week', '€50'],
                    ] as $i => $row)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm {{ $i % 2 === 0 ? 'bg-[#111111]' : 'bg-[#1A1A1A]' }}">
                        <span class="text-[#A0A0A0]">{{ $row[0] }}</span>
                        <span class="font-semibold text-[#F59E0B]">{{ $row[1] }}</span>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route($lp . 'pricing') }}" class="text-xs text-[#A0A0A0] hover:text-[#F59E0B] text-center block mt-3 transition">{{ __('View all durations') }} &rarr;</a>
                <a href="{{ route($lp . 'locations.index') }}" class="btn-primary w-full mt-3 text-center">{{ __('Book Standard') }}</a>
            </div>

            {{-- Large Locker --}}
            <div class="card overflow-hidden border-[#F59E0B]/50 hover:border-[#F59E0B] transition relative">
                <div class="absolute top-0 right-0 bg-[#F59E0B] text-black text-xs font-bold px-3 py-1 rounded-bl-lg">{{ __('Popular') }}</div>
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-[#111111]">
                        <img src="/images/lockers/large.webp" alt="Large Locker" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">{{ __('Large Locker') }}</h3>
                        <p class="text-sm text-[#A0A0A0]">{{ __('2 suitcases & 1 bag') }}</p>
                        <p class="text-xs text-[#A0A0A0]">60 &times; 45 &times; 75 cm</p>
                    </div>
                </div>
                <div class="space-y-0 rounded-xl overflow-hidden border border-[#2A2A2A]">
                    @foreach([
                        ['Up to 6 hours', '€10'], ['24 hours', '€15'], ['2 days', '€27'],
                        ['3 days', '€38'], ['4 days', '€45'], ['5 days', '€52'],
                        ['1 week', '€75'],
                    ] as $i => $row)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm {{ $i % 2 === 0 ? 'bg-[#111111]' : 'bg-[#1A1A1A]' }}">
                        <span class="text-[#A0A0A0]">{{ $row[0] }}</span>
                        <span class="font-semibold text-[#F59E0B]">{{ $row[1] }}</span>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route($lp . 'pricing') }}" class="text-xs text-[#A0A0A0] hover:text-[#F59E0B] text-center block mt-3 transition">{{ __('View all durations') }} &rarr;</a>
                <a href="{{ route($lp . 'locations.index') }}" class="btn-primary w-full mt-3 text-center">{{ __('Book Large') }}</a>
            </div>
        </div>
    </div>
</section>

{{-- Reviews --}}
<section class="bg-[#111111] py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">{{ __('What People Say About Us') }}</h2>
            <div class="flex items-center justify-center gap-3">
                <div class="flex text-[#F59E0B]">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <span class="text-lg font-bold">4.9</span>
                <span class="text-[#A0A0A0]">{{ __('based on') }} <a href="https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJq3Y86Jl7WkcRJRP0r-8Tg5M" target="_blank" class="text-[#F59E0B] hover:underline">70+ {{ __('Google reviews') }}</a></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="reviewsCarousel">
            @foreach([
                ['name' => 'Lusine Titanyan', 'text' => 'Thank you for service, it\'s really help us and the most important things it was safe. Highly recommended!', 'rating' => 5],
                ['name' => 'Taylor Hanson', 'text' => 'Highly recommend to store your luggage here! I had a 5 hour layover and wanted to explore the city. Super easy booking and very secure.', 'rating' => 5],
                ['name' => 'Rossana Reyna', 'text' => 'The service is excellent, it\'s very easy and the location is great!! Would definitely use again on my next visit.', 'rating' => 5],
                ['name' => 'Ali Canakci', 'text' => 'That was safe and easy. They communicated so well and gave instant response. Perfect for travelers passing through Belgrade.', 'rating' => 5],
                ['name' => 'Damjan Poposki', 'text' => 'Great experience! Wonderful location, clear instructions, safe and clean space. Exactly what you need when exploring the city.', 'rating' => 5],
                ['name' => 'Maria K.', 'text' => 'Used it twice already. Best luggage storage option in Belgrade. Very responsive on WhatsApp and lockers are super clean.', 'rating' => 5],
            ] as $review)
            <div class="card hover:border-[#F59E0B]/30 transition">
                <div class="flex text-[#F59E0B] mb-3">
                    @for($i = 0; $i < $review['rating']; $i++)
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-[#A0A0A0] leading-relaxed mb-4">"{{ $review['text'] }}"</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-[#F59E0B]/20 text-[#F59E0B] flex items-center justify-center text-sm font-bold">{{ substr($review['name'], 0, 1) }}</div>
                    <div>
                        <p class="text-sm font-semibold">{{ $review['name'] }}</p>
                        <p class="text-xs text-[#A0A0A0]">Google Review</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJq3Y86Jl7WkcRJRP0r-8Tg5M" target="_blank" rel="noopener" class="text-[#F59E0B] hover:underline text-sm">{{ __('See all reviews on Google') }} &rarr;</a>
        </div>
    </div>
</section>

{{-- FAQ Preview --}}
@if($faqs->count())
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">{{ __('Frequently Asked Questions') }}</h2>
        <div class="space-y-3">
            @foreach($faqs as $faq)
            <div class="card !py-0 overflow-hidden" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-left py-5 px-6">
                    <span class="font-medium pr-4">{{ $faq->question }}</span>
                    <svg class="w-5 h-5 text-[#F59E0B] transition-transform duration-300 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-5 text-[#A0A0A0] text-sm leading-relaxed border-t border-[#2A2A2A]">
                    <p class="pt-4">{{ $faq->answer }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-8">
            <a href="{{ route($lp . 'faq') }}" class="text-[#F59E0B] hover:underline">{{ __('View All FAQs') }} &rarr;</a>
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section class="bg-[#111111] py-16 lg:py-24 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#F59E0B] rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-[#F59E0B] rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold">{{ __('Ready to Explore Belgrade?') }}</h2>
        <p class="mt-4 text-lg text-[#A0A0A0]">{{ __('Drop your bags and enjoy the city hands-free. Book in 60 seconds.') }}</p>
        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-lg px-10 py-4 mt-8 inline-block">{{ __('Book Your Locker') }}</a>
    </div>
</section>
@endsection
