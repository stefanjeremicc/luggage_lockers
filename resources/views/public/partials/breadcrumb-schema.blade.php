@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $rn = RouteFacade::currentRouteName();
    $isSr = app()->getLocale() === 'sr';
    $base = $rn ? preg_replace('/^sr\./', '', $rn) : null;
    $params = request()->route() ? request()->route()->parameters() : [];
    $prefix = $isSr ? 'sr.' : '';

    // Section label + parent-index route name per page.
    // [en label, sr label, parent index route base | null]
    $map = [
        'locations.index' => ['Locations', 'Lokacije', null],
        'locations.show'  => ['Locations', 'Lokacije', 'locations.index'],
        'booking.index'   => ['Book a locker', 'Rezerviši ormarić', 'locations.index'],
        'blog.index'      => ['Blog', 'Blog', null],
        'blog.show'       => ['Blog', 'Blog', 'blog.index'],
        'faq'             => ['FAQ', 'Najčešća pitanja', null],
        'about'           => ['About', 'O nama', null],
        'pricing'         => ['Pricing', 'Cenovnik', null],
        'contact'         => ['Contact', 'Kontakt', null],
        'terms'           => ['Terms of Service', 'Uslovi korišćenja', null],
        'privacy'         => ['Privacy Policy', 'Politika privatnosti', null],
        'near'            => ['Luggage storage near', 'Čuvanje prtljaga blizu', null],
    ];

    $crumbs = [];
    if ($base && isset($map[$base])) {
        $home = $isSr ? url('/sr') : url('/');
        $crumbs[] = ['name' => ($isSr ? 'Početna' : 'Home'), 'url' => $home];

        [$enLabel, $srLabel, $parent] = $map[$base];
        $label = $isSr ? $srLabel : $enLabel;

        // Parent index level (for detail pages).
        if ($parent) {
            try {
                $crumbs[] = ['name' => $label, 'url' => route($prefix.$parent)];
            } catch (\Throwable $e) { /* skip */ }
        }

        // Leaf: detail pages can set @section('breadcrumb_name'); else use label.
        $leaf = trim((string) View::yieldContent('breadcrumb_name'));
        $crumbs[] = [
            'name' => $leaf !== '' ? $leaf : $label,
            'url'  => url()->current(),
        ];
    }
@endphp
@if(count($crumbs) >= 2)
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
@foreach($crumbs as $i => $c)
        {
            "@@type": "ListItem",
            "position": {{ $i + 1 }},
            "name": {!! json_encode($c['name'], JSON_UNESCAPED_UNICODE) !!},
            "item": "{{ $c['url'] }}"
        }@if(!$loop->last),@endif
@endforeach
    ]
}
</script>
@endif
