@php $fp = app()->getLocale() === 'sr' ? 'sr.' : ''; @endphp
<footer class="border-t border-[#2A2A2A]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <img src="/images/logo.png" alt="Belgrade Luggage Locker" class="h-16 w-auto">
                <p class="mt-3 text-sm text-[#A0A0A0]">{{ __('24/7 secure luggage storage in Belgrade. Smart lockers, easy booking.') }}</p>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white mb-3">{{ __('Locations') }}</h4>
                <ul class="space-y-2 text-sm text-[#A0A0A0]">
                    <li><a href="{{ route($fp . 'locations.show', ['slug' => 'city-center']) }}" class="hover:text-white transition">City Center</a></li>
                    <li><a href="{{ route($fp . 'locations.show', ['slug' => 'kralja-milana']) }}" class="hover:text-white transition">Kralja Milana</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white mb-3">{{ __('Company') }}</h4>
                <ul class="space-y-2 text-sm text-[#A0A0A0]">
                    <li><a href="{{ route($fp . 'about') }}" class="hover:text-white transition">{{ __('About') }}</a></li>
                    <li><a href="{{ route($fp . 'faq') }}" class="hover:text-white transition">{{ __('FAQ') }}</a></li>
                    <li><a href="{{ route($fp . 'blog.index') }}" class="hover:text-white transition">{{ __('Blog') }}</a></li>
                    <li><a href="{{ route($fp . 'contact') }}" class="hover:text-white transition">{{ __('Contact') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white mb-3">{{ __('Legal') }}</h4>
                <ul class="space-y-2 text-sm text-[#A0A0A0]">
                    <li><a href="{{ route($fp . 'terms') }}" class="hover:text-white transition">{{ __('Terms & Conditions') }}</a></li>
                    <li><a href="{{ route($fp . 'privacy') }}" class="hover:text-white transition">{{ __('Privacy Policy') }}</a></li>
                </ul>
                <div class="mt-4 text-sm text-[#A0A0A0]">
                    <p>+381 65 332 2319</p>
                    <p>info@belgradeluggagelocker.com</p>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-[#2A2A2A] text-center text-xs text-[#A0A0A0]">
            &copy; {{ date('Y') }} Belgrade Luggage Locker. All rights reserved.
        </div>
    </div>
</footer>
