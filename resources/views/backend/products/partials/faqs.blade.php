<div class=" border-slate-200 bg-white p-5 shadow-sm">

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
            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
            data-preserve-scroll
        >
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

                <div class="md:col-span-7">
                    <label class="form-label">Question</label>
                    <input
                        type="text"
                        name="question"
                        class="form-input"
                        placeholder="Example: Is hotel pickup included?"
                        required
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Sort</label>
                    <input
                        type="number"
                        name="sort_order"
                        class="form-input"
                        value="0"
                    >
                </div>

                <div class="md:col-span-3 flex items-end">
                    <button type="submit" class="btn-primary w-full">
                        Add FAQ
                    </button>
                </div>

                <div class="md:col-span-12">
                    <label class="form-label">Answer</label>
                    <textarea
                        name="answer"
                        rows="2"
                        class="form-textarea resize-y min-h-[80px]"
                        placeholder="Example: Yes, hotel pickup is included."
                        required
                    ></textarea>
                </div>

            </div>
        </form>

        <div class="my-5 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">
                FAQ List
            </h3>

            <span class="text-xs text-slate-400">
                {{ $product->faqs->count() }} item(s)
            </span>
        </div>

        {{-- FAQ List --}}
        <div class="space-y-3">

            @forelse($product->faqs as $faq)

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">

                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                {{ $faq->sort_order }}
                            </span>

                            <h4 class="font-semibold text-slate-800 leading-snug">
                                {{ $faq->question }}
                            </h4>
                        </div>

                        <p class="text-sm text-slate-500 leading-relaxed whitespace-pre-line">
                            {{ $faq->answer }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            class="btn-secondary"
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
                                class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition"
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
                                <h3 class="text-lg font-bold text-slate-800">
                                    Edit FAQ
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
                                action="{{ route('admin.products.faqs.update', $faq) }}"
                                class="space-y-4"
                                data-preserve-scroll
                            >
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="form-label">Question</label>
                                    <input
                                        type="text"
                                        name="question"
                                        value="{{ $faq->question }}"
                                        class="form-input"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="form-label">Answer</label>
                                    <textarea
                                        name="answer"
                                        rows="4"
                                        class="form-textarea"
                                        required
                                    >{{ $faq->answer }}</textarea>
                                </div>

                                <div>
                                    <label class="form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $faq->sort_order }}"
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
                    No FAQ yet.
                </p>
            </div>

        @endforelse

        </div>

    @endif

</div>
