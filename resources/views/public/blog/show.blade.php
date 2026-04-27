@extends('layouts.public')

@section('title', ($post->meta_title ?? $post->title) . ' — Belgrade Luggage Locker')
@section('meta_description', $post->meta_description ?? $post->excerpt)

@section('content')
<article class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="text-sm text-[#A0A0A0] mb-8">
            <a href="{{ route($lp . 'home') }}" class="hover:text-white">{{ __('Home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route($lp . 'blog.index') }}" class="hover:text-white">{{ __('Blog') }}</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ $post->title }}</span>
        </nav>

        @if($post->category)
        <span class="text-xs text-[#F59E0B]">{{ $post->category }}</span>
        @endif

        <h1 class="text-4xl font-bold mt-2">{{ $post->title }}</h1>

        <div class="flex items-center gap-4 mt-4 text-sm text-[#A0A0A0]">
            @if($post->author_name)<span>{{ $post->author_name }}</span>@endif
            @if($post->published_at)<span>{{ $post->published_at->format('d M Y') }}</span>@endif
        </div>

        @if($post->featured_image)
        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full rounded-xl mt-8">
        @endif

        <div class="prose prose-invert max-w-none mt-8 text-[#A0A0A0]">
            {!! $post->content !!}
        </div>
    </div>
</article>

{{-- CTA --}}
<section class="bg-[#111111] py-16 lg:py-24 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#F59E0B] rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-[#F59E0B] rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold">{{ __('Ready to Explore Belgrade?') }}</h2>
        <p class="mt-4 text-lg text-[#A0A0A0]">{{ __('Drop your bags and enjoy the city hands-free. Book in 60 seconds.') }}</p>
        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary mt-8">{{ __('Book Your Locker') }}</a>
    </div>
</section>
@endsection
