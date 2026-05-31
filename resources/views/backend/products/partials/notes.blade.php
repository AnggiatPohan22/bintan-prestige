<div id="notes-section" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

    @if(!isset($product) || !$product->exists)

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h4 class="font-semibold text-amber-800">
                Save product first
            </h4>

            <p class="mt-2 text-sm text-amber-700">
                Product notes can be added after the product is created.
            </p>
        </div>

    @else

        <form
            method="POST"
            action="{{ route('admin.products.notes.store', $product) }}"
            class="space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div class="card-header mb-5">
                <label class="form-heading" >
                    Product Notes
                    <span class="text-red-500">*</span>
                </label>
            </div>

            <div>
                <label class="form-label">Title</label>

                <input
                    type="text"
                    name="title"
                    class="form-input"
                    placeholder="Example: Important Information"
                >
            </div>

            <div>
                <label class="form-label">Description</label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-textarea"
                    placeholder="Example: Bring sunscreen and comfortable clothes."
                    required
                ></textarea>
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
                Add Note
            </button>
        </form>

        <div class="my-6 border-t border-slate-200"></div>

        @forelse($product->notes as $note)

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex gap-3 flex-1 min-w-0">

                            {{-- Icon --}}
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                !
                            </div>

                            {{-- Content --}}
                            <div class="min-w-0 flex-1">

                                @if($note->title)
                                    <div class="flex items-center gap-2 mb-1">

                                        <h4 class="font-semibold text-slate-800 leading-snug">
                                            {{ $note->title }}
                                        </h4>

                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500">
                                            #{{ $note->sort_order }}
                                        </span>

                                    </div>
                                @endif

                                <p class="text-sm leading-relaxed text-slate-500 whitespace-pre-line">
                                    {{ $note->description }}
                                </p>

                            </div>

                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                            <button
                                type="button"
                                class="btn-secondary"
                                data-modal-open="edit-note-{{ $note->id }}"
                            >
                                Edit
                            </button>

                            {{-- Delete --}}
                            <form
                                method="POST"
                                action="{{ route('admin.products.notes.destroy', $note) }}"
                                data-preserve-scroll
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition"
                                    onclick="return confirm('Delete note?')"
                                >
                                    Delete
                                </button>
                            </form>
                        </div>

                        <div
                            id="edit-note-{{ $note->id }}"
                            class="fixed inset-0 z-50 hidden bg-slate-900/50 p-4"
                            data-modal
                        >
                            <div class="mx-auto mt-16 max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                                <div class="mb-5 flex items-center justify-between gap-4">
                                    <h3 class="text-lg font-bold text-slate-800">
                                        Edit Note
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
                                    action="{{ route('admin.products.notes.update', $note) }}"
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
                                            value="{{ $note->title }}"
                                            class="form-input"
                                        >
                                    </div>

                                    <div>
                                        <label class="form-label">Description</label>
                                        <textarea
                                            name="description"
                                            rows="4"
                                            class="form-textarea"
                                            required
                                        >{{ $note->description }}</textarea>
                                    </div>

                                    <div>
                                        <label class="form-label">Sort Order</label>
                                        <input
                                            type="number"
                                            name="sort_order"
                                            value="{{ $note->sort_order }}"
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

                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-8 text-center">
                    <p class="text-sm text-slate-400">
                        No notes yet.
                    </p>
                </div>

            @endforelse

    @endif

</div>
