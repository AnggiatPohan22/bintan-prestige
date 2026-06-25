<div class="admin-card">
    <div class="admin-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-admin-primary">
                    Product Highlights
                </h3>

                <p class="mt-1 text-sm leading-6 text-admin-secondary">
                    Add short selling points shown on the product detail page.
                </p>
            </div>

            @if(isset($product) && $product->exists)
                <span class="admin-badge-info w-fit">
                    {{ $product->highlights->count() }} item(s)
                </span>
            @endif
        </div>
    </div>

    <div class="admin-card-body">

    @if(!isset($product) || !$product->exists)

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h4 class="font-semibold text-amber-800">
                Save product first
            </h4>

            <p class="mt-2 text-sm text-amber-700">
                Product highlights can be added after the product is created.
            </p>
        </div>

    @else

        <form
            method="POST"
            action="{{ route('admin.products.highlights.store', $product) }}"
            class="admin-form-card space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div>
                <h4 class="text-base font-extrabold text-admin-primary">
                    Add Highlight
                </h4>
                <p class="mt-1 text-sm text-admin-secondary">
                    Keep each highlight concise and action-oriented.
                </p>
            </div>

            <div>
                <label class="admin-form-label">Title</label>
                <input
                    type="text"
                    name="title"
                    class="admin-input"
                    placeholder="Example: Hotel Pickup"
                    required
                >
            </div>

            <div class="relative">
                <label class="admin-form-label">Icon</label>

                <button
                    type="button"
                    id="iconPickerTrigger"
                    class="admin-input flex items-center justify-between"
                >
                    <span class="flex items-center gap-3">
                        <i id="selectedIcon" class="fa-solid fa-car text-admin-secondary"></i>
                        <span id="selectedIconText">Choose icon</span>
                    </span>

                    <span class="text-admin-secondary">▼</span>
                </button>

                <input type="hidden" name="icon" id="iconInput">

                <div
                    id="iconDropdown"
                    class="hidden absolute left-0 z-50 mt-2 w-[min(92vw,360px)]
                    rounded-2xl border border-admin bg-admin-card p-4 shadow-2xl"
                >
                    <input
                        type="text"
                        id="iconSearch"
                        class="admin-input mb-4"
                        placeholder="Search icon..."
                    >

                    <div
                        id="iconGrid"
                        class="grid grid-cols-4 sm:grid-cols-5 gap-3 max-h-64 overflow-y-auto pr-1"
                    ></div>
                </div>
            </div>

            <div class="mb-5">
                <label class="admin-form-label">Sort Order</label>
                <input
                    type="number"
                    name="sort_order"
                    class="admin-input"
                    value="0"
                >
            </div>

            <button type="submit" class="admin-btn-primary w-full">
                Add Highlight
            </button>
        </form>

        <div class="my-6 border-t border-admin"></div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($product->highlights as $highlight)

                <div class="admin-list-item shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div class="flex min-w-0 gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                @if($highlight->icon)
                                    <i class="fa-solid {{ $highlight->icon }}"></i>
                                @else
                                    <span class="text-sm font-bold">#</span>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-admin-primary">
                                    {{ $highlight->title }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-admin-secondary">
                                    Sort: {{ $highlight->sort_order }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                class="admin-btn-secondary w-full sm:w-auto"
                                data-modal-open="edit-highlight-{{ $highlight->id }}"
                            >
                                Edit
                            </button>

                            <form
                                method="POST"
                                action="{{ route('admin.products.highlights.destroy', $highlight) }}"
                                data-preserve-scroll
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="admin-btn-danger w-full sm:w-auto"
                                    onclick="return confirm('Delete highlight?')"
                                >
                                    Delete
                                </button>
                            </form>
                        </div>

                        <div
                            id="edit-highlight-{{ $highlight->id }}"
                            class="fixed inset-0 z-50 hidden bg-slate-900/50 p-4"
                            data-modal
                        >
                        <div class="admin-modal-content mx-auto mt-16 max-w-lg">
                            <div class="mb-5 flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold text-admin-primary">
                                    Edit Highlight
                                </h3>

                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-admin-secondary hover:opacity-75"
                                    data-modal-close
                                >
                                    X
                                </button>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.products.highlights.update', $highlight) }}"
                                class="space-y-4"
                                data-preserve-scroll
                            >
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="admin-form-label">Title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        value="{{ $highlight->title }}"
                                        class="admin-input"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="admin-form-label">Icon</label>
                                    <input
                                        type="text"
                                        name="icon"
                                        value="{{ $highlight->icon }}"
                                        class="admin-input"
                                        placeholder="Example: fa-car"
                                    >
                                </div>

                                <div>
                                    <label class="admin-form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $highlight->sort_order }}"
                                        class="admin-input"
                                    >
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <button
                                        type="button"
                                        class="admin-btn-secondary"
                                        data-modal-close
                                    >
                                        Cancel
                                    </button>

                                    <button type="submit" class="admin-btn-primary">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                </div>

            @empty

            <div class="admin-empty-state">
                No highlights yet.
            </div>

            @endforelse
        </div>

    @endif

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const dropdown = document.getElementById('iconDropdown');
    const trigger = document.getElementById('iconPickerTrigger');
    const grid = document.getElementById('iconGrid');
    const search = document.getElementById('iconSearch');
    const selectedIcon = document.getElementById('selectedIcon');
    const selectedText = document.getElementById('selectedIconText');
    const hiddenInput = document.getElementById('iconInput');

    if (!dropdown || !trigger || !grid || !search) {
        return;
    }

    const icons = [
        'fa-car',
        'fa-bus',
        'fa-plane',
        'fa-hotel',
        'fa-location-dot',
        'fa-utensils',
        'fa-wifi',
        'fa-camera',
        'fa-ship',
        'fa-person-walking',
        'fa-suitcase',
        'fa-map',
        'fa-water',
        'fa-mountain',
        'fa-tree',
        'fa-star',
        'fa-clock',
        'fa-user-group',
        'fa-ticket',
        'fa-phone'
    ];

    function renderIcons(filter = '') {
        grid.innerHTML = '';

        icons
            .filter(icon => icon.includes(filter))
            .forEach(icon => {
                const item = document.createElement('button');

                item.type = 'button';
                item.className =
                    'h-11 w-11 flex items-center justify-center rounded-xl border border-admin bg-admin-card hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-indigo-500';

                item.innerHTML =
                    `<i class="fa-solid ${icon} text-lg"></i>`;

                item.onclick = () => {
                    selectedIcon.className = `fa-solid ${icon}`;
                    selectedText.innerText = icon;
                    hiddenInput.value = icon;
                    dropdown.classList.add('hidden');
                };

                grid.appendChild(item);
            });
    }

    trigger.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
    });

    search.addEventListener('input', e => {
        renderIcons(e.target.value);
    });

    renderIcons();
});
</script>
