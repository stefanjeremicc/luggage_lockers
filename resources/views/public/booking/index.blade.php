@extends('layouts.public')

@section('title', "Book Luggage Storage — {$location->name}")
@section('meta_description', "Book a secure locker at {$location->name}, {$location->address}, Belgrade. Choose size and duration, get instant PIN.")

@php
    $stdInfo = \App\Helpers\SiteSettings::lockerInfo('standard');
    $lrgInfo = \App\Helpers\SiteSettings::lockerInfo('large');
    $durationsForJs = [];
    foreach (['standard', 'large'] as $size) {
        $rules = $durations[$size] ?? collect();
        $durationsForJs[$size] = $rules->map(fn($r) => [
            'key' => $r->duration_key,
            'label' => $r->labelFor(app()->getLocale()),
            'price_eur' => (float) $r->price_eur,
            'price' => '€' . number_format((float) $r->price_eur, (fmod((float) $r->price_eur, 1) ? 2 : 0)),
        ])->values();
    }
    $minStandard = collect($durationsForJs['standard'])->min('price_eur');
    $minLarge = collect($durationsForJs['large'])->min('price_eur');
    $i18nForJs = [
        'lockers_available' => __(':count lockers available'),
        'no_lockers' => __('No lockers available'),
        'standard' => __('Standard'),
        'large' => __('Large'),
        'errors' => [
            'full_name' => __('Please enter your full name'),
            'email_required' => __('Email is required'),
            'email_invalid' => __('Please enter a valid email'),
            'phone_invalid' => __('Please enter a valid phone number'),
            'registration' => __('Registration failed.'),
            'network' => __('Network error. Please try again.'),
            'booking' => __('Booking failed.'),
        ],
        'weekdays' => [__('Mo'), __('Tu'), __('We'), __('Th'), __('Fr'), __('Sa'), __('Su')],
    ];
