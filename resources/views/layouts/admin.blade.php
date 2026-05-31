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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    @include('backend.partials.sidebar')

    <div class="flex-1">

        {{-- Navbar --}}
        @include('backend.partials.navbar')

        <main class="p-6">
            
            {{-- Flash Alert --}}
            <x-flash-alert />

            {{-- Confirm Modal --}}
            <x-confirm-modal />

            @yield('content')
        </main>

    </div>

</div>

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