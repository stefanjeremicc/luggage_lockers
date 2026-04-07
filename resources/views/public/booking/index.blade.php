@extends('layouts.public')

@section('title', "Book Luggage Storage — {$location->name}")
@section('meta_description', "Book a secure locker at {$location->name}, {$location->address}, Belgrade. Choose size and duration, get instant PIN.")

@section('content')
<section class="py-6 lg:py-12" x-data="bookingFlow" data-location-id="{{ $location->id }}">
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
        <div class="card mb-8 p-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row">
                <div class="sm:w-48 sm:h-auto h-32 flex-shrink-0">
                    <img src="/images/locations/{{ $location->slug }}.webp" alt="{{ $location->name }}" class="w-full h-full object-cover">
                </div>
                <div class="p-5 sm:p-6 flex flex-col justify-center">
                    <h1 class="text-2xl lg:text-3xl font-bold">{{ __('Book a Locker') }}</h1>
                    <p class="text-[#A0A0A0] mt-1 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $location->name }} &mdash; {{ $location->address }}
                    </p>
                    <p class="text-xs text-[#10B981] mt-2 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#10B981] inline-block"></span>
                        {{ __('Open 24/7') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="flex items-center gap-2 mb-8">
            @foreach([1 => __('Date & Locker'), 2 => __('Duration & Time'), 3 => __('Your Info'), 4 => __('Confirm')] as $num => $label)
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

                {{-- ==================== STEP 1: Date & Locker Type ==================== --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                    {{-- Date Selection --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('When are you arriving?') }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button @click="date = 'today'; customDate = null" class="pill" :class="{ 'active': date === 'today' }">{{ __('Today') }}</button>
                            <button @click="date = 'tomorrow'; customDate = null" class="pill" :class="{ 'active': date === 'tomorrow' }">{{ __('Tomorrow') }}</button>
                            <button @click="date = 'custom'" class="pill" :class="{ 'active': date === 'custom' }">{{ __('Pick date') }}</button>
                        </div>
                        {{-- Custom Calendar --}}
                        <div x-show="date === 'custom'" x-transition x-cloak class="mt-4 max-w-xs"
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
                                <template x-for="day in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="day">
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
                        </div>
                    </div>

                    {{-- Locker Type --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            {{ __('Choose your locker') }}
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Standard --}}
                            <button @click="lockerSize = 'standard'" class="locker-card group text-left"
                                    :class="lockerSize === 'standard' ? 'locker-card--active' : ''">
                                <div class="relative overflow-hidden rounded-t-xl h-36 bg-[#111111]">
                                    <img src="/images/lockers/standard.webp" alt="Standard Locker"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div x-show="lockerSize === 'standard'" x-transition class="absolute inset-0 bg-[#F59E0B]/10 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-semibold">{{ __('Standard') }}</span>
                                        <span class="text-[#F59E0B] font-bold text-sm">{{ __('from') }} &euro;5</span>
                                    </div>
                                    <p class="text-sm text-[#A0A0A0] mt-1">{{ __('1 suitcase & 1 bag') }}</p>
                                    <p class="text-xs text-[#A0A0A0] mt-0.5">40 &times; 35 &times; 55 cm</p>
                                </div>
                            </button>

                            {{-- Large --}}
                            <button @click="lockerSize = 'large'" class="locker-card group text-left"
                                    :class="lockerSize === 'large' ? 'locker-card--active' : ''">
                                <div class="relative overflow-hidden rounded-t-xl h-36 bg-[#111111]">
                                    <img src="/images/lockers/large.webp" alt="Large Locker"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div x-show="lockerSize === 'large'" x-transition class="absolute inset-0 bg-[#F59E0B]/10 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-semibold">{{ __('Large') }}</span>
                                        <span class="text-[#F59E0B] font-bold text-sm">{{ __('from') }} &euro;10</span>
                                    </div>
                                    <p class="text-sm text-[#A0A0A0] mt-1">{{ __('2 suitcases & 1 bag') }}</p>
                                    <p class="text-xs text-[#A0A0A0] mt-0.5">60 &times; 45 &times; 75 cm</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                            {{ __('How many lockers?') }}
                        </h3>
                        <div class="flex items-center gap-4">
                            <button @click="decrementQty()" class="qty-btn" :disabled="qty <= 1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <span class="text-xl font-bold w-8 text-center tabular-nums" x-text="qty"></span>
                            <button @click="incrementQty()" class="qty-btn" :disabled="qty >= maxQty">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>

                    <button @click="goToStep(2)" :disabled="!canContinueStep1" class="btn-primary w-full text-lg py-4">
                        {{ __('Continue') }} &rarr;
                    </button>
                </div>

                {{-- ==================== STEP 2: Duration & Time ==================== --}}
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                    {{-- Duration --}}
                    <div class="card mb-6">
                        <h3 class="font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('For how long?') }}
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="opt in durationOptions" :key="opt.key">
                                <button @click="duration = opt.key" class="duration-pill" :class="{ 'duration-pill--active': duration === opt.key }">
                                    <span class="text-sm font-medium" x-text="opt.label"></span>
                                    <span class="text-xs" :class="duration === opt.key ? 'text-black/70' : 'text-[#F59E0B]'" x-text="opt.price"></span>
                                </button>
                            </template>
                        </div>
                        <button @click="showMoreDurations = !showMoreDurations" class="text-xs text-[#F59E0B] mt-3 hover:underline cursor-pointer" x-show="!showMoreDurations">{{ __('Show more options') }} &darr;</button>
                        <div x-show="showMoreDurations" x-transition x-cloak class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                            <template x-for="opt in moreDurationOptions" :key="opt.key">
                                <button @click="duration = opt.key" class="duration-pill" :class="{ 'duration-pill--active': duration === opt.key }">
                                    <span class="text-sm font-medium" x-text="opt.label"></span>
                                    <span class="text-xs" :class="duration === opt.key ? 'text-black/70' : 'text-[#F59E0B]'" x-text="opt.price"></span>
                                </button>
                            </template>
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

                    {{-- Availability indicator --}}
                    <div x-show="resolvedDate && time && duration" x-transition x-cloak class="card mb-6 !py-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="availability[lockerSize]?.available > 3 ? 'bg-[#10B981]' : (availability[lockerSize]?.available > 0 ? 'bg-[#F59E0B]' : 'bg-[#EF4444]')"></span>
                                <span :class="availability[lockerSize]?.available > 3 ? 'text-[#10B981]' : (availability[lockerSize]?.available > 0 ? 'text-[#F59E0B]' : 'text-[#EF4444]')"
                                      x-text="availability[lockerSize]?.available > 0 ? availability[lockerSize].available + ' lockers available' : 'No lockers available'"></span>
                            </div>
                            <span class="text-[#A0A0A0]" x-text="lockerSize === 'standard' ? 'Standard' : 'Large'"></span>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(1)" class="btn-back">&larr;</button>
                        <button @click="goToStep(3)" :disabled="!canContinueStep2 || loading" class="btn-primary flex-1 text-lg py-4">
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

                        {{-- OAuth buttons (Coming soon) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                            <button disabled class="flex items-center justify-center gap-3 px-4 py-3 border border-[#2A2A2A] rounded-xl opacity-50 cursor-not-allowed relative">
                                <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                <span class="text-sm">{{ __('Google') }}</span>
                                <span class="absolute -top-2 -right-2 text-[10px] bg-[#2A2A2A] text-[#A0A0A0] px-2 py-0.5 rounded-full">{{ __('Coming soon') }}</span>
                            </button>
                            <button disabled class="flex items-center justify-center gap-3 px-4 py-3 border border-[#2A2A2A] rounded-xl opacity-50 cursor-not-allowed relative">
                                <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M11.4 24H0V12.6h11.4V24zM24 24H12.6V12.6H24V24zM11.4 11.4H0V0h11.4v11.4zM24 11.4H12.6V0H24v11.4z" fill="#00A4EF"/></svg>
                                <span class="text-sm">{{ __('Microsoft') }}</span>
                                <span class="absolute -top-2 -right-2 text-[10px] bg-[#2A2A2A] text-[#A0A0A0] px-2 py-0.5 rounded-full">{{ __('Coming soon') }}</span>
                            </button>
                        </div>

                        <div class="relative mb-6">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#2A2A2A]"></div></div>
                            <div class="relative flex justify-center text-sm"><span class="px-4 bg-[#1A1A1A] text-[#A0A0A0]">{{ __('or continue as guest') }}</span></div>
                        </div>

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
                                <div class="flex" x-data="{ phoneOpen: false }" @click.outside="phoneOpen = false">
                                    {{-- Country code dropdown --}}
                                    <button @click="phoneOpen = !phoneOpen" type="button"
                                            class="flex items-center gap-1.5 px-3 bg-[#111111] border border-[#2A2A2A] border-r-0 rounded-l-xl text-sm whitespace-nowrap hover:border-[#F59E0B] transition flex-shrink-0">
                                        <span x-text="countryFlag()"></span>
                                        <span class="text-[#A0A0A0]" x-text="selectedDialCode()"></span>
                                        <svg class="w-3 h-3 text-[#A0A0A0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="phoneOpen" x-transition x-cloak class="absolute top-full left-0 right-0 mt-1 z-50 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden shadow-2xl">
                                        <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                            <template x-for="c in phoneCountries" :key="c.code">
                                                <button @click="guestForm.country_code = c.code; phoneOpen = false" type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 transition-colors"
                                                        :class="guestForm.country_code === c.code ? 'bg-[#F59E0B] text-black font-medium' : 'text-[#A0A0A0] hover:bg-[#2A2A2A] hover:text-white'">
                                                    <span x-text="c.flag"></span>
                                                    <span x-text="c.name"></span>
                                                    <span class="ml-auto text-xs" :class="guestForm.country_code === c.code ? 'text-black/60' : 'text-[#A0A0A0]'" x-text="c.dial"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    {{-- Phone number input --}}
                                    <input type="tel" x-model="guestForm.phone" class="input-field !rounded-l-none" placeholder="65 123 4567" required>
                                </div>
                                <p x-show="formErrors.phone" x-cloak class="text-xs text-[#EF4444] mt-1" x-text="formErrors.phone"></p>
                            </div>
                            {{-- Country --}}
                            <div>
                                <label class="block text-sm text-[#A0A0A0] mb-1.5">{{ __('Country') }} *</label>
                                <div class="custom-select-wrapper" x-data="{ open: false }" @click.outside="open = false">
                                    <button @click="open = !open" type="button" class="custom-select">
                                        <span class="flex items-center gap-2 text-white"><span x-text="countryFlag()"></span> <span x-text="countryLabel()"></span></span>
                                        <svg class="w-5 h-5 text-[#A0A0A0] transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-transition x-cloak class="custom-select-dropdown">
                                        <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                            <template x-for="c in phoneCountries" :key="c.code">
                                                <button @click="guestForm.country_code = c.code; open = false" type="button"
                                                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 transition-colors"
                                                        :class="guestForm.country_code === c.code ? 'bg-[#F59E0B] text-black font-medium' : 'text-[#A0A0A0] hover:bg-[#2A2A2A] hover:text-white'">
                                                    <span x-text="c.flag"></span>
                                                    <span x-text="c.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <input type="checkbox" x-model="guestForm.agree_terms" id="agree_terms" class="rounded border-[#2A2A2A] bg-[#111111] text-[#F59E0B] mt-1">
                                <label for="agree_terms" class="text-sm text-[#A0A0A0]">{{ __('I agree to the') }} <a href="{{ route($lp . 'terms') }}" class="text-[#F59E0B] hover:underline" target="_blank">{{ __('Terms & Conditions') }}</a> *</label>
                            </div>
                            <div class="flex items-start gap-2">
                                <input type="checkbox" x-model="guestForm.whatsapp_opt_in" id="whatsapp" class="rounded border-[#2A2A2A] bg-[#111111] text-[#F59E0B] mt-1">
                                <label for="whatsapp" class="text-sm text-[#A0A0A0]">{{ __('Send booking updates via WhatsApp') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(2)" class="btn-back">&larr;</button>
                        <button @click="submitGuestForm()" :disabled="!canContinueStep3 || loading" class="btn-primary flex-1 text-lg py-4">
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

                        <div class="space-y-4">
                            <div class="flex items-start gap-3 text-sm">
                                <svg class="w-5 h-5 text-[#A0A0A0] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <span class="text-[#A0A0A0]">{{ __('Location') }}</span>
                                    <p class="font-medium">{{ $location->name }}</p>
                                    <p class="text-[#A0A0A0] text-xs">{{ $location->address }}</p>
                                </div>
                            </div>
                            <div class="border-t border-[#2A2A2A]"></div>
                            <div class="flex items-start gap-3 text-sm">
                                <svg class="w-5 h-5 text-[#A0A0A0] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <div>
                                    <span class="text-[#A0A0A0]">{{ __('Check-in') }}</span>
                                    <p class="font-medium" x-text="formatConfirmDate()"></p>
                                </div>
                            </div>
                            <div class="border-t border-[#2A2A2A]"></div>
                            <div class="flex items-start gap-3 text-sm">
                                <svg class="w-5 h-5 text-[#A0A0A0] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <div>
                                    <span class="text-[#A0A0A0]">{{ __('Lockers') }}</span>
                                    <p class="font-medium" x-text="qty + ' x ' + (lockerSize === 'standard' ? 'Standard' : 'Large') + ' (' + (orderSummary ? orderSummary.durationLabel : '') + ')'"></p>
                                </div>
                            </div>
                            <div class="border-t border-[#2A2A2A]"></div>
                            <div class="flex items-start gap-3 text-sm">
                                <svg class="w-5 h-5 text-[#A0A0A0] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div>
                                    <span class="text-[#A0A0A0]">{{ __('Customer') }}</span>
                                    <p class="font-medium" x-text="customer ? customer.full_name : ''"></p>
                                    <p class="text-[#A0A0A0] text-xs" x-text="customer ? customer.email : ''"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-6 border-[#F59E0B]/30 bg-[#F59E0B]/5">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-[#F59E0B] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <div>
                                <p class="text-sm font-medium">{{ __('Pay') }} <span class="text-[#F59E0B] font-bold" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></span> {{ __('when you arrive') }}</p>
                                <p class="text-xs text-[#A0A0A0] mt-1" x-text="orderSummary ? '~' + Math.round(orderSummary.totalRsd) + ' RSD' : ''"></p>
                                <p class="text-xs text-[#A0A0A0] mt-1">{{ __('We accept EUR and RSD.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="goToStep(3)" class="btn-back">&larr;</button>
                        <button @click="submitBooking()" :disabled="loading" class="btn-primary flex-1 text-lg py-4">
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
                <div class="card sticky top-8">
                    <h3 class="font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        {{ __('Order Summary') }}
                    </h3>

                    {{-- Location --}}
                    <div class="text-sm space-y-1 pb-4 border-b border-[#2A2A2A]">
                        <p class="text-white font-medium">{{ $location->name }}</p>
                        <p class="text-[#A0A0A0] text-xs">{{ $location->address }}</p>
                    </div>

                    {{-- Date --}}
                    <div class="py-3 border-b border-[#2A2A2A] text-sm" x-show="resolvedDate" x-transition x-cloak>
                        <div class="flex items-center gap-2 text-[#A0A0A0]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span x-text="formatSummaryDate()"></span>
                        </div>
                        <div class="flex items-center gap-2 text-[#A0A0A0] mt-1" x-show="time" x-cloak>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="time ? formatTime(time) : ''"></span>
                        </div>
                    </div>

                    {{-- Locker --}}
                    <div class="py-3 border-b border-[#2A2A2A] text-sm" x-show="lockerSize" x-transition x-cloak>
                        <div class="flex items-center gap-3">
                            <img :src="lockerSize === 'standard' ? '/images/lockers/standard.webp' : '/images/lockers/large.webp'"
                                 class="w-10 h-10 rounded-lg object-cover" :alt="lockerSize">
                            <div>
                                <p class="font-medium" x-text="qty + ' x ' + (lockerSize === 'standard' ? 'Standard' : 'Large')"></p>
                                <p class="text-xs text-[#A0A0A0]" x-text="orderSummary ? orderSummary.durationLabel : ''"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div x-show="pricing && orderSummary" x-transition x-cloak>
                        <div class="py-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-[#A0A0A0]">{{ __('Subtotal') }}</span>
                                <span x-text="orderSummary ? formatPrice(orderSummary.subtotal) : ''"></span>
                            </div>
                            <div class="flex justify-between" x-show="orderSummary && orderSummary.serviceFee > 0">
                                <span class="text-[#A0A0A0]">{{ __('Service fee') }}</span>
                                <span x-text="orderSummary ? formatPrice(orderSummary.serviceFee) : ''"></span>
                            </div>
                        </div>
                        <div class="border-t border-[#2A2A2A] pt-3">
                            <div class="flex justify-between font-bold text-lg">
                                <span>{{ __('Total') }}</span>
                                <span class="text-[#F59E0B]" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></span>
                            </div>
                            <p class="text-xs text-[#A0A0A0] text-right mt-1" x-text="orderSummary ? '~' + Math.round(orderSummary.totalRsd) + ' RSD' : ''"></p>
                        </div>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!lockerSize || !duration" class="py-6 text-center">
                        <p class="text-sm text-[#A0A0A0]">{{ __('Select options to see pricing') }}</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#2A2A2A] text-sm text-[#A0A0A0] flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>{{ __('Pay cash on arrival') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Sticky Summary --}}
    <div class="fixed bottom-0 left-0 right-0 lg:hidden z-50 bg-[#1A1A1A] border-t border-[#2A2A2A] px-4 py-3"
         x-show="step <= 2" x-transition>
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div>
                <p class="text-xs text-[#A0A0A0]" x-show="!pricing">{{ __('Select options for price') }}</p>
                <div x-show="pricing && orderSummary" x-cloak>
                    <p class="text-lg font-bold text-[#F59E0B]" x-text="orderSummary ? formatPrice(orderSummary.total) : ''"></p>
                    <p class="text-xs text-[#A0A0A0]" x-text="lockerSize ? qty + 'x ' + (lockerSize === 'standard' ? 'Standard' : 'Large') : ''"></p>
                </div>
            </div>
            <button @click="step === 1 ? goToStep(2) : goToStep(3)"
                    :disabled="step === 1 ? !canContinueStep1 : !canContinueStep2"
                    class="btn-primary px-6 py-3 text-sm">
                {{ __('Continue') }} &rarr;
            </button>
        </div>
    </div>
    <div class="h-20 lg:hidden" x-show="step <= 2"></div>
</section>
@endsection
