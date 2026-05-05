@extends('layouts.public')

@section('title', $page?->meta_title ?: $page?->title)

@section('content')
<section class="py-12 sm:py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl font-bold mb-8">{{ $page?->title ?: __('Terms & Conditions') }}</h1>
        @if($page?->content)
            <div class="legal-prose">{!! $page->content !!}</div>
        @endif
    </div>
</section>
@endsection
