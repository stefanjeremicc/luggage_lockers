@extends('layouts.public')

@section('title', __('Pricing') . ' — Belgrade Luggage Locker')
@section('meta_description', 'Luggage storage pricing in Belgrade. Standard lockers from €5, Large lockers from €10. No hidden fees.')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl font-bold text-center mb-4">{{ __('Pricing') }}</h1>
        <p class="text-center text-[#A0A0A0] mb-14 max-w-xl mx-auto">{{ __('No hidden fees. Pay cash on arrival in EUR or RSD.') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Standard Locker --}}
            <div class="pricing-card" x-data="{ showDim: false }">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ __('Standard Locker') }}</h2>
                            <p class="text-sm text-[#A0A0A0] mt-1">{{ __('Up to 1 carry-on bag + backpack') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-[#F59E0B]">&euro;5</span>
                            <p class="text-xs text-[#A0A0A0]">{{ __('from / 6h') }}</p>
                        </div>
                    </div>

                    <div class="pricing-table">
                        @foreach($pricingRules['standard'] ?? [] as $index => $rule)
                        <div class="pricing-table-row">
                            <span>{{ __($rule->label) }}</span>
                            <span class="font-semibold">&euro;{{ number_format($rule->price_eur, 0) }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-center">{{ __('Book Standard') }}</a>
                        <button @click="showDim = true" class="text-xs text-[#A0A0A0] hover:text-[#F59E0B] transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/></svg>
                            50 &times; 65 &times; 28 cm
                        </button>
                    </div>
                </div>

                {{-- Dimensions modal — fullscreen --}}
                <div x-show="showDim" x-transition.opacity x-cloak @click.self="showDim = false" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-6">
                    <div class="relative max-w-lg w-full">
                        <button @click="showDim = false" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#2A2A2A] border border-[#3A3A3A] flex items-center justify-center text-[#A0A0A0] hover:text-white hover:bg-[#3A3A3A] transition z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <img src="/images/lockers/standard-dimensions.jpg" alt="{{ __('Standard Locker dimensions') }}" class="w-full rounded-2xl border border-[#2A2A2A]">
                    </div>
                </div>
            </div>

            {{-- Large Locker --}}
            <div class="pricing-card pricing-card--featured" x-data="{ showDim: false }">
                <div class="pricing-card-badge">{{ __('Popular') }}</div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ __('Large Locker') }}</h2>
                            <p class="text-sm text-[#A0A0A0] mt-1">{{ __('Up to 4 carry-on bags or 2 checked bags') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-[#F59E0B]">&euro;10</span>
                            <p class="text-xs text-[#A0A0A0]">{{ __('from / 6h') }}</p>
                        </div>
                    </div>

                    <div class="pricing-table">
                        @foreach($pricingRules['large'] ?? [] as $index => $rule)
                        <div class="pricing-table-row">
                            <span>{{ __($rule->label) }}</span>
                            <span class="font-semibold">&euro;{{ number_format($rule->price_eur, 0) }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-center">{{ __('Book Large') }}</a>
                        <button @click="showDim = true" class="text-xs text-[#A0A0A0] hover:text-[#F59E0B] transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"/></svg>
                            50 &times; 65 &times; 90 cm
                        </button>
                    </div>
                </div>

                {{-- Dimensions modal — fullscreen --}}
                <div x-show="showDim" x-transition.opacity x-cloak @click.self="showDim = false" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-6">
                    <div class="relative max-w-lg w-full">
                        <button @click="showDim = false" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#2A2A2A] border border-[#3A3A3A] flex items-center justify-center text-[#A0A0A0] hover:text-white hover:bg-[#3A3A3A] transition z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <img src="/images/lockers/large-dimensions.jpg" alt="{{ __('Large Locker dimensions') }}" class="w-full rounded-2xl border border-[#2A2A2A]">
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-14">
            <div class="card text-center">
                <div class="w-10 h-10 rounded-full bg-[#F59E0B]/10 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="font-semibold text-sm">{{ __('Cash on Arrival') }}</h3>
                <p class="text-xs text-[#A0A0A0] mt-1">EUR &amp; RSD {{ __('accepted') }}</p>
            </div>
            <div class="card text-center">
                <div class="w-10 h-10 rounded-full bg-[#10B981]/10 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-semibold text-sm">{{ __('No Hidden Fees') }}</h3>
                <p class="text-xs text-[#A0A0A0] mt-1">{{ __('Price you see is the price you pay') }}</p>
            </div>
            <div class="card text-center">
                <div class="w-10 h-10 rounded-full bg-[#F59E0B]/10 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-semibold text-sm">{{ __('Free Cancellation') }}</h3>
                <p class="text-xs text-[#A0A0A0] mt-1">{{ __('Cancel anytime before check-in') }}</p>
            </div>
        </div>
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
