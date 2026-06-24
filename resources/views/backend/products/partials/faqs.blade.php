<div class="admin-card">
    <div class="admin-card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-slate-100">
                    Product FAQs
                </h3>

                <p class="mt-1 text-sm leading-6 text-slate-400">
                    Manage common guest questions for this product.
                </p>
            </div>

            @if(isset($product) && $product->exists)
                <span class="admin-badge-info w-fit">
                    {{ $product->faqs->count() }} item(s)
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
                Product FAQ can be added after the product is created.
            </p>
        </div>

    @else

        {{-- Add FAQ --}}
        <form
            method="POST"
            action="{{ route('admin.products.faqs.store', $product) }}"
            class="admin-form-card space-y-5"
            data-preserve-scroll
        >
            @csrf

            <div>
                <h4 class="text-base font-extrabold text-slate-100">
                    Add FAQ
                </h4>
                <p class="mt-1 text-sm text-slate-400">
                    Add short answers guests can scan before booking.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

                <div class="md:col-span-7">
                    <label class="admin-form-label">Question</label>
                    <input
                        type="text"
                        name="question"
                        class="admin-input"
                        placeholder="Example: Is hotel pickup included?"
                        required
                    >
                </div>

                <div class="md:col-span-5 lg:col-span-2">
                    <label class="admin-form-label">Sort</label>
                    <input
                        type="number"
                        name="sort_order"
                        class="admin-input"
                        value="0"
                    >
                </div>

                <div class="md:col-span-12 lg:col-span-3 flex items-end">
                    <button type="submit" class="admin-btn-primary w-full">
                        Add FAQ
                    </button>
                </div>

                <div class="md:col-span-12">
                    <label class="admin-form-label">Answer</label>
                    <textarea
                        name="answer"
                        rows="2"
                        class="admin-textarea min-h-[96px]"
                        placeholder="Example: Yes, hotel pickup is included."
                        required
                    ></textarea>
                </div>

            </div>
        </form>

        <div class="my-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-semibold text-slate-100">
                FAQ List
            </h3>

            <span class="text-xs text-slate-400">
                {{ $product->faqs->count() }} item(s)
            </span>
        </div>

        {{-- FAQ List --}}
        <div class="space-y-3">

            @forelse($product->faqs as $faq)

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                {{ $faq->sort_order }}
                            </span>

                            <h4 class="font-semibold text-slate-100 leading-snug">
                                {{ $faq->question }}
                            </h4>
                        </div>

                        <p class="text-sm text-slate-400 leading-relaxed whitespace-pre-line">
                            {{ $faq->answer }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            class="admin-btn-secondary w-full sm:w-auto"
                            data-modal-open="edit-faq-{{ $faq->id }}"
                        >
                            Edit
                        </button>

                        <form
                            method="POST"
                            action="{{ route('admin.products.faqs.destroy', $faq) }}"
                            data-preserve-scroll
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="admin-btn-danger w-full sm:w-auto"
                                onclick="return confirm('Delete FAQ?')"
                            >
                                Delete
                            </button>
                        </form>
                    </div>

                    <div
                        id="edit-faq-{{ $faq->id }}"
                        class="fixed inset-0 z-50 hidden bg-slate-900/50 p-4"
                        data-modal
                    >
                        <div class="mx-auto mt-16 max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                            <div class="mb-5 flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold text-slate-100">
                                    Edit FAQ
                                </h3>

                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-700"
                                    data-modal-close
                                >
                                    X
                                </button>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.products.faqs.update', $faq) }}"
                                class="space-y-4"
                                data-preserve-scroll
                            >
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="admin-form-label">Question</label>
                                    <input
                                        type="text"
                                        name="question"
                                        value="{{ $faq->question }}"
                                        class="admin-input"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="admin-form-label">Answer</label>
                                    <textarea
                                        name="answer"
                                        rows="4"
                                        class="admin-textarea"
                                        required
                                    >{{ $faq->answer }}</textarea>
                                </div>

                                <div>
                                    <label class="admin-form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $faq->sort_order }}"
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
                No FAQ yet.
            </div>

        @endforelse

        </div>

    @endif

    </div>
</div>
