@php
    $alerts = [
        'success' => [
            'bg' => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'text' => 'text-emerald-700',
            'title' => 'Success',
            'iconBg' => 'bg-emerald-100',
            'iconText' => 'text-emerald-600',
        ],

        'error' => [
            'bg' => 'bg-red-50',
            'border' => 'border-red-200',
            'text' => 'text-red-700',
            'title' => 'Error',
            'iconBg' => 'bg-red-100',
            'iconText' => 'text-red-600',
        ],

        'warning' => [
            'bg' => 'bg-yellow-50',
            'border' => 'border-yellow-200',
            'text' => 'text-yellow-700',
            'title' => 'Warning',
            'iconBg' => 'bg-yellow-100',
            'iconText' => 'text-yellow-600',
        ],

        'info' => [
            'bg' => 'bg-blue-50',
            'border' => 'border-blue-200',
            'text' => 'text-blue-700',
            'title' => 'Information',
            'iconBg' => 'bg-blue-100',
            'iconText' => 'text-blue-600',
        ],
    ];
@endphp

<div class="space-y-4 mb-6">

    @foreach(['success','error','warning','info'] as $type)

        @if(session($type))

            @php
                $config = $alerts[$type];
                $id = 'alert-' . uniqid();
            @endphp

            <div
                id="{{ $id }}"
                class="rounded-2xl border
                {{ $config['bg'] }}
                {{ $config['border'] }}
                px-5 py-4 shadow-lg transition-all duration-500"
            >

                <div class="flex items-start gap-4">

                    {{-- icon --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                        {{ $config['iconBg'] }}">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 {{ $config['iconText'] }}"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <div class="flex-1">

                        <h4 class="font-semibold {{ $config['text'] }}">
                            {{ $config['title'] }}
                        </h4>

                        <p class="text-sm {{ $config['text'] }}">
                            {{ session($type) }}
                        </p>

                    </div>

                    {{-- close --}}
                    <button
                        onclick="closeAlert('{{ $id }}')"
                        class="{{ $config['text'] }}
                        hover:opacity-70 transition"
                    >
                        ✕
                    </button>

                </div>

            </div>

        @endif

    @endforeach

</div>

<script>
    function closeAlert(id) {

        const el = document.getElementById(id);

        if (!el) return;

        el.classList.add(
            'opacity-0',
            '-translate-y-2'
        );

        setTimeout(() => {
            el.remove();
        }, 500);
    }

    setTimeout(() => {

        document.querySelectorAll('[id^="alert-"]')
            .forEach(alert => {

                alert.classList.add(
                    'opacity-0',
                    '-translate-y-2'
                );

                setTimeout(() => {
                    alert.remove();
                }, 500);

            });

    }, 3000);
</script>