<!DOCTYPE html>
<html lang="en">
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
