<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- Booking Button --}}
    <div class="md:col-span-2">
        <h3 class="text-lg font-bold text-slate-800">
            Booking Button
        </h3>

        <p class="text-sm text-slate-500">
            Control booking text shown on the product page.
        </p>
    </div>

    <div>
        <label class="form-label">
            Booking Box Title
        </label>

        <input
            type="text"
            name="cta_title"
            value="{{ old('cta_title', $product->cta_title ?? '') }}"
            class="form-input"
            placeholder="Example: Need help booking this tour?"
        >
    </div>

    <div>
        <label class="form-label">
            WhatsApp Button Text
        </label>

        <input
            type="text"
            name="cta_button_text"
            value="{{ old('cta_button_text', $product->cta_button_text ?? '') }}"
            class="form-input"
            placeholder="Example: Chat via WhatsApp"
        >
    </div>

    <div class="md:col-span-2">
        <label class="form-label">
            Booking Help Text
        </label>

        <textarea
            name="cta_description"
            rows="3"
            class="form-textarea"
            placeholder="Example: Contact our team via WhatsApp for fast booking assistance."
        >{{ old('cta_description', $product->cta_description ?? '') }}</textarea>
    </div>


    {{-- Pickup Information --}}
    <div class="md:col-span-2 pt-5 border-t">
        <h3 class="text-lg font-bold text-slate-800">
            Pickup Information
        </h3>

        <p class="text-sm text-slate-500">
            Explain pickup availability and pickup rules.
        </p>
    </div>

    <div class="md:col-span-2 rounded-xl border border-slate-200 p-4">
        <div class="flex items-center justify-between">

            <div>
                <h4 class="font-semibold text-slate-700">
                    Pickup Available
                </h4>

                <p class="text-sm text-slate-500">
                    Enable if this product supports pickup service.
                </p>
            </div>

            <label class="inline-flex cursor-pointer">

                <input
                    type="hidden"
                    name="pickup_available"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="pickup_available"
                    value="1"
                    class="sr-only peer"
                    {{ old('pickup_available', $product->pickup_available ?? false) ? 'checked' : '' }}
                >

                <div class="relative w-12 h-6 bg-slate-300 rounded-full
                    peer peer-checked:bg-emerald-600
                    after:content-['']
                    after:absolute after:left-[2px]
                    after:top-[2px]
                    after:bg-white after:h-5 after:w-5
                    after:rounded-full after:transition-all
                    peer-checked:after:translate-x-full">
                </div>

            </label>

        </div>
    </div>

    <div>
        <label class="form-label">
            Pickup Type
        </label>

        <input
            type="text"
            name="pickup_type"
            value="{{ old('pickup_type', $product->pickup_type ?? '') }}"
            class="form-input"
            placeholder="Example: Hotel Pickup / Meeting Point Only"
        >
    </div>

    <div class="md:col-span-2">
        <label class="form-label">
            Pickup Note
        </label>

        <textarea
            name="pickup_note"
            rows="3"
            class="form-textarea"
            placeholder="Example: Pickup available from selected hotels only."
        >{{ old('pickup_note', $product->pickup_note ?? '') }}</textarea>
    </div>


    {{-- Google / Social Preview --}}
    <div class="md:col-span-2 pt-5 border-t">
        <h3 class="text-lg font-bold text-slate-800">
            Google / Social Preview
        </h3>

        <p class="text-sm text-slate-500">
            Optional settings for Google search and link sharing.
        </p>
    </div>

    <div>
        <label class="form-label">
            Google Title
        </label>

        <input
            type="text"
            name="meta_title"
            value="{{ old('meta_title', $product->meta_title ?? '') }}"
            class="form-input"
            placeholder="Example: Bintan Mangrove Tour"
        >
    </div>

    <div>
        <label class="form-label">
            Main Page URL
        </label>

        <input
            type="url"
            name="canonical_url"
            value="{{ old('canonical_url', $product->canonical_url ?? '') }}"
            class="form-input"
            placeholder="https://example.com/products/bintan-tour"
        >
    </div>

    <div class="md:col-span-2">
        <label class="form-label">
            Google Description
        </label>

        <textarea
            name="meta_description"
            rows="3"
            class="form-textarea"
            placeholder="Short description shown on Google search result."
        >{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label class="form-label">
            Search Keywords
        </label>

        <textarea
            name="meta_keywords"
            rows="2"
            class="form-textarea"
            placeholder="bintan tour, bintan activity, mangrove tour"
        >{{ old('meta_keywords', $product->meta_keywords ?? '') }}</textarea>
    </div>

</div>