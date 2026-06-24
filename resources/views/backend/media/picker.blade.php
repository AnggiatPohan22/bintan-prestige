<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Media Picker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-800 p-4">
    {{-- Selection metadata is posted to the parent and routed to the requesting block field. --}}
    <div x-data="mediaPicker()">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h1 class="text-base font-bold text-slate-100">Select media</h1>
            <form method="GET" action="{{ route('admin.media.index') }}" class="flex gap-2">
                <input type="hidden" name="picker" value="1">
                <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="Search...">
                <button type="submit" class="admin-btn-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        @include('backend.media.partials.grid', ['media' => $media])
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mediaPicker', () => ({
                select(payload) {
                    window.parent.postMessage({ type: 'media-selected', media: payload }, window.location.origin);
                },
            }));
        });
    </script>
</body>
</html>