@endphp
@section('content')
<section class="py-6 lg:py-12" x-data="bookingFlow"
    data-location-id="{{ $location->id }}"
    data-durations='@json($durationsForJs)'
    data-i18n='@json($i18nForJs)'>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-[#A0A0A0] mb-6">
            <a href="{{ route($lp . 'home') }}" class="hover:text-white transition">{{ __('Home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route($lp . 'locations.show', ['slug' => $location->slug]) }}" class="hover:text-white transition">{{ $location->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ __('Book') }}</span>
        </nav>

        {{-- Location Header --}}
        <div class="mb-8">
            <h1 class="text-2xl lg:text-3xl font-bold">{{ __('Book a Locker') }}</h1>
            <div class="flex items-center gap-3 mt-2">
                <p class="text-[#A0A0A0] flex items-center gap-1.5 text-sm">
                    <svg class="w-4 h-4 flex-shrink-0 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-[#F59E0B] font-medium">{{ $location->address }}@if($location->city), {{ $location->city }}@endif</span>
                </p>
                <span class="text-xs text-[#10B981] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] inline-block"></span>
                    24/7
                </span>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-2 mb-8">
            @foreach([1 => __('Date & Time'), 2 => __('Locker & Duration'), 3 => __('Your Info'), 4 => __('Confirm')] as $num => $label)
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                     :class="step > {{ $num }} ? 'bg-[#10B981] text-white' : (step === {{ $num }} ? 'bg-[#F59E0B] text-black' : 'bg-[#2A2A2A] text-[#A0A0A0]')">
                    <template x-if="step > {{ $num }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="step <= {{ $num }}">
                        <span>{{ $num }}</span>
                    </template>
                </div>
                <span class="text-sm hidden sm:inline" :class="step >= {{ $num }} ? 'text-white' : 'text-[#A0A0A0]'">{{ $label }}</span>
            </div>
            @if($num < 4)<div class="flex-1 h-px transition-colors duration-300" :class="step > {{ $num }} ? 'bg-[#10B981]' : 'bg-[#2A2A2A]'"></div>@endif
            @endforeach
        </div>

        {{-- Error Alert --}}
        <div x-show="error" x-transition x-cloak class="rounded-xl border border-[#EF4444]/50 bg-[#EF4444]/10 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-[#EF4444] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-[#EF4444] text-sm" x-text="error"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- ==================== MAIN CONTENT ==================== --}}
            <div class="lg:col-span-2">

                {{-- ==================== STEP 1: Date & Time ==================== --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                    {{-- Date Selection --}}
                    <div class="card mb-6" x-data="{ showCal: false }">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('When are you arriving?') }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button @click="date = 'today'; customDate = null; showCal = false" class="pill" :class="{ 'active': date === 'today' }">{{ __('Today') }}</button>
                            <button @click="date = 'tomorrow'; customDate = null; showCal = false" class="pill" :class="{ 'active': date === 'tomorrow' }">{{ __('Tomorrow') }}</button>
                            <button @click="showCal = true; date = 'custom'" class="pill" :class="{ 'active': date === 'custom' }">
                                <span x-show="!customDate || date !== 'custom'">{{ __('Pick date') }}</span>
                                <span x-show="customDate && date === 'custom'" x-text="customDate" x-cloak></span>
                            </button>
                        </div>
                        {{-- Custom Calendar --}}
                        <div class="relative">
                            <div x-show="showCal"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                                 x-cloak
                                 class="mt-4 sm:absolute sm:top-full sm:left-0 sm:mt-2 sm:z-30 sm:w-80 sm:bg-[#1A1A1A] sm:border sm:border-[#2A2A2A] sm:rounded-2xl sm:p-5 sm:shadow-2xl"
                                 x-data="calendarPicker({ minDate: '{{ now()->format('Y-m-d') }}', maxDate: '{{ now()->addDays(30)->format('Y-m-d') }}' })"
                                 x-init="$watch('selectedDate', val => { if(val) { $dispatch('custom-date-picked', val); } })">

                                {{-- Month Navigation --}}
                                <div class="flex items-center justify-between mb-4">
                                    <button @click="prevMonth()" :disabled="!canGoPrev" type="button"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                            :class="canGoPrev ? 'bg-[#2A2A2A] hover:bg-[#3A3A3A] text-white' : 'text-[#2A2A2A] cursor-not-allowed'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span class="text-sm font-semibold" x-text="monthLabel"></span>
                                    <button @click="nextMonth()" :disabled="!canGoNext" type="button"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                            :class="canGoNext ? 'bg-[#2A2A2A] hover:bg-[#3A3A3A] text-white' : 'text-[#2A2A2A] cursor-not-allowed'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>

                                {{-- Day of Week Headers --}}
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <template x-for="day in ['{{ __('Mo') }}','{{ __('Tu') }}','{{ __('We') }}','{{ __('Th') }}','{{ __('Fr') }}','{{ __('Sa') }}','{{ __('Su') }}']" :key="day">
                                        <div class="text-center text-xs text-[#A0A0A0] font-medium py-1" x-text="day"></div>
                                    </template>
                                </div>

                                {{-- Calendar Days --}}
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="cell in calendarDays" :key="cell.key">
                                        <button @click="selectDay(cell)" type="button"
                                                class="aspect-square rounded-lg text-sm flex items-center justify-center transition-all duration-150"
                                                :class="cellClasses(cell)"
                                                :disabled="!cell.enabled"
                                                x-text="cell.day || ''">
                                        </button>
                                    </template>
                                </div>

                                {{-- Calendar actions --}}
                                <div class="flex items-center justify-between mt-4 pt-3 border-t border-[#2A2A2A]">
                                    <button @click="showCal = false; date = null; customDate = null" type="button" class="text-xs text-[#A0A0A0] hover:text-white transition">{{ __('Cancel') }}</button>
                                    <button @click="if(selectedDate) { showCal = false; }" type="button"
                                            class="text-xs font-medium transition"
                                            :class="selectedDate ? 'text-[#F59E0B] hover:text-white' : 'text-[#2A2A2A] cursor-not-allowed'"
                                            :disabled="!selectedDate">{{ __('Confirm') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrival Time --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('What time will you arrive?') }}
                        </h3>

                        {{-- Custom styled select --}}
                        <div class="custom-select-wrapper" x-data="{ open: false, search: '' }" @click.outside="open = false">
                            <button @click="open = !open" type="button" class="custom-select">
                                <span x-text="time ? formatTime(time) : '{{ __('Select arrival time...') }}'" :class="time ? 'text-white' : 'text-[#A0A0A0]'"></span>
                                <svg class="w-5 h-5 text-[#A0A0A0] transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak
                                 class="custom-select-dropdown">
                                <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                    <template x-for="slot in generateTimeSlots()" :key="slot">
                                        <button @click="time = slot; open = false" type="button"
                                                class="w-full text-left px-4 py-2.5 text-sm transition-colors"
                                                :class="time === slot ? 'bg-[#F59E0B] text-black font-medium' : 'text-[#A0A0A0] hover:bg-[#2A2A2A] hover:text-white'"
                                                x-text="formatTime(slot)">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-[#A0A0A0] mt-2">{{ __('You can arrive up to 20 min before your selected time.') }}</p>
                    </div>

                    <button @click="goToStep(2)" :disabled="!canContinueStep1" class="btn-primary w-full">
                        {{ __('Continue') }} &rarr;
                    </button>
                </div>

                {{-- ==================== STEP 2: Locker & Duration ==================== --}}
                <div x-show="step === 2">

                    {{-- Locker Type --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            {{ __('Choose your locker') }}
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach([
                                ['size'=>'standard','info'=>$stdInfo,'fallbackCap'=>'1 suitcase & 1 bag','fallbackImg'=>'/images/lockers/standard-dimensions.jpg','label'=>'Standard'],
                                ['size'=>'large','info'=>$lrgInfo,'fallbackCap'=>'2 suitcases & 1 bag','fallbackImg'=>'/images/lockers/large-dimensions.jpg','label'=>'Large'],
                            ] as $card)
                            <div class="locker-card group flex flex-col" :class="qtys.{{ $card['size'] }} > 0 ? 'locker-card--active' : ''">
                                {{-- Image --}}
                                <div class="relative overflow-hidden rounded-t-xl h-36 bg-[#111111]">
                                    <img src="{{ $card['info']['image'] ?: $card['fallbackImg'] }}" alt="{{ __($card['label']) }} {{ __('Locker') }}"
                                         class="w-full h-full object-contain bg-[#111111]">
                                    {{-- Active corner badge when qty > 0 --}}
                                    <div x-show="qtys.{{ $card['size'] }} > 0" x-transition class="absolute top-2 right-2 bg-[#F59E0B] text-black text-xs font-bold rounded-full w-7 h-7 flex items-center justify-center" x-text="qtys.{{ $card['size'] }}"></div>
                                </div>

                                <div class="p-4 flex-1 flex flex-col gap-3">
                                    {{-- Title + capacity --}}
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-lg font-semibold">{{ __($card['label']) }}</span>
                                            @if($card['info']['dimensions'])<span class="text-xs text-[#A0A0A0]">{{ $card['info']['dimensions'] }}</span>@endif
                                        </div>
                                        <p class="text-sm text-[#A0A0A0] mt-1">{{ $card['info']['capacity'] ?: __($card['fallbackCap']) }}</p>
                                    </div>

                                    {{-- Per-card duration grid — only renders when this size has qty > 0,
                                         so the inactive card looks visibly "off" and there's no confusing
                                         dual highlight. --}}
                                    <div x-show="qtys.{{ $card['size'] }} > 0" x-cloak>
                                        <p class="text-[10px] uppercase tracking-wider text-[#6B7280] mb-2">{{ __('Duration') }}</p>
                                        <div class="grid grid-cols-3 gap-1.5">
                                            @foreach(($durationsForJs[$card['size']] ?? collect()) as $opt)
                                            <button type="button" @click="duration = '{{ $opt['key'] }}'"
                                                class="duration-pill !py-2 !px-1"
                                                :class="duration === '{{ $opt['key'] }}' ? 'duration-pill--active' : ''">
                                                <span class="text-[11px] sm:text-xs font-medium leading-tight">{{ __($opt['label']) }}</span>
                                                <span class="text-[10px] sm:text-[11px]" :class="duration === '{{ $opt['key'] }}' ? 'text-black/70' : 'text-[#F59E0B]'">{{ $opt['price'] }}</span>
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Hint shown only when this card is empty — invites the user to add. --}}
                                    <p x-show="qtys.{{ $card['size'] }} === 0" x-cloak class="text-xs text-[#6B7280] italic">
                                        {{ __('Tap + to add and pick duration') }}
                                    </p>

                                    {{-- Quantity stepper + availability --}}
                                    <div class="flex items-center justify-between pt-3 border-t border-[#2A2A2A]">
                                        <div class="text-xs">
                                            <span x-show="resolvedDate && time && duration"
                                                  :class="availability['{{ $card['size'] }}']?.available > 3 ? 'text-[#10B981]' : (availability['{{ $card['size'] }}']?.available > 0 ? 'text-[#F59E0B]' : 'text-[#EF4444]')"
                                                  x-text="availabilityLabelFor('{{ $card['size'] }}')"></span>
                                            <span x-show="!(resolvedDate && time && duration)" class="text-[#6B7280]">{{ __('How many?') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="decrementSize('{{ $card['size'] }}')" :disabled="qtys.{{ $card['size'] }} <= 0"
                                                    class="qty-btn !w-10 !h-10 !rounded-lg disabled:opacity-30">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="text-lg font-bold w-7 text-center tabular-nums" x-text="qtys.{{ $card['size'] }}"></span>
                                            <button type="button" @click="incrementSize('{{ $card['size'] }}')" :disabled="qtys.{{ $card['size'] }} >= maxQtyFor('{{ $card['size'] }}')"
                                                    class="qty-btn !w-10 !h-10 !rounded-lg disabled:opacity-30">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(1)" class="btn-back">&larr;</button>
                        <button @click="goToStep(3)" :disabled="!canContinueStep2 || loading" class="btn-primary flex-1">
                            <span x-show="!loading">{{ __('Continue') }} &rarr;</span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- ==================== STEP 3: Customer Info ==================== --}}
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ __('Your Information') }}
                        </h3>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-[#A0A0A0] mb-1.5">{{ __('Full Name') }} *</label>
                                    <input type="text" x-model="guestForm.full_name" @blur="validateField('full_name')" class="input-field" :class="formErrors.full_name ? '!border-[#EF4444]' : ''" placeholder="John Doe" required>
                                    <p x-show="formErrors.full_name" x-cloak class="text-xs text-[#EF4444] mt-1" x-text="formErrors.full_name"></p>
                                </div>
                                <div>
                                    <label class="block text-sm text-[#A0A0A0] mb-1.5">{{ __('Email') }} *</label>
                                    <input type="email" x-model="guestForm.email" @blur="validateField('email')" class="input-field" :class="formErrors.email ? '!border-[#EF4444]' : ''" placeholder="john@example.com" required>
                                    <p x-show="formErrors.email" x-cloak class="text-xs text-[#EF4444] mt-1" x-text="formErrors.email"></p>
                                </div>
                            </div>
                            {{-- Phone with country prefix --}}
                            <div>
                                <label class="block text-sm text-[#A0A0A0] mb-1.5">{{ __('Phone Number') }} *</label>
                                <div class="flex relative" x-data="{ phoneOpen: false, phoneSearch: '' }" @click.outside="phoneOpen = false">
                                    <button @click="phoneOpen = !phoneOpen" type="button"
                                            class="flex items-center gap-1.5 px-3 bg-[#111111] border border-[#2A2A2A] border-r-0 rounded-l-xl text-sm whitespace-nowrap hover:border-[#F59E0B] transition flex-shrink-0">
                                        <span class="fi fis" :class="'fi-' + guestForm.country_code.toLowerCase()"></span>
                                        <span class="text-[#A0A0A0]" x-text="selectedDialCode()"></span>
                                        <svg class="w-3 h-3 text-[#A0A0A0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="phoneOpen" x-transition x-cloak class="absolute top-full left-0 w-72 mt-1 z-50 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden shadow-2xl">
                                        <div class="px-3 py-2 border-b border-[#2A2A2A]">
                                            <input type="text" x-model="phoneSearch" class="input-field !py-2 text-xs" placeholder="{{ __('Search country...') }}">
                                        </div>
                                        <div class="max-h-52 overflow-y-auto custom-scrollbar">
                                            <template x-for="c in phoneCountries.filter(c => !phoneSearch || c.name.toLowerCase().includes(phoneSearch.toLowerCase()) || c.dial.includes(phoneSearch))" :key="c.code">
                                                <button @click="guestForm.country_code = c.code; phoneOpen = false; phoneSearch = ''" type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-3 transition-colors"
                                                        :class="guestForm.country_code === c.code ? 'bg-[#F59E0B] text-black font-medium' : 'text-[#A0A0A0] hover:bg-[#2A2A2A] hover:text-white'">
                                                    <span class="fi fis" :class="'fi-' + c.code.toLowerCase()"></span>
                                                    <span x-text="c.name"></span>
                                                    <span class="ml-auto text-xs" :class="guestForm.country_code === c.code ? 'text-black/60' : 'text-[#A0A0A0]'" x-text="c.dial"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="tel" x-model="guestForm.phone" class="input-field !rounded-l-none" placeholder="65 123 4567" required>
                                </div>
                                <p x-show="formErrors.phone" x-cloak class="text-xs text-[#EF4444] mt-1" x-text="formErrors.phone"></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="guestForm.agree_terms" id="agree_terms">
                                <label for="agree_terms" class="text-sm text-[#A0A0A0]">{{ __('I agree to the') }} <a href="{{ route($lp . 'terms') }}" class="text-[#F59E0B] hover:underline" target="_blank">{{ __('Terms & Conditions') }}</a> *</label>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="guestForm.whatsapp_opt_in" id="whatsapp">
                                <label for="whatsapp" class="text-sm text-[#A0A0A0]">{{ __('Send booking updates via WhatsApp') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(2)" class="btn-back">&larr;</button>
                        <button @click="submitGuestForm()" :disabled="!canContinueStep3 || loading" class="btn-primary flex-1">
                            <span x-show="!loading">{{ __('Continue') }} &rarr;</span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- ==================== STEP 4: Confirm ==================== --}}
                <div x-show="step === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="card mb-6">
                        <h3 class="font-semibold text-lg mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Confirm Your Booking') }}
                        </h3>

                        <div class="space-y-5">
                            <div class="flex items-center gap-4 text-sm">
                                <div class="w-9 h-9 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white">{{ $location->name }}</p>
                                    <p class="text-[#A0A0A0] text-xs">{{ $location->address }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="w-9 h-9 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white" x-text="formatConfirmDate()"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="w-9 h-9 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <template x-for="line in cartItems" :key="line.size">
                                        <p class="font-medium text-white"><span x-text="line.qty"></span> × <span x-text="sizeLabelFor(line.size)"></span></p>
                                    </template>
                                    <p class="text-[#A0A0A0] text-xs" x-text="orderSummary ? orderSummary.durationLabel : ''"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="w-9 h-9 rounded-xl bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white" x-text="customer ? customer.full_name : ''"></p>
                                    <p class="text-[#A0A0A0] text-xs" x-text="customer ? customer.email : ''"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment info --}}
                    <div class="card mb-6 !p-0 overflow-hidden">
                        <div class="bg-[#F59E0B]/5 border border-[#F59E0B]/20 rounded-2xl p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-[#A0A0A0]">{{ __('Total — pay on arrival') }}</p>
                                    <p class="text-2xl font-bold text-[#F59E0B] mt-1" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></p>
                                    <p class="text-xs text-[#A0A0A0] mt-0.5" x-text="orderSummary ? '~' + Math.round(orderSummary.totalRsd) + ' RSD' : ''"></p>
                                </div>
                                <div class="w-12 h-12 rounded-2xl bg-[#F59E0B]/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                            </div>
                            <p class="text-xs text-[#A0A0A0] mt-3">{{ __('We accept EUR and RSD.') }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(3)" class="btn-back">&larr;</button>
                        <button @click="submitBooking()" :disabled="loading" class="btn-primary flex-1">
                            <span x-show="!loading">{{ __('Book Now') }}</span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ __('Booking...') }}
                            </span>
                        </button>
                    </div>
                    <p class="text-xs text-[#A0A0A0] text-center mt-4">{{ __('By booking, you agree to our') }} <a href="{{ route($lp . 'terms') }}" class="text-[#F59E0B] hover:underline">{{ __('Terms') }}</a>.</p>
                </div>
            </div>

            {{-- ==================== ORDER SUMMARY SIDEBAR ==================== --}}
            <div class="lg:col-span-1 hidden lg:block">
                <div class="card sticky top-24 !p-0 overflow-hidden">
                    {{-- Header --}}
                    <div class="bg-[#111111] px-6 py-4 border-b border-[#2A2A2A]">
                        <h3 class="font-semibold text-sm uppercase tracking-wider text-[#A0A0A0]">{{ __('Order Summary') }}</h3>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        {{-- Location --}}
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="text-sm">
                                <p class="text-white font-medium">{{ $location->name }}</p>
                                <p class="text-[#A0A0A0] text-xs">{{ $location->address }}</p>
                            </div>
                        </div>

                        {{-- Date --}}
                        <div class="flex items-center gap-3" x-show="resolvedDate" x-transition x-cloak>
                            <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="text-sm">
                                <p class="text-white font-medium" x-text="formatSummaryDate()"></p>
                                <p class="text-[#A0A0A0] text-xs" x-show="time" x-cloak x-text="time ? formatTime(time) : ''"></p>
                            </div>
                        </div>

                        {{-- Lockers (multi-size cart) --}}
                        <div class="flex items-start gap-3" x-show="totalQty > 0" x-transition x-cloak>
                            <div class="w-8 h-8 rounded-lg bg-[#F59E0B]/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="text-sm flex-1">
                                <template x-for="line in cartItems" :key="line.size">
                                    <p class="text-white font-medium"><span x-text="line.qty"></span> × <span x-text="sizeLabelFor(line.size)"></span></p>
                                </template>
                                <p class="text-[#A0A0A0] text-xs mt-0.5" x-text="orderSummary ? orderSummary.durationLabel : ''"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div x-show="pricing && orderSummary" x-transition x-cloak>
                        <div class="border-t border-[#2A2A2A] px-6 py-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-[#A0A0A0]">{{ __('Subtotal') }}</span>
                                <span x-text="orderSummary ? formatPrice(orderSummary.subtotal) : ''"></span>
                            </div>
                            <div class="flex justify-between" x-show="orderSummary && orderSummary.serviceFee > 0">
                                <span class="text-[#A0A0A0]">{{ __('Service fee') }}</span>
                                <span x-text="orderSummary ? formatPrice(orderSummary.serviceFee) : ''"></span>
                            </div>
                        </div>
                        <div class="border-t border-[#2A2A2A] px-6 py-4">
                            <div class="flex justify-between font-bold text-lg">
                                <span>{{ __('Total') }}</span>
                                <span class="text-[#F59E0B]" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></span>
                            </div>
                            <p class="text-xs text-[#A0A0A0] text-right mt-1" x-text="orderSummary ? '~' + Math.round(orderSummary.totalRsd) + ' RSD' : ''"></p>
                        </div>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!totalQty || !duration" x-cloak class="px-6 py-6 text-center border-t border-[#2A2A2A]">
                        <p class="text-sm text-[#A0A0A0]">{{ __('Select options to see pricing') }}</p>
                    </div>

                    {{-- Pay cash footer --}}
                    <div class="bg-[#111111] px-6 py-3 border-t border-[#2A2A2A] text-xs text-[#A0A0A0] flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{ __('Pay cash on arrival') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Sticky Summary --}}
    <div class="fixed bottom-0 left-0 right-0 lg:hidden z-50 bg-[#1A1A1A] border-t border-[#2A2A2A] px-4 py-3 mobile-cta-bar"
         x-transition>
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div>
                <p class="text-xs text-[#A0A0A0]" x-show="!pricing">{{ __('Select options for price') }}</p>
                <div x-show="pricing && orderSummary" x-cloak>
                    <p class="text-lg font-bold text-[#F59E0B]" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></p>
                    <p class="text-xs text-[#A0A0A0]" x-text="totalQty > 0 ? cartItems.map(l => l.qty + '× ' + sizeLabelFor(l.size)).join(' + ') : ''"></p>
                </div>
            </div>
            {{-- Step 1 --}}
            <button x-show="step === 1" @click="goToStep(2)" :disabled="!canContinueStep1" class="btn-primary">
                {{ __('Continue') }} &rarr;
            </button>
            {{-- Step 2 --}}
            <button x-show="step === 2" @click="goToStep(3)" :disabled="!canContinueStep2 || loading" class="btn-primary">
                {{ __('Continue') }} &rarr;
            </button>
            {{-- Step 3 --}}
            <button x-show="step === 3" @click="submitGuestForm()" :disabled="!canContinueStep3 || loading" class="btn-primary">
                {{ __('Continue') }} &rarr;
            </button>
            {{-- Step 4 --}}
            <button x-show="step === 4" @click="submitBooking()" :disabled="loading" class="btn-primary">
                <span x-show="!loading">{{ __('Book Now') }}</span>
                <span x-show="loading">{{ __('Booking...') }}</span>
            </button>
        </div>
    </div>
    <div class="h-20 lg:hidden" x-show="step <= 2"></div>
</section>
@endsection
