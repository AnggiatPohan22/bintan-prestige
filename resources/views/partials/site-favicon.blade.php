@php
    $faviconAsset = isset($siteAssets)
        ? (($siteAssets['site.favicon'] ?? null) ?: ($siteAssets['site.logo.icon'] ?? null))
        : null;
@endphp

@if($faviconAsset?->url)
    <link rel="icon" href="{{ $faviconAsset->url }}">
    <link rel="shortcut icon" href="{{ $faviconAsset->url }}">
@endif
