@php
    $seoDefaults = $seoDefaultSettings ?? [];
    $seoDefaultImage = isset($siteAssets) ? ($siteAssets[\App\Support\SeoDefaultSettings::OG_IMAGE_KEY] ?? null) : null;
    $shareImage = $seoImage ?? $seoDefaultImage ?? (isset($siteAssets) ? ($siteAssets['site.social_share.default_image'] ?? null) : null);
    $identity = $businessIdentity ?? [];
    $siteName = $seoDefaults['site_name'] ?: ($identity['brand_name'] ?? config('app.name'));
    $baseTitle = $seoTitle ?? $title ?? $seoDefaults['meta_title'] ?? $siteName;
    $pageTitle = \App\Support\SeoDefaultSettings::titleWithSuffix($baseTitle, $seoDefaults);
    $pageDescription = $seoDescription ?? $seoDefaults['meta_description'] ?? ($identity['short_description'] ?? config('app.name'));
    $pageKeywords = $seoKeywords ?? $seoDefaults['keywords'] ?? null;
    $canonical = \App\Support\SeoDefaultSettings::canonicalUrl($canonicalUrl ?? null, $seoDefaults);
    $shareTitle = $socialShareTitle ?? $seoDefaults['og_title'] ?? $pageTitle;
    $shareTitle = $shareTitle ?: $pageTitle;
    $shareDescription = $socialShareDescription ?? $seoDefaults['og_description'] ?? $pageDescription;
    $shareDescription = $shareDescription ?: $pageDescription;
    $shareImageUrl = is_string($shareImage) ? $shareImage : $shareImage?->url;
    $shareImageAlt = $seoImageAlt ?? $seoDefaults['og_image_alt'] ?? (is_string($shareImage) ? $pageTitle : $shareImage?->alt);
@endphp

<meta name="description" content="{{ $pageDescription }}">
@if($pageKeywords)
    <meta name="keywords" content="{{ $pageKeywords }}">
@endif
<meta name="robots" content="{{ $seoRobots ?? ($seoDefaults['robots'] ?? 'index, follow') }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="{{ $socialShareType ?? 'website' }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ \App\Support\Locales::ogLocale(\App\Support\Locales::current()) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $shareTitle }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta name="twitter:card" content="{{ $seoDefaults['twitter_card_type'] ?? ($shareImageUrl ? 'summary_large_image' : 'summary') }}">
<meta name="twitter:title" content="{{ $shareTitle }}">
<meta name="twitter:description" content="{{ $shareDescription }}">

@if($shareImageUrl)
    <meta property="og:image" content="{{ $shareImageUrl }}">
    <meta name="twitter:image" content="{{ $shareImageUrl }}">
    @if($shareImageAlt)
        <meta property="og:image:alt" content="{{ $shareImageAlt }}">
    @endif
@endif
