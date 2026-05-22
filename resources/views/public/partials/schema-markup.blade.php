@php
    use App\Helpers\SiteSettings;
    use App\Models\PricingRule;

    $minPrice = PricingRule::active()->min('price_eur');
    $maxPrice = PricingRule::active()->max('price_eur');
    $priceRange = ($minPrice && $maxPrice) ? 'EUR '.intval($minPrice).'-'.intval($maxPrice) : 'EUR 5-230';
    $minStandard = PricingRule::active()->where('locker_size', 'standard')->min('price_eur');
    $minLarge = PricingRule::active()->where('locker_size', 'large')->min('price_eur');

    $locale = app()->getLocale();
    $isSr = $locale === 'sr';

    // Rating + review count for AggregateRating. reviewCount must be an integer,
    // so we strip any "+" / text from the stored count (e.g. "70+" -> 70).
    $ratingValue = (float) (SiteSettings::get('google_rating', '4.9'));
    $reviewCount = (int) preg_replace('/\D/', '', (string) SiteSettings::get('google_review_count', '70')) ?: 70;

    $geo = SiteSettings::mapCenter();
    $sameAs = array_values(SiteSettings::social());
    $siteName = SiteSettings::siteName();
    $logoUrl = url('/images/logo.png');
    $heroUrl = url(SiteSettings::heroImage());
    $desc = $isSr
        ? 'Sigurno čuvanje prtljaga u Beogradu 24/7 — pametni ormarići, rezervacija online za 60 sekundi.'
        : '24/7 secure luggage storage in Belgrade with smart self-service lockers. Book online in 60 seconds.';

    $serviceName = $isSr ? 'Čuvanje prtljaga' : 'Luggage storage';

    // AggregateRating may only be emitted on a page where the reviews are
    // actually visible (the homepage) — otherwise it risks a Google penalty.
    $isHome = request()->routeIs('home') || request()->routeIs('sr.home');
@endphp
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "LocalBusiness",
    "@@id": "{{ url('/') }}/#business",
    "name": "{{ $siteName }}",
    "description": "{{ $desc }}",
    "url": "{{ url('/') }}",
    "telephone": "{{ SiteSettings::phoneTel() }}",
    "email": "{{ SiteSettings::email() }}",
    "image": "{{ $heroUrl }}",
    "logo": "{{ $logoUrl }}",
    "priceRange": "{{ $priceRange }}",
    "currenciesAccepted": "EUR",
    "paymentAccepted": "Cash",
    "address": {
        "@@type": "PostalAddress",
        @if($a = SiteSettings::address())"streetAddress": "{{ $a }}",@endif
        "addressLocality": "{{ SiteSettings::city() ?: 'Belgrade' }}",
        "addressCountry": "RS"
    },
    "geo": {
        "@@type": "GeoCoordinates",
        "latitude": {{ $geo['lat'] }},
        "longitude": {{ $geo['lng'] }}
    },
    "areaServed": {
        "@@type": "City",
        "name": "Belgrade"
    },
    "openingHoursSpecification": {
        "@@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "00:00",
        "closes": "23:59"
    },
@if($isHome)
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "{{ $ratingValue }}",
        "reviewCount": "{{ $reviewCount }}",
        "bestRating": "5",
        "worstRating": "1"
    },
@endif
@if($sameAs)
    "sameAs": {!! json_encode($sameAs, JSON_UNESCAPED_SLASHES) !!},
@endif
    "makesOffer": [
        {
            "@@type": "Offer",
            "name": "{{ $serviceName }} — {{ $isSr ? 'standardni ormarić' : 'standard locker' }}",
            "priceCurrency": "EUR",
            "price": "{{ $minStandard ? intval($minStandard) : 5 }}",
            "availability": "https://schema.org/InStock",
            "itemOffered": {
                "@@type": "Service",
                "name": "{{ $serviceName }}",
                "serviceType": "{{ $isSr ? 'Čuvanje prtljaga / garderoba' : 'Luggage storage' }}",
                "areaServed": "Belgrade"
            }
        },
        {
            "@@type": "Offer",
            "name": "{{ $serviceName }} — {{ $isSr ? 'veliki ormarić' : 'large locker' }}",
            "priceCurrency": "EUR",
            "price": "{{ $minLarge ? intval($minLarge) : 10 }}",
            "availability": "https://schema.org/InStock",
            "itemOffered": {
                "@@type": "Service",
                "name": "{{ $serviceName }}",
                "serviceType": "{{ $isSr ? 'Čuvanje prtljaga / garderoba' : 'Luggage storage' }}",
                "areaServed": "Belgrade"
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebSite",
    "@@id": "{{ url('/') }}/#website",
    "name": "{{ $siteName }}",
    "url": "{{ url('/') }}",
    "inLanguage": ["en-US", "sr-RS"],
    "publisher": { "@@id": "{{ url('/') }}/#business" }
}
</script>
