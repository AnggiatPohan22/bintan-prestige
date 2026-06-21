@php
    $seoDefaults = $seoDefaultSettings ?? [];
    $baseTitle = $seoTitle ?? $title ?? $seoDefaults['meta_title'] ?? ($businessIdentity['brand_name'] ?? config('app.name'));
    $documentTitle = \App\Support\SeoDefaultSettings::titleWithSuffix($baseTitle, $seoDefaults);
    $themeService = app(\App\Services\ThemeService::class);
@endphp

<!DOCTYPE html>
<html lang="{{ $seoDefaults['language'] ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    {{-- Only injected for admin previews (full-tab or srcdoc iframe).
         Ensures root-relative asset paths resolve correctly inside an srcdoc document
         where the base URL would otherwise be about:srcdoc. --}}
    @if($preview ?? false)
    <base href="{{ url('/') }}/">
    @endif

    <title>
        {{ $documentTitle }}
    </title>

    @include('partials.site-favicon')
    @include('partials.site-social-share-meta')
    @include('partials.site-structured-data')
    @include('partials.tracking-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">

    @php($activeGoogleFont = $themeService->getGoogleFont())
    @if($activeGoogleFont)
        <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $activeGoogleFont) }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>body { font-family: '{{ $activeGoogleFont }}', sans-serif; }</style>
    @endif

    @vite([
        'resources/css/frontend.css',
        'resources/js/app.js'
    ])

    @include('partials.site-brand-colors')

    @php($themeTokens = $themeService->resolvedTokens())
    @if($themeTokens)
        <style>
            :root {
                @foreach($themeTokens as $cssVar => $value)
                    {{ $cssVar }}: {{ $value }};
                @endforeach
            }
        </style>
    @endif
</head>

<body class="frontend-body">

    @include('partials.tracking-body-start')

    @include(app(\App\Services\ThemeService::class)->resolvePartial('header'))

    <main class="frontend-main">
        @yield('content')
    </main>

    @include(app(\App\Services\ThemeService::class)->resolvePartial('footer'))

    @include('partials.tracking-body-end')

</body>
</html>
