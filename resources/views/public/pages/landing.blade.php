@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $title = $landing->title;
    $metaTitle = $landing->meta_title ?: $title;
    $metaDesc = $landing->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($landing->content ?? ''), 155);
    $hero = is_array($landing->sections) ? ($landing->sections['hero'] ?? []) : [];
    $heroTitle = $hero['title'] ?? $title;
    $heroSub = $hero['subtitle'] ?? null;
    $bookingHref = $landing->location
        ? route($lp . 'booking.index', ['slug' => $landing->location->slug])
        : route($lp . 'locations.index');
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDesc)

@section('head')
@if($landing->canonical_url)
<link rel="canonical" href="{{ $landing->canonical_url }}">
@endif
@if($landing->og_title)
<meta property="og:title" content="{{ $landing->og_title }}">
@endif
@if($landing->og_description)
<meta property="og:description" content="{{ $landing->og_description }}">
@endif
@if($landing->og_image)
<meta property="og:image" content="{{ url($landing->og_image) }}">
<meta name="twitter:image" content="{{ url($landing->og_image) }}">
@endif
@endsection

@section('content')
<section class="relative py-16 lg:py-24 bg-gradient-to-b from-[#0F0F0F] to-[#0A0A0A]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">{{ $heroTitle }}</h1>
        @if($heroSub)
        <p class="text-lg text-[#A0A0A0] mt-5 max-w-2xl mx-auto leading-relaxed">{{ $heroSub }}</p>
        @endif
        <div class="mt-8">
            <a href="{{ $bookingHref }}" class="btn-primary inline-flex items-center gap-2">
                {{ __('Book a Locker') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</section>

@if($landing->content)
<section class="py-12 lg:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-invert max-w-none text-[#D0D0D0] leading-relaxed">{!! $landing->content !!}</div>
    </div>
</section>
@endif

@if($landing->location)
<section class="bg-[#0F0F0F] border-y border-[#1A1A1A] py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-xs uppercase tracking-wide text-[#F59E0B] font-semibold mb-2">{{ __('Closest location') }}</p>
        <h2 class="text-2xl font-bold mb-2">{{ $landing->location->nameFor($locale) }}</h2>
        <p class="text-sm text-[#A0A0A0] mb-5">{{ $landing->location->addressFor($locale) }}</p>
        <a href="{{ route($lp . 'locations.show', $landing->location->slug) }}" class="btn-outline">{{ __('View location') }}</a>
    </div>
</section>
@endif
@endsection
