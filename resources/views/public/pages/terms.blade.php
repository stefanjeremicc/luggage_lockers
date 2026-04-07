@extends('layouts.public')

@section('title', 'Terms & Conditions — Belgrade Luggage Locker')

@section('content')
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold mb-8">{{ __('Terms & Conditions') }}</h1>
        @if($page && $page->content)
            <div class="prose prose-invert max-w-none text-[#A0A0A0]">{!! $page->content !!}</div>
        @else
            <div class="text-[#A0A0A0] space-y-4 text-sm leading-relaxed">
                <p>These terms and conditions govern your use of Belgrade Luggage Locker services. By making a booking, you agree to these terms.</p>
                <p>Content will be managed from the admin panel.</p>
            </div>
        @endif
    </div>
</section>
@endsection
