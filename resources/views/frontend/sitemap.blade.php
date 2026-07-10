<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

@php $default = \App\Support\Locales::default(); @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @foreach($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <lastmod>{{ $url['lastmod'] }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        @foreach($url['alternates'] as $locale => $alt)
            <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $alt }}" />
        @endforeach
        @if(isset($url['alternates'][$default]))
            <xhtml:link rel="alternate" hreflang="x-default" href="{{ $url['alternates'][$default] }}" />
        @endif
    </url>
    @endforeach
</urlset>
