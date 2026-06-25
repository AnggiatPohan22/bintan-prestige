<div class="admin-card">
    <div class="admin-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-admin-primary">
                    Product Itinerary
                </h3>

                <p class="mt-1 text-sm leading-6 text-admin-secondary">
                    Build the timeline guests will follow during this product.
                </p>
            </div>

            @if(isset($product) && $product->exists)
                <span class="admin-badge-info w-fit">
                    {{ $product->itineraries->count() }} item(s)
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
                    Product itinerary can be added after the product is created.
                </p>
            </div>

        @else

            <form
                method="POST"
                action="{{ route('admin.products.itineraries.store', $product) }}"
                class="admin-form-card space-y-5"
                data-preserve-scroll
            >
                @csrf

                <div>
                    <h4 class="text-base font-extrabold text-admin-primary">
                        Add Itinerary
                    </h4>
                    <p class="mt-1 text-sm text-admin-secondary">
                        Add one timeline stop or activity at a time.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="admin-form-label">Time</label>

                        <input
                            type="text"
                            name="time"
                            class="admin-input"
                            placeholder="Example: 08:00 / Morning / Flexible"
                        >
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

                    <div class="sm:col-span-2">
                        <label class="admin-form-label">Description</label>

                        <textarea
                            name="description"
                            rows="4"
                            class="admin-textarea"
                            placeholder="Example: Pickup from hotel lobby."
                        ></textarea>
                    </div>

                    <div>
                        <label class="admin-form-label">Start Time</label>

                        <input
                            type="number"
                            name="start_time"
                            class="admin-input"
                            value="0"
                            placeholder="Example: 800 for 08:00"
                        >

                        <p class="mt-2 text-xs text-admin-secondary">
                            Used for sorting. Example: 800 = 08:00, 1330 = 13:30.
                        </p>
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
                </div>

                <button type="submit" class="admin-btn-primary w-full">
                    Add Itinerary
                </button>
            </form>

            <div class="my-6 border-t border-slate-200"></div>

            <div class="space-y-4">
                @forelse($product->itineraries as $itinerary)

                    <div class="admin-list-item shadow-sm">
                        <div class="flex gap-4">

                            <div class="flex flex-col items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                    {{ $loop->iteration }}
                                </div>

                                @if(!$loop->last)
                                    <div class="mt-2 h-full w-px bg-slate-200"></div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        @if($itinerary->time)
                                            <div class="mb-2 inline-flex items-center rounded-full border border-admin bg-admin-card px-3 py-1 text-xs font-semibold text-admin-secondary">
                                                {{ $itinerary->time }}
                                            </div>
                                        @endif

                                        <h4 class="text-base font-semibold leading-snug text-admin-primary">
                                            {{ $itinerary->title }}
                                        </h4>

                                        @if($itinerary->description)
                                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-admin-secondary">
                                                {{ $itinerary->description }}
                                            </p>
                                        @endif

                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-admin-secondary">
                                            <span class="rounded-full border border-admin bg-admin-card px-3 py-1 text-admin-secondary">
                                                Start Time: {{ $itinerary->start_time ?? 0 }}
                                            </span>

                                            <span class="rounded-full border border-admin bg-admin-card px-3 py-1 text-admin-secondary">
                                                Sort: {{ $itinerary->sort_order ?? 0 }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                                        <button
                                            type="button"
                                            class="admin-btn-secondary w-full sm:w-auto"
                                            data-modal-open="edit-itinerary-{{ $itinerary->id }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.products.itineraries.destroy', $itinerary) }}"
                                            data-preserve-scroll
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="admin-btn-danger w-full sm:w-auto"
                                                onclick="return confirm('Delete itinerary?')"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>

                                    <div
                                        id="edit-itinerary-{{ $itinerary->id }}"
                                        class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 p-4"
                                        data-modal
                                    >
                                        <div class="admin-modal-content mx-auto my-10 max-w-2xl">
                                            <div class="mb-5 flex items-center justify-between gap-4">
                                                <h3 class="text-lg font-bold text-admin-primary">
                                                    Edit Itinerary
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
                                                action="{{ route('admin.products.itineraries.update', $itinerary) }}"
                                                class="space-y-4"
                                                data-preserve-scroll
                                            >
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label class="admin-form-label">Time</label>
                                                    <input
                                                        type="text"
                                                        name="time"
                                                        value="{{ $itinerary->time }}"
                                                        class="admin-input"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="admin-form-label">Title</label>
                                                    <input
                                                        type="text"
                                                        name="title"
                                                        value="{{ $itinerary->title }}"
                                                        class="admin-input"
                                                        required
                                                    >
                                                </div>

                                                <div>
                                                    <label class="admin-form-label">Description</label>
                                                    <textarea
                                                        name="description"
                                                        rows="4"
                                                        class="admin-textarea"
                                                    >{{ $itinerary->description }}</textarea>
                                                </div>

                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="admin-form-label">Start Time</label>
                                                        <input
                                                            type="number"
                                                            name="start_time"
                                                            value="{{ $itinerary->start_time }}"
                                                            class="admin-input"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="admin-form-label">Sort Order</label>
                                                        <input
                                                            type="number"
                                                            name="sort_order"
                                                            value="{{ $itinerary->sort_order }}"
                                                            class="admin-input"
                                                        >
                                                    </div>
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

                        </div>
                    </div>

                @empty

                    <div class="admin-empty-state">
                        No itinerary yet.
                    </div>

                @endforelse
            </div>

        @endif
    </div>
</div>
