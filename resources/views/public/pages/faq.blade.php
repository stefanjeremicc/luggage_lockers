@extends('layouts.public')

@section('title', 'FAQ — Belgrade Luggage Locker')
@section('meta_description', 'Frequently asked questions about luggage storage in Belgrade. How it works, pricing, security, and more.')

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($faqs as $faq)
        {
            "@@type": "Question",
            "name": "{{ addslashes($faq->question) }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ addslashes($faq->answer) }}"
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-center mb-4">{{ __('Frequently Asked Questions') }}</h1>
        <p class="text-center text-[#A0A0A0] mb-12">{{ __('Everything you need to know about our luggage storage service.') }}</p>

        @foreach($categories as $category => $categoryFaqs)
        <div class="mb-8">
            @if($category)
            <h2 class="text-xl font-semibold mb-4 text-[#F59E0B]">{{ $category }}</h2>
            @endif
            <div class="space-y-3">
                @foreach($categoryFaqs as $faq)
                <div class="card !py-0 overflow-hidden" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex items-center justify-between text-left py-5 px-6 cursor-pointer">
                        <span class="font-medium pr-4">{{ $faq->question }}</span>
                        <svg class="w-5 h-5 text-[#F59E0B] flex-shrink-0 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak
                         class="px-6 pb-5 text-[#A0A0A0] text-sm leading-relaxed border-t border-[#2A2A2A]">
                        <p class="pt-4">{{ $faq->answer }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="text-center mt-12">
            <p class="text-[#A0A0A0]">{{ __('Still have questions?') }}</p>
            <a href="{{ route($lp . 'contact') }}" class="text-[#F59E0B] hover:underline">{{ __('Contact us') }} &rarr;</a>
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
