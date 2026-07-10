<div id="notes-section" class="admin-card">
    <div class="admin-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-admin-primary">
                    Product Notes
                </h3>

                <p class="mt-1 text-sm leading-6 text-admin-secondary">
                    Add important reminders, rules, and guest-facing notes.
                </p>
            </div>

            @if(isset($product) && $product->exists)
                <span class="admin-badge-info w-fit">
                    {{ $product->notes->count() }} item(s)
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
                    Product notes can be added after the product is created.
                </p>
            </div>

        @else

            <form
                method="POST"
                action="{{ route('admin.products.notes.store', $product) }}"
                class="admin-form-card space-y-5"
                data-preserve-scroll
            >
                @csrf

                <div>
                    <h4 class="text-base font-extrabold text-admin-primary">
                        Add Note
                    </h4>
                    <p class="mt-1 text-sm text-admin-secondary">
                        Keep notes concise and easy for guests to scan.
                    </p>
                </div>

                <div>
                    <label class="admin-form-label">Title</label>

                    <input
                        type="text"
                        name="title"
                        class="admin-input"
                        placeholder="Example: Important Information"
                    >
                </div>

                <div>
                    <label class="admin-form-label">Description</label>

                    <textarea
                        name="description"
                        rows="4"
                        class="admin-textarea"
                        placeholder="Example: Bring sunscreen and comfortable clothes."
                        required
                    ></textarea>
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
                    Add Note
                </button>
            </form>

            <div class="my-6 border-t border-admin"></div>

            <div class="space-y-4">
                @forelse($product->notes as $note)

                    <div class="admin-list-item shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                            <div class="flex min-w-0 flex-1 gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 font-bold text-amber-700">
                                    !
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        {{-- Admin is EN-only (§1.2); show base column regardless. --}}
                                        @if($note->getRawOriginal('title'))
                                            <h4 class="font-semibold leading-snug text-admin-primary">
                                                {{ $note->getRawOriginal('title') }}
                                            </h4>
                                        @else
                                            <h4 class="font-semibold leading-snug text-admin-primary">
                                                Product Note
                                            </h4>
                                        @endif

                                        <span class="rounded-full border border-admin bg-admin-card px-2 py-1 text-[11px] font-medium text-admin-secondary">
                                            #{{ $note->sort_order }}
                                        </span>
                                    </div>

                                    <p class="whitespace-pre-line text-sm leading-relaxed text-admin-secondary">
                                        {{ $note->getRawOriginal('description') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                                <button
                                    type="button"
                                    class="admin-btn-secondary w-full sm:w-auto"
                                    data-modal-open="edit-note-{{ $note->id }}"
                                >
                                    Edit
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('admin.products.notes.destroy', $note) }}"
                                    data-preserve-scroll
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="admin-btn-danger w-full sm:w-auto"
                                        data-confirm="Delete note?"
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
                                <div class="admin-modal-content mx-auto mt-16 max-w-2xl">
                                    <div class="mb-5 flex items-center justify-between gap-4">
                                        <h3 class="text-lg font-bold text-admin-primary">
                                            Edit Note
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
                                        action="{{ route('admin.products.notes.update', $note) }}"
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
                                                value="{{ $note->getRawOriginal('title') }}"
                                                class="admin-input"
                                            >
                                        </div>

                                        <div>
                                            <label class="admin-form-label">Description</label>
                                            <textarea
                                                name="description"
                                                rows="4"
                                                class="admin-textarea"
                                                required
                                            >{{ $note->getRawOriginal('description') }}</textarea>
                                        </div>

                                        @include('backend.products.partials._translations-inline', [
                                            'record' => $note,
                                            'fields' => ['title' => 'Title', 'description' => 'Description'],
                                            'textareaFields' => ['description'],
                                        ])

                                        <div>
                                            <label class="admin-form-label">Sort Order</label>
                                            <input
                                                type="number"
                                                name="sort_order"
                                                value="{{ $note->sort_order }}"
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
                        No notes yet.
                    </div>

                @endforelse
            </div>

        @endif
    </div>
</div>
