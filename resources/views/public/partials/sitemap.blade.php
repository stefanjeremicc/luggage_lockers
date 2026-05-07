@php echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n"; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url><loc>{{ url('/') }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/') }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url('/sr') }}"/>
        <changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ url('/sr') }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/') }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url('/sr') }}"/>
        <changefreq>daily</changefreq><priority>1.0</priority></url>

    @foreach([
        ['en' => '/locations', 'sr' => '/sr/lokacije', 'priority' => 0.9, 'cf' => 'weekly'],
        ['en' => '/pricing', 'sr' => '/sr/cenovnik', 'priority' => 0.8, 'cf' => 'monthly'],
        ['en' => '/faq', 'sr' => '/sr/cesta-pitanja', 'priority' => 0.7, 'cf' => 'weekly'],
        ['en' => '/blog', 'sr' => '/sr/blog', 'priority' => 0.7, 'cf' => 'weekly'],
        ['en' => '/about', 'sr' => '/sr/o-nama', 'priority' => 0.5, 'cf' => 'monthly'],
        ['en' => '/contact', 'sr' => '/sr/kontakt', 'priority' => 0.5, 'cf' => 'monthly'],
        ['en' => '/terms', 'sr' => '/sr/uslovi', 'priority' => 0.3, 'cf' => 'yearly'],
        ['en' => '/privacy', 'sr' => '/sr/privatnost', 'priority' => 0.3, 'cf' => 'yearly'],
    ] as $page)
    <url><loc>{{ url($page['en']) }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url($page['en']) }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url($page['sr']) }}"/>
        <changefreq>{{ $page['cf'] }}</changefreq><priority>{{ $page['priority'] }}</priority></url>
    <url><loc>{{ url($page['sr']) }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url($page['en']) }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url($page['sr']) }}"/>
        <changefreq>{{ $page['cf'] }}</changefreq><priority>{{ $page['priority'] }}</priority></url>
    @endforeach

    @foreach($locations as $location)
    <url><loc>{{ url("/locations/{$location->slug}") }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url("/locations/{$location->slug}") }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url("/sr/lokacije/{$location->slug}") }}"/>
        <changefreq>weekly</changefreq><priority>0.8</priority></url>
    <url><loc>{{ url("/sr/lokacije/{$location->slug}") }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url("/locations/{$location->slug}") }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url("/sr/lokacije/{$location->slug}") }}"/>
        <changefreq>weekly</changefreq><priority>0.8</priority></url>
    @endforeach

    @foreach($posts as $post)
    <url><loc>{{ url("/blog/{$post->slug}") }}</loc>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url("/blog/{$post->slug}") }}"/>
        <xhtml:link rel="alternate" hreflang="sr" href="{{ url("/sr/blog/{$post->slug}") }}"/>
        <changefreq>monthly</changefreq><priority>0.6</priority></url>
    @endforeach
</urlset>
