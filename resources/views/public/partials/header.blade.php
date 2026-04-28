@php
    $locale = app()->getLocale();
    $prefix = $locale === 'sr' ? 'sr.' : '';

    $currentRoute = Route::currentRouteName() ?? 'home';
    $routeParams = Route::current()?->parameters() ?? [];
    $switchRoute = $locale === 'en' ? 'sr.' . $currentRoute : str_replace('sr.', '', $currentRoute);
    try { $switchUrl = route($switchRoute, $routeParams); } catch (\Exception $e) { $switchUrl = $locale === 'en' ? '/sr' : '/'; }
@endphp
<header class="fixed top-0 left-0 right-0 site-header" x-data="{ mobileOpen: false, scrolled: false }" @scroll.window="scrolled = (window.scrollY > 20)" @keydown.escape.window="mobileOpen = false">
    <div class="header-bg transition-all duration-500 ease-in-out" :class="scrolled ? 'header-bg--scrolled' : ''"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex items-center justify-between h-20">
            <a href="{{ route($prefix . 'home') }}" class="flex items-center relative z-50">
                <img src="/images/logo.png" alt="{{ \App\Helpers\SiteSettings::siteName() }}" class="h-16 w-auto">
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
                        <img src="/images/flags/rs.svg" alt="SR" class="w-5 h-5"> SR
                    </a>
                @else
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-xs font-medium text-white/40 hover:text-white transition-colors duration-200 uppercase tracking-wider">
                        <img src="/images/flags/gb.svg" alt="EN" class="w-5 h-5"> EN
                    </a>
                @endif
                <a href="{{ route($prefix . 'locations.index') }}" class="btn-primary btn-sm">{{ __('Book Now') }}</a>
            </div>

            {{-- Hamburger button (hidden when drawer is open) --}}
            <button x-show="!mobileOpen" @click="mobileOpen = true" class="md:hidden relative z-50 w-10 h-10 flex flex-col items-center justify-center gap-1.5" aria-label="Menu">
                <span class="mobile-hamburger-line"></span>
                <span class="mobile-hamburger-line"></span>
            </button>
        </div>
    </div>

    {{-- Mobile backdrop --}}
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileOpen = false"
         x-cloak
         class="md:hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm">
    </div>

    {{-- Mobile slide-in panel (left side) --}}
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         x-cloak
         class="md:hidden fixed inset-y-0 left-0 z-50 w-72 mobile-drawer flex flex-col">

        {{-- Drawer header --}}
        <div class="flex items-center justify-between h-20 px-6">
            <a href="{{ route($prefix . 'home') }}" @click="mobileOpen = false" class="flex items-center">
                <img src="/images/logo.png" alt="{{ \App\Helpers\SiteSettings::siteName() }}" class="h-12 w-auto">
            </a>
            <div class="flex items-center gap-3">
                {{-- Language switch (only show opposite language) --}}
                @if($locale === 'en')
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-xs text-white/40 hover:text-white transition">
                        <img src="/images/flags/rs.svg" alt="SR" class="w-4 h-4"> SR
                    </a>
                @else
                    <a href="{{ $switchUrl }}" class="flex items-center gap-1.5 text-xs text-white/40 hover:text-white transition">
                        <img src="/images/flags/gb.svg" alt="EN" class="w-4 h-4"> EN
                    </a>
                @endif
                <button @click="mobileOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/40 hover:text-white hover:bg-white/5 transition-all" aria-label="Close menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Navigation links --}}
        <nav class="flex-1 px-4 py-6 flex flex-col gap-1 overflow-y-auto">
            <a @click="mobileOpen = false" href="{{ route($prefix . 'locations.index') }}" class="mobile-nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                {{ __('Locations') }}
            </a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'pricing') }}" class="mobile-nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                {{ __('Pricing') }}
            </a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'faq') }}" class="mobile-nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                {{ __('FAQ') }}
            </a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'blog.index') }}" class="mobile-nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>
                {{ __('Blog') }}
            </a>
            <a @click="mobileOpen = false" href="{{ route($prefix . 'contact') }}" class="mobile-nav-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                {{ __('Contact') }}
            </a>
        </nav>

        {{-- Drawer footer --}}
        <div class="px-4 pb-8">
            <div class="mobile-drawer-divider mb-5"></div>

            {{-- Book Now button --}}
            <a @click="mobileOpen = false" href="{{ route($prefix . 'locations.index') }}" class="btn-primary btn-sm w-full text-center">
                {{ __('Book Now') }}
            </a>
        </div>
    </div>
</header>
