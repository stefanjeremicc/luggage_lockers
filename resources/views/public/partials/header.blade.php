@php
    $locale = app()->getLocale();
    $prefix = $locale === 'sr' ? 'sr.' : '';

    $currentRoute = Route::currentRouteName() ?? 'home';
    $routeParams = Route::current()?->parameters() ?? [];
    $switchRoute = $locale === 'en' ? 'sr.' . $currentRoute : str_replace('sr.', '', $currentRoute);
    try { $switchUrl = route($switchRoute, $routeParams); } catch (\Exception $e) { $switchUrl = $locale === 'en' ? '/sr' : '/'; }
@endphp
<header class="fixed top-0 left-0 right-0 z-50" x-data="{ mobileOpen: false, scrolled: false }" @scroll.window="scrolled = (window.scrollY > 20)">
    <div class="absolute inset-0 transition-all duration-500 ease-in-out" :class="scrolled ? 'bg-[#0A0A0A]/85 backdrop-blur-xl' : 'bg-transparent'" style="border-bottom: 1px solid transparent;" :style="scrolled ? 'border-bottom-color: rgba(255,255,255,0.06)' : 'border-bottom-color: transparent'"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex items-center justify-between h-20">
            <a href="{{ route($prefix . 'home') }}" class="flex items-center relative z-50">
                <img src="/images/logo.png" alt="Belgrade Luggage Locker" class="h-16 w-auto">
            </a>

            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route($prefix . 'locations.index') }}" class="text-[15px] text-white hover:text-white/50 transition-colors duration-200">{{ __('Locations') }}</a>
                <a href="{{ route($prefix . 'pricing') }}" class="text-[15px] text-white hover:text-white/50 transition-colors duration-200">{{ __('Pricing') }}</a>
                <a href="{{ route($prefix . 'faq') }}" class="text-[15px] text-white hover:text-white/50 transition-colors duration-200">{{ __('FAQ') }}</a>
                <a href="{{ route($prefix . 'blog.index') }}" class="text-[15px] text-white hover:text-white/50 transition-colors duration-200">{{ __('Blog') }}</a>
                <a href="{{ route($prefix . 'contact') }}" class="text-[15px] text-white hover:text-white/50 transition-colors duration-200">{{ __('Contact') }}</a>
            </nav>

            <div class="hidden md:flex items-center gap-4">
                @if($locale === 'en')
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-xs font-medium text-white/40 hover:text-white transition-colors duration-200 uppercase tracking-wider">
                        <img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-srbija.svg" alt="SR" class="w-5 h-5"> SR
                    </a>
                @else
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-xs font-medium text-white/40 hover:text-white transition-colors duration-200 uppercase tracking-wider">
                        <img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-england.svg" alt="EN" class="w-5 h-5"> EN
                    </a>
                @endif
                <a href="{{ route($prefix . 'locations.index') }}" class="btn-primary text-sm !py-2.5 !px-5">{{ __('Book Now') }}</a>
            </div>

            <button @click="mobileOpen = !mobileOpen" class="md:hidden relative z-50 w-10 h-10 flex items-center justify-center" aria-label="Menu">
                <svg x-show="!mobileOpen" class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobileOpen" x-cloak class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click.self="mobileOpen = false" x-cloak class="md:hidden fixed inset-0 z-40 bg-[#0A0A0A]/95 backdrop-blur-2xl flex flex-col items-center justify-center">
        <button @click="mobileOpen = false" class="absolute top-6 right-4 w-10 h-10 flex items-center justify-center text-white/60 hover:text-white transition" aria-label="Close menu">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <nav class="flex flex-col items-center gap-7">
            <a @click="mobileOpen = false" href="{{ route($prefix . 'locations.index') }}" class="text-2xl text-white hover:text-white/50 transition">{{ __('Locations') }}</a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'pricing') }}" class="text-2xl text-white hover:text-white/50 transition">{{ __('Pricing') }}</a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'faq') }}" class="text-2xl text-white hover:text-white/50 transition">{{ __('FAQ') }}</a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'blog.index') }}" class="text-2xl text-white hover:text-white/50 transition">{{ __('Blog') }}</a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'contact') }}" class="text-2xl text-white hover:text-white/50 transition">{{ __('Contact') }}</a>

            <div class="w-12 h-px bg-white/10 my-1"></div>

            <div class="flex items-center gap-4">
                @if($locale === 'en')
                    <span class="flex items-center gap-1.5 text-sm text-white font-medium"><img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-england.svg" alt="EN" class="w-5 h-5"> EN</span>
                    <span class="text-white/20">/</span>
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-sm text-white/40 hover:text-white transition"><img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-srbija.svg" alt="SR" class="w-5 h-5"> SR</a>
                @else
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-sm text-white/40 hover:text-white transition"><img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-england.svg" alt="EN" class="w-5 h-5"> EN</a>
                    <span class="text-white/20">/</span>
                    <span class="flex items-center gap-1.5 text-sm text-white font-medium"><img src="https://www.sodavoda.com/images/icons/soda-voda-vartolomej-srbija.svg" alt="SR" class="w-5 h-5"> SR</span>
                @endif
            </div>

            <a @click="mobileOpen = false" href="{{ route($prefix . 'locations.index') }}" class="btn-primary text-lg !px-10 !py-3.5 mt-2">{{ __('Book Now') }}</a>
        </nav>
    </div>
</header>
