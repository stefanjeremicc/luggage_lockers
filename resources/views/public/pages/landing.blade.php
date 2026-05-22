@extends('layouts.public')

@php
    use App\Helpers\SiteSettings;
    use App\Models\PricingRule;

    $locale = app()->getLocale();
    $isSr = $locale === 'sr';
    $title = $landing->title;
    $metaTitle = $landing->meta_title ?: $title;
    $metaDesc = $landing->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($landing->content ?? ''), 155);
    $hero = is_array($landing->sections) ? ($landing->sections['hero'] ?? []) : [];
    $heroTitle = $hero['title'] ?? $title;
    $heroSub = $hero['subtitle'] ?? null;
    $poiName = $landing->section('poi') ?: $title;
    $geo = $landing->section('geo', []);

    $loc = $landing->location;
    $bookingHref = $loc
        ? route($lp . 'booking.index', ['slug' => $loc->slug])
        : route($lp . 'locations.index');

    $minPrice = PricingRule::active()->min('price_eur');
    $minPriceLabel = $minPrice ? '€'.rtrim(rtrim(number_format($minPrice, 2), '0'), '.') : null;
    $rating = SiteSettings::get('google_rating', '4.9');

    // Straight-line distance from the POI to the linked location (if both known).
    $distanceKm = null;
    if ($loc && $loc->lat && $loc->lng && !empty($geo['lat']) && !empty($geo['lng'])) {
        $earth = 6371.0;
        $dLat = deg2rad($loc->lat - $geo['lat']);
        $dLng = deg2rad($loc->lng - $geo['lng']);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($geo['lat'])) * cos(deg2rad($loc->lat)) * sin($dLng / 2) ** 2;
        $distanceKm = round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    // Map focus point: linked location, else POI.
    $mapLat = $loc->lat ?? ($geo['lat'] ?? null);
    $mapLng = $loc->lng ?? ($geo['lng'] ?? null);

    // Auto-built FAQ (POI-aware) — also emitted as FAQPage schema.
    $distanceText = $distanceKm !== null
        ? ($isSr ? "oko {$distanceKm} km" : "about {$distanceKm} km")
        : ($isSr ? 'na kratkoj šetnji' : 'a short walk away');
    $locName = $loc ? $loc->nameFor($locale) : ($isSr ? 'naša lokacija' : 'our location');
    $faqs = $isSr ? [
        ["Koliko je čuvanje prtljaga udaljeno od {$poiName}?", "Naša najbliža lokacija sa pametnim ormarićima, {$locName}, udaljena je {$distanceText} od {$poiName}."],
        ["Da li je čuvanje prtljaga blizu {$poiName} dostupno 24/7?", "Da. Pametni ormarići su samouslužni i dostupni 24 sata dnevno, svakog dana."],
        ["Koliko košta čuvanje prtljaga blizu {$poiName}?", ($minPriceLabel ? "Cene počinju od {$minPriceLabel}. " : '')."Veličinu ormarića i trajanje biraš pri online rezervaciji, a plaćaš keš pri dolasku."],
        ["Moram li da rezervišem unapred?", "Online rezervacija traje oko 60 sekundi i garantuje ormarić, ali možeš i na licu mesta ako ima slobodnih."],
    ] : [
        ["How far is the luggage storage from {$poiName}?", "Our nearest smart-locker location, {$locName}, is {$distanceText} from {$poiName}."],
        ["Is luggage storage near {$poiName} available 24/7?", "Yes. Our smart lockers are self-service and accessible 24 hours a day, every day."],
        ["How much does luggage storage near {$poiName} cost?", ($minPriceLabel ? "Prices start from {$minPriceLabel}. " : '')."You choose the locker size and duration when you book online and pay cash on arrival."],
        ["Do I need to book in advance?", "Booking online takes about 60 seconds and guarantees a locker, but you can also book on the spot if lockers are free."],
    ];

    $otherLandings = \App\Models\Page::landings()->published()
        ->where('locale', $locale)->where('slug', '!=', $landing->slug)
        ->inRandomOrder()->limit(6)->get();
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@section('breadcrumb_name', $title)

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
@foreach($faqs as $i => $f)
        {
            "@@type": "Question",
            "name": {!! json_encode($f[0], JSON_UNESCAPED_UNICODE) !!},
            "acceptedAnswer": { "@@type": "Answer", "text": {!! json_encode($f[1], JSON_UNESCAPED_UNICODE) !!} }
        }@if(!$loop->last),@endif
