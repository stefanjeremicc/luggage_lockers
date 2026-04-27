@extends('layouts.public')

@section('title', 'Blog — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-center mb-12">{{ __('Blog') }}</h1>

        @if($posts->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($posts as $post)
            <a href="{{ route($lp . 'blog.show', ['slug' => $post->slug]) }}" class="card hover:border-[#F59E0B] transition group">
                @if($post->featured_image)
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-48 object-cover rounded-lg mb-4">
                @endif
                @if($post->category)
                <span class="text-xs text-[#F59E0B]">{{ $post->category }}</span>
                @endif
                <h2 class="text-lg font-semibold mt-1 group-hover:text-[#F59E0B] transition">{{ $post->title }}</h2>
                <p class="text-sm text-[#A0A0A0] mt-2">{{ Str::limit($post->excerpt, 120) }}</p>
                <p class="text-xs text-[#A0A0A0] mt-3">{{ $post->published_at?->format('d M Y') }}</p>
            </a>
            @endforeach
        </div>
        {{ $posts->links() }}
        @else
        <p class="text-center text-[#A0A0A0]">{{ __('No blog posts yet. Check back soon!') }}</p>
        @endif
    </div>
</section>

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
