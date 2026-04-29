@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $metaTitleKey = $locale === 'sr' ? 'home_meta_title_sr' : 'home_meta_title';
    $metaDescKey = $locale === 'sr' ? 'home_meta_description_sr' : 'home_meta_description';
    $homePage = \App\Models\Page::seoFor('home', $locale);
    $heroHeadline = $homePage?->section('hero.title') ?: \App\Helpers\SiteSettings::heroHeadline($locale);
    $heroSubhead = $homePage?->section('hero.subtitle') ?: \App\Helpers\SiteSettings::heroSubhead($locale);
    $heroImage = $homePage?->section('hero.image') ?: \App\Helpers\SiteSettings::heroImage();
    $heroCtaPrimary = $homePage?->section('hero.cta_primary');
    $heroCtaSecondary = $homePage?->section('hero.cta_secondary');
    $locationsCount = $locations->count();
    $minPriceEur = \App\Models\PricingRule::active()->min('price_eur');
@endphp

@section('title', $settings[$metaTitleKey] ?? ($settings['home_meta_title'] ?? 'Belgrade Luggage Locker — 24/7 Secure Luggage Storage'))
@section('meta_description', $settings[$metaDescKey] ?? ($settings['home_meta_description'] ?? ''))

@section('content')
{{-- Hero --}}
<section class="hero-section relative flex items-center justify-center overflow-hidden -mt-20">
    {{-- Background image --}}
    <div class="absolute inset-0">
        <img src="{{ $heroImage }}" alt="" class="w-full h-full object-cover" loading="eager">
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-[#0A0A0A]"></div>
    </div>

    {{-- Content --}}
    <div class="relative z-10 max-w-4xl mx-auto px-6 sm:px-6 lg:px-8 pt-24 pb-28 sm:pt-28 sm:pb-32 text-center">
        <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold leading-[1.15] sm:leading-[1.08] tracking-tight">
            @if($heroHeadline)
                {!! nl2br(e($heroHeadline)) !!}
            @else
                {{ __('Luggage Storage') }}<br>
                <span class="text-[#F59E0B]">{{ __('in Belgrade') }}</span>
            @endif
        </h1>

        <p class="mt-6 sm:mt-6 text-base sm:text-xl text-white/60 max-w-2xl mx-auto leading-relaxed">
            {{ $heroSubhead ?: __('Drop your bags, explore the city. 24/7 smart lockers at :count locations. Book online in 60 seconds, pay cash on arrival.', ['count' => $locationsCount]) }}
        </p>

        <div class="mt-10 sm:mt-10 flex flex-row items-center justify-center gap-3 sm:gap-4">
            <a href="{{ route($lp . 'locations.index') }}" class="btn-primary flex-1 sm:flex-none inline-flex items-center justify-center gap-2">
                <span class="sm:hidden">{{ $heroCtaPrimary ?: __('Book Now') }}</span>
                <span class="hidden sm:inline">{{ $heroCtaPrimary ?: __('Book Now') }}{{ $minPriceEur ? ' — '.__('From').' €'.rtrim(rtrim(number_format($minPriceEur, 2), '0'), '.') : '' }}</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#how-it-works" data-scroll-to="how-it-works" class="btn-outline flex-1 sm:flex-none text-center">{{ $heroCtaSecondary ?: __('How It Works') }}</a>
        </div>
    </div>

    {{-- Stats bar at bottom — marquee on mobile, static on desktop --}}
    <div class="absolute bottom-0 left-0 right-0 z-10 bg-[#111111] border-t border-[#2A2A2A]">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-4 sm:py-5 overflow-hidden">
            {{-- Desktop: static --}}
            <div class="hidden sm:flex flex-wrap items-center justify-center gap-10 text-sm">
                <div class="flex items-center gap-2">
                    <div class="flex text-[#F59E0B]">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="text-[#A0A0A0]"><strong class="text-white">{{ $settings['google_rating'] ?? '4.9' }}</strong> {{ __('on Google') }} ({{ $settings['google_review_count'] ?? '70+' }} {{ __('reviews') }})</span>
                </div>
                <div class="w-px h-4 bg-[#2A2A2A]"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="text-[#A0A0A0]">{{ __('Secure & Monitored') }}</span>
                </div>
                <div class="w-px h-4 bg-[#2A2A2A]"></div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-[#A0A0A0]">{{ __('Instant Booking') }}</span>
                </div>
                <div class="w-px h-4 bg-[#2A2A2A]"></div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    <span class="text-[#A0A0A0]">{{ __('24/7 Access') }}</span>
                </div>
            </div>

            {{-- Mobile: marquee scroll --}}
            <div class="sm:hidden hero-marquee">
                <div class="hero-marquee-track text-sm">
                    @for($m = 0; $m < 2; $m++)
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="flex text-[#F59E0B]">
                            @for($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <span class="text-[#A0A0A0] whitespace-nowrap"><strong class="text-white">{{ $settings['google_rating'] ?? '4.9' }}</strong> {{ __('on Google') }}</span>
                    </div>
                    <span class="shrink-0 mx-4 w-px h-3.5 bg-[#2A2A2A]"></span>
                    <div class="flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="text-[#A0A0A0] whitespace-nowrap">{{ __('Secure & Monitored') }}</span>
                    </div>
                    <span class="shrink-0 mx-4 w-px h-3.5 bg-[#2A2A2A]"></span>
                    <div class="flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span class="text-[#A0A0A0] whitespace-nowrap">{{ __('Instant Booking') }}</span>
                    </div>
                    <span class="shrink-0 mx-4 w-px h-3.5 bg-[#2A2A2A]"></span>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                        <span class="text-[#A0A0A0] whitespace-nowrap">{{ __('24/7 Access') }}</span>
                    </div>
                    <span class="shrink-0 mx-4 w-px h-3.5 bg-[#2A2A2A]"></span>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</section>

{{-- How It Works --}}
<section id="how-it-works" class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $hiwTitle = $home?->section('how_it_works_heading.title') ?: __('How To Use Our Lockers');
            $hiwSub = $home?->section('how_it_works_heading.subtitle') ?: __('Simple, fast, and secure — in 4 easy steps');
        @endphp
        <h2 class="text-3xl font-bold text-center mb-4">{{ $hiwTitle }}</h2>
        <p class="text-center text-[#A0A0A0] mb-14">{{ $hiwSub }}</p>
        @php
            $stepIcons = [
                'computer' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
                'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
                'smile' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            ];
            $steps = is_array($howItWorksSteps) && count($howItWorksSteps) ? $howItWorksSteps : [
                ['icon' => 'computer', 'title' => 'Book Online or Scan QR', 'desc' => 'Reserve your locker through our website. You\'ll receive a confirmation email with your locker number and access code.'],
                ['icon' => 'search', 'title' => 'Arrive & Find Your Locker', 'desc' => 'We\'re open 24/7 — come whenever it suits you. Locate the locker number from your confirmation email.'],
                ['icon' => 'lock', 'title' => 'Enter Your Code', 'desc' => 'Use the keypad to enter your access code. The locker will unlock — store your luggage safely inside.'],
                ['icon' => 'smile', 'title' => 'Done!', 'desc' => 'Close the door and enjoy your day — your belongings are safe and secure.'],
            ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
            @foreach($steps as $item)
            <div class="step-card group">
                <div class="step-card-accent"></div>
                <div class="p-6">
                    <div class="step-card-icon mb-5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $stepIcons[$item['icon']] ?? $stepIcons['computer'] !!}</svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-2">{{ __($item['title']) }}</h3>
                    <p class="text-sm text-[#A0A0A0] leading-relaxed">{{ __($item['desc']) }}</p>
                </div>
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
                        <a href="{{ route($lp . 'booking.index', ['slug' => $location->slug]) }}" class="btn-primary flex-1 text-center inline-flex items-center justify-center gap-2">{{ __('Book Now') }} <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></a>
                        <a href="{{ route($lp . 'locations.show', ['slug' => $location->slug]) }}" class="btn-outline flex-1 text-center">{{ __('View Details') }}</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Pricing --}}
<section class="py-16 lg:py-24">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-14">{{ __('Pricing') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Standard Locker --}}
            <div class="pricing-card">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ __('Standard Locker') }}</h3>
                            @php $stdInfo = \App\Helpers\SiteSettings::lockerInfo('standard'); @endphp
                            <p class="text-sm text-[#A0A0A0] mt-1">{{ $stdInfo['capacity'] ?: __('1 suitcase & 1 bag') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-[#F59E0B]">&euro;{{ isset($pricingRules['standard']) ? intval($pricingRules['standard']->first()->price_eur) : '5' }}</span>
                            <p class="text-xs text-[#A0A0A0]">{{ __('from / 6h') }}</p>
                        </div>
                    </div>

                    <div class="pricing-table">
                        @if(isset($pricingRules['standard']) && $pricingRules['standard']->count())
                            @foreach($pricingRules['standard'] as $rule)
                            <div class="pricing-table-row">
                                <span>{{ __($rule->label) }}</span>
                                <span class="font-semibold">&euro;{{ intval($rule->price_eur) }}</span>
                            </div>
                            @endforeach
                        @else
                            @foreach([
                                ['Up to 6 hours', '€5'], ['24 hours', '€10'], ['2 days', '€18'],
                                ['3 days', '€25'], ['4 days', '€30'], ['5 days', '€35'],
                                ['1 week', '€50'], ['2 weeks', '€85'], ['1 month', '€150'],
                            ] as $row)
                            <div class="pricing-table-row">
                                <span>{{ __($row[0]) }}</span>
                                <span class="font-semibold">{{ $row[1] }}</span>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-center">{{ __('Book Standard') }}</a>
                        @if($stdInfo['dimensions'])<span class="text-xs text-[#A0A0A0]">{{ $stdInfo['dimensions'] }}</span>@endif
                    </div>
                </div>
            </div>

            {{-- Large Locker --}}
            <div class="pricing-card pricing-card--featured">
                <div class="pricing-card-badge">{{ __('Popular') }}</div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ __('Large Locker') }}</h3>
                            @php $lrgInfo = \App\Helpers\SiteSettings::lockerInfo('large'); @endphp
                            <p class="text-sm text-[#A0A0A0] mt-1">{{ $lrgInfo['capacity'] ?: __('2 suitcases & 1 bag') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-[#F59E0B]">&euro;{{ isset($pricingRules['large']) ? intval($pricingRules['large']->first()->price_eur) : '10' }}</span>
                            <p class="text-xs text-[#A0A0A0]">{{ __('from / 6h') }}</p>
                        </div>
                    </div>

                    <div class="pricing-table">
                        @if(isset($pricingRules['large']) && $pricingRules['large']->count())
                            @foreach($pricingRules['large'] as $rule)
                            <div class="pricing-table-row">
                                <span>{{ __($rule->label) }}</span>
                                <span class="font-semibold">&euro;{{ intval($rule->price_eur) }}</span>
                            </div>
                            @endforeach
                        @else
                            @foreach([
                                ['Up to 6 hours', '€10'], ['24 hours', '€15'], ['2 days', '€27'],
                                ['3 days', '€38'], ['4 days', '€45'], ['5 days', '€52'],
                                ['1 week', '€75'], ['2 weeks', '€120'], ['1 month', '€200'],
                            ] as $row)
                            <div class="pricing-table-row">
                                <span>{{ __($row[0]) }}</span>
                                <span class="font-semibold">{{ $row[1] }}</span>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-center">{{ __('Book Large') }}</a>
                        @if($lrgInfo['dimensions'])<span class="text-xs text-[#A0A0A0]">{{ $lrgInfo['dimensions'] }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Reviews --}}
<section class="bg-[#111111] py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">{{ __('What People Say About Us') }}</h2>
            {{-- Stars wrap above text on small screens so the line doesn't squish.
                 Single column on mobile, inline on sm+. --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-3 text-center">
                <div class="flex items-center gap-2">
                    <div class="flex text-[#F59E0B]">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <span class="text-lg font-bold">{{ $settings['google_rating'] ?? '4.9' }}</span>
                </div>
                <span class="text-sm sm:text-base text-[#A0A0A0]">{{ __('based on') }} <a href="{{ $settings['google_reviews_url'] ?? '#' }}" target="_blank" class="text-[#F59E0B] hover:underline">{{ $settings['google_review_count'] ?? '70+' }} {{ __('Google reviews') }}</a></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="reviewsCarousel">
            @foreach($reviews as $review)
            <div class="card hover:border-[#F59E0B]/30 transition">
                <div class="flex text-[#F59E0B] mb-3">
                    @for($i = 0; $i < $review->rating; $i++)
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-sm text-[#A0A0A0] leading-relaxed mb-4">"{{ app()->getLocale() === 'sr' && !empty($review->text_sr) ? $review->text_sr : $review->text }}"</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-[#F59E0B]/20 text-[#F59E0B] flex items-center justify-center text-sm font-bold">{{ $review->avatar_letter ?? strtoupper(substr($review->name, 0, 1)) }}</div>
                    <div>
                        <p class="text-sm font-semibold">{{ $review->name }}</p>
                        <p class="text-xs text-[#A0A0A0]">{{ ucfirst($review->source) }} {{ __('Review') }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ $settings['google_reviews_url'] ?? '#' }}" target="_blank" rel="noopener" class="text-[#F59E0B] hover:underline text-sm">{{ __('See all reviews on Google') }} &rarr;</a>
        </div>

        {{-- Review submit modal --}}
        <div x-data="reviewSubmitForm()"
             x-on:open-review-modal.window="open = true"
             x-show="open"
             x-cloak
             x-transition.opacity
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
             @click.self="open = false">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-2xl shadow-2xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto"
                 x-transition.scale.95>
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold">{{ __('Leave a review') }}</h3>
                        <p class="text-sm text-[#A0A0A0] mt-1">{{ __('Share your experience with our luggage storage.') }}</p>
                    </div>
                    <button type="button" @click="open = false" class="text-[#A0A0A0] hover:text-white p-1 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div x-show="success" class="bg-[#10B981]/15 border border-[#10B981]/30 text-[#10B981] rounded-lg p-4 text-sm">
                    <p class="font-semibold mb-1">{{ __('Thank you!') }}</p>
                    <p class="text-[#A0A0A0]">{{ __('Your review will appear after a quick check.') }}</p>
                </div>

                <form x-show="!success" @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium block mb-1">{{ __('Your name') }} <span class="text-[#EF4444]">*</span></label>
                        <input x-model="form.name" required minlength="2" maxlength="80"
                               class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none text-sm">
                    </div>

                    <div>
                        <label class="text-sm font-medium block mb-1">{{ __('Email') }} <span class="text-[#6B7280] text-xs">({{ __('optional, not shown') }})</span></label>
                        <input x-model="form.email" type="email" maxlength="255"
                               class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none text-sm">
                    </div>

                    <div>
                        <label class="text-sm font-medium block mb-1">{{ __('Rating') }} <span class="text-[#EF4444]">*</span></label>
                        <div class="flex gap-1">
                            <template x-for="n in 5" :key="n">
                                <button type="button" @click="form.rating = n"
                                        class="text-3xl leading-none cursor-pointer transition"
                                        :class="n <= form.rating ? 'text-[#F59E0B]' : 'text-[#3A3A3A] hover:text-[#6B7280]'">
                                    ★
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium block mb-1">{{ __('Your review') }} <span class="text-[#EF4444]">*</span></label>
                        <textarea x-model="form.text" required minlength="10" maxlength="1000" rows="4"
                                  :placeholder="'{{ __('Tell us about your experience…') }}'"
                                  class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none text-sm resize-none"></textarea>
                        <p class="text-[10px] text-[#6B7280] mt-1" x-text="form.text.length + ' / 1000'"></p>
                    </div>

                    {{-- Honeypot --}}
                    <input type="text" x-model="form.website" tabindex="-1" autocomplete="off"
                           style="position:absolute;left:-9999px" aria-hidden="true">

                    <p x-show="error" x-text="error" class="text-sm text-[#EF4444]"></p>

                    <div class="flex gap-2 justify-end pt-2">
                        <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-lg border border-[#2A2A2A] text-[#A0A0A0] hover:text-white hover:border-[#3A3A3A] transition cursor-pointer">{{ __('Cancel') }}</button>
                        <button type="submit" :disabled="submitting"
                                class="px-5 py-2 text-sm rounded-lg bg-[#F59E0B] hover:bg-[#D97706] text-black font-semibold transition disabled:opacity-50 cursor-pointer">
                            <span x-show="!submitting">{{ __('Submit review') }}</span>
                            <span x-show="submitting">{{ __('Submitting…') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
function reviewSubmitForm() {
    return {
        open: false,
        submitting: false,
        success: false,
        error: '',
        form: { name: '', email: '', rating: 5, text: '', website: '' },
        async submit() {
            this.error = '';
            if (this.form.text.length < 10) { this.error = '{{ __('Review must be at least 10 characters.') }}'; return; }
            if (this.form.rating < 1) { this.error = '{{ __('Please pick a rating.') }}'; return; }
            this.submitting = true;
            try {
                const res = await fetch('{{ route($lp . 'reviews.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || '{{ __('Failed to submit. Please try again.') }}';
                    return;
                }
                this.success = true;
                setTimeout(() => { this.open = false; this.success = false; this.form = { name: '', email: '', rating: 5, text: '', website: '' }; }, 3000);
            } catch (e) {
                this.error = '{{ __('Network error. Please try again.') }}';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush

{{-- Featured Blog Posts --}}
@if($blogPosts->count())
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">{{ __('From the Blog') }}</h2>
            <p class="text-[#A0A0A0]">{{ __('Travel tips, city guides, and luggage advice for Belgrade.') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($blogPosts as $post)
            <a href="{{ route($lp . 'blog.show', ['slug' => $post->slug]) }}" class="card hover:border-[#F59E0B] transition group">
                @if($post->featured_image)
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full aspect-square object-cover rounded-lg mb-4">
                @endif
                <h3 class="text-lg font-semibold group-hover:text-[#F59E0B] transition">{{ $post->title }}</h3>
                <p class="text-sm text-[#A0A0A0] mt-2 line-clamp-2">{{ Str::limit($post->excerpt, 120) }}</p>
                <p class="text-xs text-[#A0A0A0] mt-3">{{ $post->published_at?->format('d M Y') }}</p>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route($lp . 'blog.index') }}" class="text-[#F59E0B] hover:underline">{{ __('View all posts') }} &rarr;</a>
        </div>
    </div>
</section>
@endif

{{-- FAQ Preview --}}
@if($faqs->count())
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-{{ $home?->section('faq.subtitle') ? '4' : '12' }}">{{ $home?->section('faq.title') ?: __('Frequently Asked Questions') }}</h2>
        @if($home?->section('faq.subtitle'))
        <p class="text-center text-[#A0A0A0] mb-12">{{ $home->section('faq.subtitle') }}</p>
        @endif
        <div class="space-y-3">
            @foreach($faqs as $faq)
            <div class="card !p-0 overflow-hidden" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-left py-4 px-4 sm:py-5 sm:px-6">
                    <span class="font-medium pr-4 text-sm sm:text-base">{{ $faq->question }}</span>
                    <svg class="w-5 h-5 text-[#F59E0B] transition-transform duration-300 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-4 pb-4 sm:px-6 sm:pb-5 text-[#A0A0A0] text-sm leading-relaxed border-t border-[#2A2A2A]">
                    <div class="pt-4 prose prose-invert max-w-none prose-sm">{!! $faq->answer !!}</div>
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
        <h2 class="text-3xl sm:text-4xl font-bold">{{ $home?->section('cta.title') ?: __('Ready to Explore Belgrade?') }}</h2>
        <p class="mt-4 text-lg text-[#A0A0A0]">{{ $home?->section('cta.subtitle') ?: __('Drop your bags and enjoy the city hands-free. Book in 60 seconds.') }}</p>
        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary mt-8">{{ $home?->section('cta.button') ?: __('Book Your Locker') }}</a>
    </div>
</section>
@endsection
