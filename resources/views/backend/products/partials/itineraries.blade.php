<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

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
            class="space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div class="card-header mb-5">
                <label class="form-heading" >
                    Product Itinerary
                    <span class="text-red-500">*</span>
                </label>
            </div>

            <div>
                <label class="form-label">Time</label>

                <input
                    type="text"
                    name="time"
                    class="form-input"
                    placeholder="Example: 08:00 / Morning / Flexible"
                >
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

            <div>
                <label class="form-label">Description</label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-textarea"
                    placeholder="Example: Pickup from hotel lobby."
                ></textarea>
            </div>

            <div>
                <label class="form-label">Start Time</label>

                <input
                    type="number"
                    name="start_time"
                    class="form-input"
                    value="0"
                    placeholder="Example: 800 for 08:00"
                >

                <p class="mt-1 text-xs text-slate-400">
                    Used for sorting. Example: 800 = 08:00, 1330 = 13:30.
                </p>
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
                Add Itinerary
            </button>
        </form>

        <div class="my-6 border-t border-slate-200"></div>

        @forelse($product->itineraries as $itinerary)

            <div class="relative rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">

                <div class="flex gap-4">

                    {{-- Timeline Marker --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm">
                            {{ $loop->iteration }}
                        </div>

                        @if(!$loop->last)
                            <div class="mt-2 h-full w-px bg-slate-200"></div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">

                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">

                            <div>
                                @if($itinerary->time)
                                    <div class="mb-1 inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                        {{ $itinerary->time }}
                                    </div>
                                @endif

                                <h4 class="text-base font-semibold text-slate-800 leading-snug">
                                    {{ $itinerary->title }}
                                </h4>

                                @if($itinerary->description)
                                    <p class="mt-2 text-sm text-slate-500 leading-relaxed whitespace-pre-line">
                                        {{ $itinerary->description }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                                <button
                                    type="button"
                                    class="btn-secondary"
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
                                        class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition"
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
                                <div class="mx-auto my-10 max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                                    <div class="mb-5 flex items-center justify-between gap-4">
                                        <h3 class="text-lg font-bold text-slate-800">
                                            Edit Itinerary
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
                                        action="{{ route('admin.products.itineraries.update', $itinerary) }}"
                                        class="space-y-4"
                                        data-preserve-scroll
                                    >
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="form-label">Time</label>
                                            <input
                                                type="text"
                                                name="time"
                                                value="{{ $itinerary->time }}"
                                                class="form-input"
                                            >
                                        </div>

                                        <div>
                                            <label class="form-label">Title</label>
                                            <input
                                                type="text"
                                                name="title"
                                                value="{{ $itinerary->title }}"
                                                class="form-input"
                                                required
                                            >
                                        </div>

                                        <div>
                                            <label class="form-label">Description</label>
                                            <textarea
                                                name="description"
                                                rows="4"
                                                class="form-textarea"
                                            >{{ $itinerary->description }}</textarea>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label class="form-label">Start Time</label>
                                                <input
                                                    type="number"
                                                    name="start_time"
                                                    value="{{ $itinerary->start_time }}"
                                                    class="form-input"
                                                >
                                            </div>

                                            <div>
                                                <label class="form-label">Sort Order</label>
                                                <input
                                                    type="number"
                                                    name="sort_order"
                                                    value="{{ $itinerary->sort_order }}"
                                                    class="form-input"
                                                >
                                            </div>
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

                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-400">
                            <span class="rounded-full bg-slate-50 px-3 py-1 border border-slate-200">
                                Start Time: {{ $itinerary->start_time ?? 0 }}
                            </span>

                            <span class="rounded-full bg-slate-50 px-3 py-1 border border-slate-200">
                                Sort: {{ $itinerary->sort_order ?? 0 }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-8 text-center">
                <p class="text-sm text-slate-400">
                    No itinerary yet.
                </p>
            </div>

        @endforelse

    @endif

</div>
