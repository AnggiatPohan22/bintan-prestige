@php
    $seoDefaults = $seoDefaultSettings ?? [];
    $baseTitle = $seoTitle ?? $title ?? $seoDefaults['meta_title'] ?? ($businessIdentity['brand_name'] ?? config('app.name'));
    $documentTitle = \App\Support\SeoDefaultSettings::titleWithSuffix($baseTitle, $seoDefaults);
@endphp

<!DOCTYPE html>
<html lang="{{ $seoDefaults['language'] ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        {{ $documentTitle }}
    </title>

    @include('partials.site-favicon')
    @include('partials.site-social-share-meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">

    @vite([
        'resources/css/frontend.css',
        'resources/js/app.js'
    ])

    @include('partials.site-brand-colors')
</head>

<body class="frontend-body">

    @include('frontend.partials.header')

    <main class="frontend-main">
        @yield('content')
    </main>

    @include('frontend.partials.footer')

</body>
</html>
