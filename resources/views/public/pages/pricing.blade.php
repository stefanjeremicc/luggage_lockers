@extends('layouts.public')

@section('title', __('Pricing') . ' — Belgrade Luggage Locker')
@section('meta_description', 'Luggage storage pricing in Belgrade. Standard lockers from €5, Large lockers from €10. No hidden fees.')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-center mb-4">{{ __('Our Pricing') }}</h1>
        <p class="text-center text-[#A0A0A0] mb-12 max-w-2xl mx-auto">{{ __('No hidden fees. Pay cash on arrival in EUR or RSD.') }}</p>

        {{-- ============ Standard Locker ============ --}}
        <div class="rounded-2xl border border-[#2A2A2A] overflow-hidden bg-[#1A1A1A] mb-8" x-data="{ showDim: false }">
            <div class="pricing-row">
                <div class="pricing-card-image relative">
                    <img src="/images/lockers/standard.webp" alt="{{ __('Standard Locker') }}">
                    <button @click="showDim = true" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full flex items-center justify-center transition backdrop-blur-sm bg-black/60 border border-white/15" title="{{ __('View dimensions') }}">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <div x-show="showDim" x-transition x-cloak @click.self="showDim = false" class="dim-overlay">
                        <div class="relative max-w-sm w-full">
                            <button @click="showDim = false" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#2A2A2A] border border-[#3A3A3A] flex items-center justify-center text-[#A0A0A0] hover:text-white hover:bg-[#3A3A3A] transition z-30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img src="/images/lockers/standard-dimensions.jpg" alt="{{ __('Standard Locker dimensions') }}" class="w-full rounded-xl border border-[#2A2A2A]">
                        </div>
                    </div>
                </div>
                <div class="pricing-card-content p-4 lg:p-8">
                    <h2 class="text-2xl font-bold mb-2">{{ __('Standard Locker') }}</h2>
                    <p class="text-[#A0A0A0] text-sm">{{ __('Up to 1 carry-on bag + backpack') }}</p>
                    <div class="flex items-center gap-3 mt-3">
                        <span class="text-xs text-[#A0A0A0] border border-[#2A2A2A] rounded-lg px-3 py-2">50 &times; 65 &times; 28 cm</span>
                        <span class="text-xs text-[#A0A0A0] border border-[#2A2A2A] rounded-lg px-3 py-2">19 &times; 25 &times; 11 in</span>
                    </div>
                    <div class="rounded-xl overflow-hidden border border-[#2A2A2A] mt-5">
                        @foreach($pricingRules['standard'] ?? [] as $index => $rule)
                        <div class="flex justify-between items-center px-5 py-2.5 text-sm {{ $index % 2 === 0 ? 'bg-[#111111]' : 'bg-[#161616]' }}">
                            <span class="text-[#A0A0A0]">{{ __($rule->label) }}</span>
                            <span class="font-bold text-[#F59E0B]">&euro;{{ number_format($rule->price_eur, 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route($lp . 'locations.index') }}" class="btn-primary w-full text-center mt-5">{{ __('Book Standard') }}</a>
                </div>
            </div>
        </div>

        {{-- ============ Large Locker ============ --}}
        <div class="rounded-2xl border border-[#F59E0B]/50 overflow-hidden bg-[#1A1A1A] mb-8 relative" x-data="{ showDim: false }">
            <div class="absolute top-0 right-0 bg-[#F59E0B] text-black text-xs font-bold px-3 py-1 rounded-bl-lg z-10">{{ __('Popular') }}</div>
            <div class="pricing-row">
                <div class="pricing-card-image relative">
                    <img src="/images/lockers/large.webp" alt="{{ __('Large Locker') }}">
                    <button @click="showDim = true" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full flex items-center justify-center transition backdrop-blur-sm bg-black/60 border border-white/15" title="{{ __('View dimensions') }}">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <div x-show="showDim" x-transition x-cloak @click.self="showDim = false" class="dim-overlay">
                        <div class="relative max-w-sm w-full">
                            <button @click="showDim = false" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-[#2A2A2A] border border-[#3A3A3A] flex items-center justify-center text-[#A0A0A0] hover:text-white hover:bg-[#3A3A3A] transition z-30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img src="/images/lockers/large-dimensions.jpg" alt="{{ __('Large Locker dimensions') }}" class="w-full rounded-xl border border-[#2A2A2A]">
                        </div>
                    </div>
                </div>
                <div class="pricing-card-content p-4 lg:p-8">
                    <h2 class="text-2xl font-bold mb-2">{{ __('Large Locker') }}</h2>
                    <p class="text-[#A0A0A0] text-sm">{{ __('Up to 4 carry-on bags or 2 checked bags') }}</p>
                    <div class="flex items-center gap-3 mt-3">
                        <span class="text-xs text-[#A0A0A0] border border-[#2A2A2A] rounded-lg px-3 py-2">50 &times; 65 &times; 90 cm</span>
                        <span class="text-xs text-[#A0A0A0] border border-[#2A2A2A] rounded-lg px-3 py-2">19 &times; 25 &times; 35 in</span>
                    </div>
                    <div class="rounded-xl overflow-hidden border border-[#2A2A2A] mt-5">
                        @foreach($pricingRules['large'] ?? [] as $index => $rule)
                        <div class="flex justify-between items-center px-5 py-2.5 text-sm {{ $index % 2 === 0 ? 'bg-[#111111]' : 'bg-[#161616]' }}">
                            <span class="text-[#A0A0A0]">{{ __($rule->label) }}</span>
                            <span class="font-bold text-[#F59E0B]">&euro;{{ number_format($rule->price_eur, 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route($lp . 'locations.index') }}" class="btn-primary w-full text-center mt-5">{{ __('Book Large') }}</a>
                </div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
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
        <a href="{{ route($lp . 'locations.index') }}" class="btn-primary text-lg px-10 py-4 mt-8 inline-block">{{ __('Book Your Locker') }}</a>
    </div>
</section>
@endsection
