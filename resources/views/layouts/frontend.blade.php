<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        {{ $title ?? config('app.name') }}
    </title>

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
