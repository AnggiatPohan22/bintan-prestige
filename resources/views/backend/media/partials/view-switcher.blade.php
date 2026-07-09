{{--
    Media Library view-mode switcher. The host Alpine component must expose
    `viewMode` (string) and `setView(mode)`. Persisted to localStorage so the
    choice is shared between the library page and the picker popup.
--}}
<div class="inline-flex items-center gap-1 rounded-lg border border-admin bg-admin-card p-1" role="group" aria-label="Media view mode">
    @foreach([
        ['small',  'fa-grip',              'Small'],
        ['large',  'fa-table-cells-large', 'Large'],
        ['xlarge', 'fa-square',            'Extra large'],
        ['list',   'fa-list',              'List'],
        ['detail', 'fa-rectangle-list',    'Detail'],
    ] as [$mode, $icon, $label])
        <button type="button" title="{{ $label }} view" aria-label="{{ $label }} view"
                x-on:click="setView('{{ $mode }}')"
                :aria-pressed="viewMode === '{{ $mode }}' ? 'true' : 'false'"
                class="grid h-7 w-7 place-items-center rounded-md text-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                :class="viewMode === '{{ $mode }}' ? 'bg-indigo-600 text-white' : 'text-admin-secondary hover:opacity-75'">
            <i class="fa-solid {{ $icon }}"></i>
        </button>
    @endforeach
</div>
