@php
    $alerts = [
        'success' => [
            'type'  => 'success',
            'title' => 'Success',
            'icon'  => 'fa-circle-check',
        ],
        'error' => [
            'type'  => 'danger',
            'title' => 'Error',
            'icon'  => 'fa-circle-xmark',
        ],
        'warning' => [
            'type'  => 'warning',
            'title' => 'Warning',
            'icon'  => 'fa-triangle-exclamation',
        ],
        'info' => [
            'type'  => 'info',
            'title' => 'Information',
            'icon'  => 'fa-circle-info',
        ],
    ];
@endphp

<div class="fixed bottom-6 right-6 z-50 flex flex-col gap-3">

    @foreach(['success','error','warning','info'] as $type)

        @if(session($type))

            @php
                $config = $alerts[$type];
                $id = 'alert-' . uniqid();
            @endphp

            <div
                id="{{ $id }}"
                class="admin-toast admin-toast--{{ $config['type'] }} transition-all duration-500"
            >

                <div class="admin-toast__icon">
                    <i class="fa-solid {{ $config['icon'] }}"></i>
                </div>

                <div class="admin-toast__content">
                    <div class="admin-toast__title">{{ $config['title'] }}</div>
                    <div class="admin-toast__message">{{ session($type) }}</div>
                </div>

                <button
                    onclick="closeAlert('{{ $id }}')"
                    class="admin-btn-icon h-6 w-6 shrink-0 text-xs"
                    aria-label="Dismiss"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

        @endif

    @endforeach

</div>

<script>
    function closeAlert(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('opacity-0', 'translate-x-4');
        setTimeout(() => el.remove(), 500);
    }

    setTimeout(() => {
        document.querySelectorAll('[id^="alert-"]').forEach(alert => {
            alert.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
</script>