@endforeach
    ]
}
</script>
@endpush

@section('head')
@if($landing->canonical_url)<link rel="canonical" href="{{ $landing->canonical_url }}">@endif
@if($landing->og_title)<meta property="og:title" content="{{ $landing->og_title }}">@endif
@if($landing->og_description)<meta property="og:description" content="{{ $landing->og_description }}">@endif
@if($landing->og_image)<meta property="og:image" content="{{ url($landing->og_image) }}"><meta name="twitter:image" content="{{ url($landing->og_image) }}">@endif
@if($mapLat && $mapLng)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<style>
    .leaflet-container { background:#0A0A0A; font-family:inherit; }
    .leaflet-control-attribution { background:rgba(15,15,15,.85)!important; color:#6B7280!important; font-size:10px!important; }
    .leaflet-control-attribution a { color:#A0A0A0!important; }
    .leaflet-control-zoom a { background:#1A1A1A!important; color:#F59E0B!important; border-color:#2A2A2A!important; }
</style>
@endif
@endsection

@section('content')
{{-- Hero --}}
<section class="relative py-16 lg:py-24 bg-gradient-to-b from-[#0F0F0F] to-[#0A0A0A]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">{{ $heroTitle }}</h1>
        @if($heroSub)
        <p class="text-lg text-[#A0A0A0] mt-5 max-w-2xl mx-auto leading-relaxed">{{ $heroSub }}</p>
        @endif

        {{-- Trust line --}}
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-[#A0A0A0]">
            <span class="inline-flex items-center gap-1.5"><span class="text-[#F59E0B]">★</span> {{ $rating }} {{ __('on Google') }}</span>
            <span class="inline-flex items-center gap-1.5">🔒 {{ __('Secure smart lockers') }}</span>
            <span class="inline-flex items-center gap-1.5">🕒 {{ __('Open 24/7') }}</span>
            @if($minPriceLabel)<span class="inline-flex items-center gap-1.5">💶 {{ __('From') }} {{ $minPriceLabel }}</span>@endif
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ $bookingHref }}" class="btn-primary inline-flex items-center gap-2">
                {{ __('Book a Locker') }}{{ $minPriceLabel ? ' — '.__('From').' '.$minPriceLabel : '' }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#how-it-works" data-scroll-to="how-it-works" class="btn-outline">{{ __('How It Works') }}</a>
        </div>
    </div>
</section>

{{-- Closest location — bookable card --}}
@if($loc)
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-xs uppercase tracking-wide text-[#F59E0B] font-semibold mb-3">{{ __('Closest location') }}</p>
        <div class="card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-white">{{ $loc->nameFor($locale) }}</h2>
                <p class="text-sm text-[#A0A0A0] mt-1">{{ $loc->addressFor($locale) }}{{ $loc->cityFor($locale) ? ', '.$loc->cityFor($locale) : '' }}</p>
                <div class="flex items-center gap-3 text-xs text-[#A0A0A0] mt-2">
                    <span>{{ $loc->is_24h ? __('Open 24/7') : ($loc->opening_time.' – '.$loc->closing_time) }}</span>
                    @if($distanceKm !== null)<span>·</span><span class="text-[#F59E0B] font-semibold">{{ number_format($distanceKm, 1) }} km {{ __('from here') }}</span>@endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route($lp.'locations.show', $loc->slug) }}" class="btn-outline text-sm">{{ __('Details') }}</a>
                <a href="{{ $bookingHref }}" class="btn-primary text-sm">{{ __('Book this locker') }}</a>
            </div>
        </div>

        @if($mapLat && $mapLng)
        <div id="landingMap" class="mt-4 h-64 rounded-xl overflow-hidden border border-[#2A2A2A]"
             data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}"
             @if(!empty($geo['lat']) && !empty($geo['lng'])) data-poi-lat="{{ $geo['lat'] }}" data-poi-lng="{{ $geo['lng'] }}" data-poi-name="{{ $poiName }}" @endif
             @if($loc) data-loc-name="{{ $loc->nameFor($locale) }}" @endif></div>
        @endif
    </div>
</section>
@endif

{{-- How it works --}}
<section id="how-it-works" class="py-12 bg-[#0F0F0F] border-y border-[#1A1A1A]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-center mb-8">{{ __('How It Works') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach([
                ['1', __('Book online'), __('Pick a locker size and how long you need it — it takes about 60 seconds.')],
                ['2', __('Get your PIN'), __('You receive a personal code to open your smart locker.')],
                ['3', __('Drop & explore'), __('Store your bags and enjoy Belgrade hands-free, 24/7.')],
            ] as $step)
            <div class="text-center">
                <div class="w-10 h-10 mx-auto rounded-full bg-[#F59E0B] text-black font-bold flex items-center justify-center mb-3">{{ $step[0] }}</div>
                <h3 class="font-semibold text-white mb-1">{{ $step[1] }}</h3>
                <p class="text-sm text-[#A0A0A0] leading-relaxed">{{ $step[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Admin-authored rich content --}}
@if($landing->content)
<section class="py-12 lg:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-invert max-w-none text-[#D0D0D0] leading-relaxed">{!! $landing->content !!}</div>
    </div>
</section>
@endif

{{-- FAQ --}}
<section class="py-12 bg-[#0F0F0F] border-y border-[#1A1A1A]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">{{ __('Frequently asked questions') }}</h2>
        <div class="space-y-3">
            @foreach($faqs as $f)
            <details class="card group">
                <summary class="cursor-pointer font-semibold text-white list-none flex items-center justify-between gap-3">
                    {{ $f[0] }}
                    <span class="text-[#F59E0B] group-open:rotate-45 transition shrink-0">+</span>
                </summary>
                <p class="text-sm text-[#A0A0A0] mt-3 leading-relaxed">{{ $f[1] }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- Other nearby landing pages --}}
@if($otherLandings->count())
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">{{ $isSr ? 'Čuvanje prtljaga na drugim lokacijama' : 'Luggage storage at other spots' }}</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($otherLandings as $o)
            <a href="{{ route($lp.'near', ['slug' => $o->slug]) }}" class="px-3 py-1.5 rounded-full border border-[#2A2A2A] text-sm text-[#A0A0A0] hover:border-[#F59E0B] hover:text-white transition">{{ $o->section('poi') ?: $o->title }}</a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Final CTA --}}
<section class="py-14 text-center bg-gradient-to-b from-[#0A0A0A] to-[#0F0F0F]">
    <div class="max-w-2xl mx-auto px-4">
        <h2 class="text-2xl sm:text-3xl font-bold mb-3">{{ $isSr ? 'Spremni da ostavite prtljag?' : 'Ready to drop your bags?' }}</h2>
        <p class="text-[#A0A0A0] mb-6">{{ $isSr ? 'Rezerviši za 60 sekundi, plati keš pri dolasku.' : 'Book in 60 seconds, pay cash on arrival.' }}</p>
        <a href="{{ $bookingHref }}" class="btn-primary inline-flex items-center gap-2">
            {{ __('Book a Locker') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
</section>
@endsection

@push('scripts')
@if($mapLat && $mapLng)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('landingMap');
        if (!el || typeof L === 'undefined') return;
        var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
        var map = L.map('landingMap', { center: [lat, lng], zoom: 15, scrollWheelZoom: false });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 20
        }).addTo(map);
        var pts = [];
        var locMarker = L.marker([lat, lng]).addTo(map);
        if (el.dataset.locName) locMarker.bindPopup(el.dataset.locName);
        pts.push([lat, lng]);
        if (el.dataset.poiLat && el.dataset.poiLng) {
            var pLat = parseFloat(el.dataset.poiLat), pLng = parseFloat(el.dataset.poiLng);
            var poi = L.circleMarker([pLat, pLng], { radius: 7, color: '#F59E0B', fillColor: '#F59E0B', fillOpacity: .9 }).addTo(map);
            if (el.dataset.poiName) poi.bindPopup(el.dataset.poiName);
            pts.push([pLat, pLng]);
        }
        if (pts.length > 1) map.fitBounds(pts, { padding: [40, 40] });
    });
</script>
@endif
@endpush
