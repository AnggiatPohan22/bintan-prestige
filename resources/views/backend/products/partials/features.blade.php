<div id="features-section" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

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

        <div class="card-header mb-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="form-heading">
                    Product Feature
                    <span class="text-red-500">*</span>
                </label>

                <span class="text-xs font-semibold text-slate-400">
                    {{ $product->features->count() }} item(s)
                </span>
            </div>
        </div>

        @if($includedFeatures->isEmpty() || $excludedFeatures->isEmpty())
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Add Included and Excluded first so the frontend package information is clear.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.products.features.store', $product) }}"
            class="space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div>
                <label class="form-label">Type</label>

                <select name="label" class="form-select" required>
                    @foreach($featureTypes as $value => $meta)
                        <option value="{{ $value }}" @selected(old('label') === $value)>
                            {{ $meta['label'] }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-xs text-slate-400">
                    Start with Included and Excluded before adding optional, addon, or important items.
                </p>
            </div>

            <div>
                <label class="form-label">Value</label>

                <input
                    type="text"
                    name="value"
                    class="form-input"
                    placeholder="Example: Hotel Pickup"
                    required
                >
            </div>

            <div>
                <label class="form-label">Sort Order</label>

                <input
                    type="number"
                    name="sort_order"
                    class="form-input"
                    value="0"
                >
            </div>

            <button type="submit" class="btn-primary w-full">
                Add Feature
            </button>
        </form>

        <div class="my-6 border-t border-slate-200"></div>

        <div class="space-y-5">
            @foreach($featureTypes as $type => $meta)

                @php
                    $items = $groupedFeatures->get($type, collect());
                    $isCoreMissing =
                        in_array($type, ['included', 'excluded'], true)
                        && $items->isEmpty();
                @endphp

                <div class="rounded-xl border border-slate-200">
                    <div class="flex flex-col gap-2 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    {{ $items->count() }} item(s)
                                </span>
                            </div>

                            <p class="mt-2 text-xs text-slate-400">
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
                            <div class="flex flex-col gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-700">
                                        {{ $feature->value }}
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        Sort: {{ $feature->sort_order }}
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <button
                                        type="button"
                                        class="btn-secondary w-full sm:w-auto"
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
                                            class="btn-secondary w-full sm:w-auto"
                                            onclick="return confirm('Delete feature?')"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                <div
                                    id="edit-feature-{{ $feature->id }}"
                                    class="fixed inset-0 z-50 hidden bg-slate-900/50 p-4"
                                    data-modal
                                >
                                    <div class="mx-auto mt-16 max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                                        <div class="mb-5 flex items-center justify-between gap-4">
                                            <h3 class="text-lg font-bold text-slate-800">
                                                Edit Feature
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
                                            action="{{ route('admin.products.features.update', $feature) }}"
                                            class="space-y-4"
                                            data-preserve-scroll
                                        >
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <label class="form-label">Type</label>
                                                <select name="label" class="form-select" required>
                                                    @foreach($featureTypes as $value => $option)
                                                        <option value="{{ $value }}" @selected($feature->label === $value)>
                                                            {{ $option['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="form-label">Value</label>
                                                <input
                                                    type="text"
                                                    name="value"
                                                    value="{{ $feature->value }}"
                                                    class="form-input"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="form-label">Sort Order</label>
                                                <input
                                                    type="number"
                                                    name="sort_order"
                                                    value="{{ $feature->sort_order }}"
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
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-400">
                                No {{ strtolower($meta['label']) }} items yet.
                            </div>
                        @endforelse
                    </div>
                </div>

            @endforeach
        </div>

    @endif

</div>
