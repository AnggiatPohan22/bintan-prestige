<!DOCTYPE html>
<html lang="en"
    @if(($adminUiMode ?? 'dark') === 'light') data-admin-mode="light" @endif
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
        html, body { background: {{ ($adminUiMode ?? 'dark') === 'light' ? '#F8FAFC' : ($adminAppearance->bg_base ?? '#020617') }}; }
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
/* ─── Confirm Modal ─────────────────────────────────────────────── */
let _confirmCallback = null;

function adminConfirm(target, message, options = {}) {
    const title    = options.title    || 'Konfirmasi Hapus';
    const btnLabel = options.btnLabel || 'Hapus';
    const icon     = options.icon     || 'fa-trash';
    const danger   = options.danger   !== false;

    document.getElementById('confirmModalTitle').innerText = title;
    document.getElementById('confirmText').innerText       = message;
    document.getElementById('confirmYesLabel').innerText   = btnLabel;

    const iconEl = document.getElementById('confirmModalIcon');
    iconEl.className = `flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${danger ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'}`;
    iconEl.querySelector('i').className = `fa-solid ${icon} text-sm`;

    const yesBtn = document.getElementById('confirmYesBtn');
    yesBtn.className = danger ? 'admin-btn-danger' : 'admin-btn-primary';

    if (typeof target === 'function') {
        _confirmCallback = target;
    } else if (target instanceof HTMLFormElement) {
        _confirmCallback = () => target.submit();
    } else if (typeof target === 'string') {
        // Legacy: URL-based (PATCH fetch)
        _confirmCallback = () => {
            fetch(target, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            }).then(() => location.reload());
        };
    }

    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmYesBtn').focus();
}

// Backwards-compat alias used by some older patterns
function openConfirmModal(url, text) {
    adminConfirm(url, text, { title: 'Confirm Action', btnLabel: 'Yes, Confirm' });
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    _confirmCallback = null;
}

document.getElementById('confirmYesBtn').addEventListener('click', function () {
    closeConfirmModal();
    if (_confirmCallback) _confirmCallback();
});

// Close on backdrop click
document.getElementById('confirmModal').addEventListener('click', function (e) {
    if (e.target === this) closeConfirmModal();
});

// Close on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !document.getElementById('confirmModal').classList.contains('hidden')) {
        closeConfirmModal();
    }
});

/* ─── data-confirm delegation ───────────────────────────────────── *
 * Add data-confirm="message" to any button/submit → auto-intercept.
 * Add data-confirm-submit="message" to any form → auto-intercept submit.
 * Supports optional data-confirm-title, data-confirm-btn overrides.
 * ─────────────────────────────────────────────────────────────────*/
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const form = btn.form || btn.closest('form');
    adminConfirm(
        form || (() => {}),
        btn.getAttribute('data-confirm'),
        {
            title:    btn.getAttribute('data-confirm-title')  || undefined,
            btnLabel: btn.getAttribute('data-confirm-btn')    || undefined,
        }
    );
}, true); // capture phase so it fires before Alpine

document.addEventListener('submit', function (e) {
    const form = e.target;
    const msg  = form.getAttribute('data-confirm-submit');
    if (!msg) return;
    e.preventDefault();

    adminConfirm(
        () => {
            form.removeAttribute('data-confirm-submit');
            form.submit();
            // Restore after submit for multi-use forms
            setTimeout(() => form.setAttribute('data-confirm-submit', msg), 100);
        },
        msg,
        {
            title:    form.getAttribute('data-confirm-title') || undefined,
            btnLabel: form.getAttribute('data-confirm-btn')   || undefined,
        }
    );
});
</script>

</body>
</html>
