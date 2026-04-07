{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ url('/locations') }}</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>
    <url><loc>{{ url('/faq') }}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
    <url><loc>{{ url('/about') }}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    <url><loc>{{ url('/contact') }}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    <url><loc>{{ url('/blog') }}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
    @foreach($locations as $location)
    <url><loc>{{ url("/locations/{$location->slug}") }}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
    <url><loc>{{ url("/locations/{$location->slug}/book") }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    @endforeach
    @foreach($posts as $post)
    <url><loc>{{ url("/blog/{$post->slug}") }}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
    @endforeach
</urlset>
