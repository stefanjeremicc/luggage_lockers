@extends('layouts.public')

@section('title', $page?->meta_title ?: __('Privacy Policy') . ' — Belgrade Luggage Locker')
@section('meta_description', $page?->meta_description ?: '')

@section('content')
<section class="py-12 sm:py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl font-bold mb-8">{{ $page?->title ?: __('Privacy Policy') }}</h1>
        @if($page && $page->content)
            <div class="legal-prose">{!! $page->content !!}</div>
        @else
            <div class="text-[#A0A0A0] space-y-4 text-sm leading-relaxed">
                <p>{{ __('This page is being prepared. Please check back soon or contact us if you need this information today.') }}</p>
            </div>
        @endif
    </div>
</section>
@endsection
