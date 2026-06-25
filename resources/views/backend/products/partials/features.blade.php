<div id="features-section" class="admin-card">
    <div class="admin-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-admin-primary">
                    Product Features
                </h3>

                <p class="mt-1 text-sm leading-6 text-admin-secondary">
                    Manage included, excluded, optional, addon, and important product details.
                </p>
            </div>

            @if(isset($product) && $product->exists)
                <span class="admin-badge-info w-fit">
                    {{ $product->features->count() }} item(s)
                </span>
            @endif
        </div>
    </div>

    <div class="admin-card-body">
        @if(!isset($product) || !$product->exists)

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-semibold text-amber-800">
                    Save product first
                </h4>

                <p class="mt-2 text-sm text-amber-700">
                    Product features can be added after the product is created.
                </p>
            </div>

        @else

            @php
                $featureTypes = [
                    'included' => [
                        'label' => 'Included',
                        'hint' => 'Things guests will get.',
                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    ],
                    'excluded' => [
                        'label' => 'Excluded',
                        'hint' => 'Costs or items not covered.',
                        'class' => 'bg-red-50 text-red-700 border-red-200',
                    ],
                    'optional' => [
                        'label' => 'Optional',
                        'hint' => 'Available but not required.',
                        'class' => 'bg-sky-50 text-sky-700 border-sky-200',
                    ],
                    'addon' => [
                        'label' => 'Addon',
                        'hint' => 'Extra purchasable upgrades.',
                        'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    ],
                    'important' => [
                        'label' => 'Important',
                        'hint' => 'Rules or must-know details.',
                        'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                    ],
                ];

                $groupedFeatures = $product->features->groupBy('label');
                $includedFeatures = $groupedFeatures->get('included', collect());
                $excludedFeatures = $groupedFeatures->get('excluded', collect());
            @endphp

            @if($includedFeatures->isEmpty() || $excludedFeatures->isEmpty())
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Add Included and Excluded first so the frontend package information is clear.
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.products.features.store', $product) }}"
                class="admin-form-card space-y-5"
                data-preserve-scroll
            >
                @csrf

                <div>
                    <h4 class="text-base font-extrabold text-admin-primary">
                        Add Feature
                    </h4>
                    <p class="mt-1 text-sm text-admin-secondary">
                        Start with Included and Excluded before adding optional, addon, or important items.
                    </p>
                </div>

                <div>
                    <label class="admin-form-label">Type</label>

                    <select name="label" class="admin-select" required>
                        @foreach($featureTypes as $value => $meta)
                            <option value="{{ $value }}" @selected(old('label') === $value)>
                                {{ $meta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="admin-form-label">Value</label>

                    <input
                        type="text"
                        name="value"
                        class="admin-input"
                        placeholder="Example: Hotel Pickup"
                        required
                    >
                </div>

                <div>
                    <label class="admin-form-label">Sort Order</label>

                    <input
                        type="number"
                        name="sort_order"
                        class="admin-input"
                        value="0"
                    >
                </div>

                <button type="submit" class="admin-btn-primary w-full">
                    Add Feature
                </button>
            </form>

            <div class="my-6 border-t border-admin"></div>

            <div class="space-y-5">
                @foreach($featureTypes as $type => $meta)

                    @php
                        $items = $groupedFeatures->get($type, collect());
                        $isCoreMissing =
                            in_array($type, ['included', 'excluded'], true)
                            && $items->isEmpty();
                    @endphp

                    <div class="rounded-2xl border border-admin bg-admin-card shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-admin p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>

                                    <span class="text-xs font-semibold text-admin-secondary">
                                        {{ $items->count() }} item(s)
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-admin-secondary">
                                    {{ $meta['hint'] }}
                                </p>
                            </div>

                            @if($isCoreMissing)
                                <span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                    Required first
                                </span>
                            @endif
                        </div>

                        <div class="space-y-3 p-4">
                            @forelse($items as $feature)
                                <div class="admin-list-item">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-admin-primary">
                                                {{ $feature->value }}
                                            </p>

                                            <p class="mt-1 text-xs font-semibold text-admin-secondary">
                                                Sort: {{ $feature->sort_order }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <button
                                                type="button"
                                                class="admin-btn-secondary w-full sm:w-auto"
                                                data-modal-open="edit-feature-{{ $feature->id }}"
                                            >
                                                Edit
                                            </button>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.products.features.destroy', $feature) }}"
                                                data-preserve-scroll
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="admin-btn-danger w-full sm:w-auto"
                                                    onclick="return confirm('Delete feature?')"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div
                                        id="edit-feature-{{ $feature->id }}"
                                        class="fixed inset-0 z-50 hidden bg-slate-900/50 p-4"
                                        data-modal
                                    >
                                        <div class="admin-modal-content mx-auto mt-16 max-w-lg">
                                            <div class="mb-5 flex items-center justify-between gap-4">
                                                <h3 class="text-lg font-bold text-admin-primary">
                                                    Edit Feature
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
                                                action="{{ route('admin.products.features.update', $feature) }}"
                                                class="space-y-4"
                                                data-preserve-scroll
                                            >
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label class="admin-form-label">Type</label>
                                                    <select name="label" class="admin-select" required>
                                                        @foreach($featureTypes as $value => $option)
                                                            <option value="{{ $value }}" @selected($feature->label === $value)>
                                                                {{ $option['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="admin-form-label">Value</label>
                                                    <input
                                                        type="text"
                                                        name="value"
                                                        value="{{ $feature->value }}"
                                                        class="admin-input"
                                                        required
                                                    >
                                                </div>

                                                <div>
                                                    <label class="admin-form-label">Sort Order</label>
                                                    <input
                                                        type="number"
                                                        name="sort_order"
                                                        value="{{ $feature->sort_order }}"
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
                            @empty
                                <div class="admin-empty-state">
                                    No {{ strtolower($meta['label']) }} items yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                @endforeach
            </div>

        @endif
    </div>
</div>
