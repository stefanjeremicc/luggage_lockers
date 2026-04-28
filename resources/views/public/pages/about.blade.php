@extends('layouts.public')

@section('title', 'About Us — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold mb-8">{{ __('About Belgrade Luggage Locker') }}</h1>

        @if($page && $page->content)
            <div class="prose prose-invert max-w-none text-[#A0A0A0]">{!! $page->content !!}</div>
        @else
            <div class="space-y-6 text-[#A0A0A0]">
                <p>{{ __('Belgrade Luggage Locker provides secure, 24/7 luggage storage in the heart of Belgrade. Our smart locker system allows travelers to safely store their belongings while exploring the city.') }}</p>
                <p>{{ __('With 2 convenient locations — City Center and Savamala — we make it easy for tourists, business travelers, and locals to store their bags at affordable prices.') }}</p>

                <h2 class="text-2xl font-bold text-white mt-8 mb-4">{{ __('Why Choose Us?') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach([
                        ['title' => '24/7 Access', 'desc' => 'Access your locker any time, day or night.'],
                        ['title' => 'Smart Locks', 'desc' => 'Secure PIN-based smart lock technology.'],
                        ['title' => 'Easy Booking', 'desc' => 'Book online in 60 seconds, pay on arrival.'],
                        ['title' => 'Great Locations', 'desc' => '2 locations in the center of Belgrade.'],
                    ] as $item)
                    <div class="card">
                        <h3 class="font-semibold text-white">{{ __($item['title']) }}</h3>
                        <p class="text-sm mt-1">{{ __($item['desc']) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
