<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Preview — {{ $entry->title ?? 'Entry' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Forum&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 antialiased">

    {{-- Builder live-preview canvas: renders only the entry's block body. Full
         frontend chrome (header/footer/template) arrives with entry routing at B11–B12. --}}
    @forelse($blocks as $block)
        @includeIf('frontend.blocks.' . str_replace('_', '-', $block->block_type), [
            'block' => $block,
            'data'  => $block->data ?? [],
        ])
    @empty
        <div class="flex min-h-[60vh] items-center justify-center px-6 py-24 text-center">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">{{ $entry->title ?? 'Untitled' }}</h1>
                <p class="mt-4 text-slate-500">This entry has no body content yet. Drag a block from the left to begin.</p>
            </div>
        </div>
    @endforelse

</body>
</html>
