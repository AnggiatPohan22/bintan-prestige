<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

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
            class="space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div class="card-header mb-5">
                <label class="form-heading" >
                    Product Highlight
                    <span class="text-red-500">*</span>
                </label>
            </div>
            <div>
                <label class="form-label">Title</label>
                <input
                    type="text"
                    name="title"
                    class="form-input"
                    placeholder="Example: Hotel Pickup"
                    required
                >
            </div>

            <div class="relative">
                <label class="form-label">Icon</label>

                <button
                    type="button"
                    id="iconPickerTrigger"
                    class="form-input flex items-center justify-between"
                >
                    <span class="flex items-center gap-3">
                        <i id="selectedIcon" class="fa-solid fa-car text-slate-600"></i>
                        <span id="selectedIconText">Choose icon</span>
                    </span>

                    <span class="text-slate-400">▼</span>
                </button>

                <input type="hidden" name="icon" id="iconInput">

                <div
                    id="iconDropdown"
                    class="hidden absolute left-0 z-50 mt-2 w-[min(92vw,360px)]
                    rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl"
                >
                    <input
                        type="text"
                        id="iconSearch"
                        class="form-input mb-4"
                        placeholder="Search icon..."
                    >

                    <div
                        id="iconGrid"
                        class="grid grid-cols-4 sm:grid-cols-5 gap-3 max-h-64 overflow-y-auto pr-1"
                    ></div>
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label">Sort Order</label>
                <input
                    type="number"
                    name="sort_order"
                    class="form-input"
                    value="0"
                >
            </div>

            <button type="submit" class="btn-primary w-full">
                Add Highlight
            </button>
        </form>

        <div class="my-6 border-t border-slate-200"></div>

        @forelse($product->highlights as $highlight)

            <div class="rounded-xl border border-slate-200 p-4 mb-3 shadow-sm">
                <div class="flex items-center justify-between gap-3">

                    <div>
                        <p class="font-semibold text-slate-700">
                            @if($highlight->icon)
                                <i class="fa-solid {{ $highlight->icon }} mr-2"></i>
                            @endif

                            {{ $highlight->title }}
                        </p>

                        <p class="text-xs text-slate-400">
                            Sort: {{ $highlight->sort_order }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn-secondary"
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
                                class="btn-secondary"
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
                        <div class="mx-auto mt-16 max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                            <div class="mb-5 flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold text-slate-800">
                                    Edit Highlight
                                </h3>

                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-100"
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
                                    <label class="form-label">Title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        value="{{ $highlight->title }}"
                                        class="form-input"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="form-label">Icon</label>
                                    <input
                                        type="text"
                                        name="icon"
                                        value="{{ $highlight->icon }}"
                                        class="form-input"
                                        placeholder="Example: fa-car"
                                    >
                                </div>

                                <div>
                                    <label class="form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $highlight->sort_order }}"
                                        class="form-input"
                                    >
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        class="btn-secondary"
                                        data-modal-close
                                    >
                                        Cancel
                                    </button>

                                    <button type="submit" class="btn-primary">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        @empty

            <div class="text-center py-8 text-slate-400">
                No highlights yet.
            </div>

        @endforelse

    @endif

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
                    'h-11 w-11 flex items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50';

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
