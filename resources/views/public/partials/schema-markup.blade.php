@php
    $minPrice = \App\Models\PricingRule::active()->min('price_eur');
    $maxPrice = \App\Models\PricingRule::active()->max('price_eur');
    $priceRange = ($minPrice && $maxPrice) ? 'EUR '.intval($minPrice).'-'.intval($maxPrice) : 'EUR 5-230';
@endphp
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "LocalBusiness",
    "name": "{{ \App\Helpers\SiteSettings::siteName() }}",
    "description": "24/7 secure luggage storage in Belgrade with smart lockers",
    "url": "{{ url('/') }}",
    "telephone": "{{ \App\Helpers\SiteSettings::phoneTel() }}",
    "email": "{{ \App\Helpers\SiteSettings::email() }}",
    "address": {
        "@@type": "PostalAddress",
        @if($a = \App\Helpers\SiteSettings::address())"streetAddress": "{{ $a }}",@endif
        "addressLocality": "{{ \App\Helpers\SiteSettings::city() ?: 'Belgrade' }}",
        "addressCountry": "RS"
    },
    "openingHoursSpecification": {
        "@@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "00:00",
        "closes": "23:59"
    },
    "priceRange": "{{ $priceRange }}"
}
</script>
