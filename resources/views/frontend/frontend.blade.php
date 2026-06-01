<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $seoTitle ?? config('app.name') }}</title>

    <meta name="description" content="{{ $seoDescription ?? '' }}">
    <meta name="keywords" content="{{ $seoKeywords ?? '' }}">

    @if(!empty($canonicalUrl))
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif

    <meta property="og:title" content="{{ $seoTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ $seoDescription ?? '' }}">
    <meta property="og:image" content="{{ $seoImage ?? '' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $seoDescription ?? '' }}">
    <meta name="twitter:image" content="{{ $seoImage ?? '' }}">

    @vite([
        'resources/css/frontend.css',
        'resources/js/app.js'
    ])
</head>

<body class="frontend-body">

    @include('frontend.partials.header')

    <main class="frontend-main">
        @yield('content')
    </main>

    @include('frontend.partials.footer')

</body>
</html>
