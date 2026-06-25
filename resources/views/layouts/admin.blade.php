<!DOCTYPE html>
<html lang="en"
    @isset($adminAppearance)
        @if($adminAppearance->mode !== 'dark') data-admin-mode="light" @endif
        @if($adminAppearance->sidebar_style === 'light') data-admin-sidebar="light" @endif
    @endisset
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token"
      content="{{ csrf_token() }}">
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />
    <title>{{ config('app.name') }}</title>

    @include('partials.site-favicon')

    {{-- Inline styles run immediately on HTML parse, before @vite CSS loads.
         Prevents: (1) white flash while external CSS is fetching,
                   (2) Alpine x-cloak elements flashing visible,
                   (3) .hidden fixed overlays intercepting first click.
         bg_base from DB — dynamic so theme changes reflect without FOUC. --}}
    <style>
        html, body { background: {{ $adminAppearance->bg_base ?? '#020617' }}; }
        [x-cloak]  { display: none !important; }
        .hidden    { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Admin Appearance: CSS Custom Properties dari DB.
         Override :root vars sesuai tema aktif (preset / custom colors).
         Dikelola via Settings > Customize Dashboard (super admin only).
         {!! !!} aman: nilai hex sudah divalidasi via FormRequest sebelum masuk DB. --}}
    @isset($adminAppearanceCss)
    <style id="admin-appearance-vars">
        {!! $adminAppearanceCss !!}
    </style>
    @endisset

    @include('partials.site-brand-colors')
</head>
<body class="admin-body">

<div class="admin-shell">

    {{-- Sidebar --}}
    @include('backend.partials.sidebar')

    <div class="admin-shell__workspace">

        {{-- Navbar --}}
        @include('backend.partials.navbar')

        <main class="admin-shell__main">
            <div class="admin-shell__content">

            {{-- Flash Alert --}}
            <x-flash-alert />

            {{-- Confirm Modal --}}
            <x-confirm-modal />

            @yield('content')

            </div>
        </main>

    </div>

    {{-- Command Palette --}}
    <x-admin.command-palette />

</div>

@stack('scripts')

<script>
let confirmUrl = null;

function openConfirmModal(url, text) {

    confirmUrl = url;

    document
        .getElementById('confirmText')
        .innerText = text;

    document
        .getElementById('confirmModal')
        .classList.remove('hidden');
}

function closeConfirmModal() {

    document
        .getElementById('confirmModal')
        .classList.add('hidden');
}

document
.getElementById('confirmYesBtn')
.addEventListener('click', function () {

    fetch(confirmUrl, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN':
                document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            'Accept': 'application/json'
        }
    })
    .then(() => location.reload());
});
</script>

</body>
</html>
