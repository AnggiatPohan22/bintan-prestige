@php
    $shareImage = isset($siteAssets) ? ($siteAssets['site.social_share.default_image'] ?? null) : null;
    $identity = $businessIdentity ?? [];
    $shareTitle = $socialShareTitle ?? $title ?? ($identity['brand_name'] ?? config('app.name'));
    $shareDescription = $socialShareDescription ?? ($identity['short_description'] ?? config('app.name'));
@endphp

<meta property="og:type" content="{{ $socialShareType ?? 'website' }}">
<meta property="og:title" content="{{ $shareTitle }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta name="twitter:card" content="{{ $shareImage?->url ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $shareTitle }}">
<meta name="twitter:description" content="{{ $shareDescription }}">

@if($shareImage?->url)
    <meta property="og:image" content="{{ $shareImage->url }}">
    <meta name="twitter:image" content="{{ $shareImage->url }}">
@endif
