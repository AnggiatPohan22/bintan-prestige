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
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="bg-slate-50 text-slate-800">

    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <a href="{{ route('products.index') }}"
               class="text-xl font-bold text-slate-900">
                Bintan Prestige
            </a>

            <nav class="flex gap-6 text-sm font-medium">
                <a href="{{ route('products.index') }}"
                   class="hover:text-emerald-600">
                    Products
                </a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

</body>
</html>