@php
    $inputClass = 'w-full rounded-xl border bg-white px-4 py-3 text-sm
    transition duration-200 outline-none focus:ring-4';

    $normalClass = 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-100 shadow-sm';

    $errorClass = 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-100 shadow-md shadow-red-100';
@endphp

<div class="admin-page">

    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <h1 class="admin-page-title">
                    {{ isset($product)
                        ? 'Edit Product'
                        : 'Create Product' }}
                </h1>

                <p class="admin-page-subtitle">
                    {{ isset($product)
                        ? 'Update product information'
                        : 'Create a new product' }}
                </p>
            </div>

            <a href="{{ route('admin.products.index') }}"
               class="admin-btn-secondary w-full sm:w-auto">
                Back
            </a>

        </div>
    </div>

    <div class="admin-form-card">
        <form
            action="{{ isset($product)
                ? route('admin.products.update', $product)
                : route('admin.products.store') }}"
            method="POST"
            enctype="multipart/form-data"
        >

        @csrf

        @isset($product)
            @method('PUT')
        @endisset

        <div class="space-y-6">

        {{-- PRODUCT INFO --}}
        <details
            id="product-info-section"
            open
            class="admin-card"
            data-product-accordion
        >

            <summary
                class="cursor-pointer border-b border-slate-100 bg-slate-50 px-6 py-5"
            >
                <span class="block text-lg font-extrabold text-slate-900">
                    Basic Information
                </span>
                <span class="mt-1 block text-sm leading-6 text-slate-500">
                    Manage the primary product identity, booking context, and publishing state.
                </span>
            </summary>

            <div class="admin-card-body space-y-8">

                <section>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="admin-form-label">
                                Product Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $product->name ?? '') }}"
                                placeholder="Enter product name"
                                class="admin-input {{ $errors->has('name') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >

                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="admin-form-label">Slug</label>

                            <input
                                type="text"
                                name="slug"
                                value="{{ old('slug', $product->slug ?? '') }}"
                                placeholder="Auto generate if empty"
                                class="admin-input"
                            >

                            <p class="mt-2 text-xs text-slate-400">
                                Optional. Leave empty to auto-generate from product name.
                            </p>
                        </div>

                        <div>
                            <label class="admin-form-label">
                                Category
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="category_id"
                                class="admin-select {{ $errors->has('category_id') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >
                                <option value="">Select Category</option>

                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected(old('category_id', $product->category_id ?? '') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="admin-form-label">
                                Destination
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="destination_id"
                                class="admin-select {{ $errors->has('destination_id') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >
                                <option value="">Select Destination</option>

                                @foreach($destinations as $destination)
                                    <option value="{{ $destination->id }}"
                                        @selected(old('destination_id', $product->destination_id ?? '') == $destination->id)>
                                        {{ $destination->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="admin-form-label">
                                WhatsApp Number
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="whatsapp_number"
                                value="{{ old('whatsapp_number', $product->whatsapp_number ?? '') }}"
                                placeholder="628xxxxxxxx"
                                class="admin-input {{ $errors->has('whatsapp_number') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >

                            <p class="mt-2 text-xs text-slate-400">
                                Used for the booking CTA on the product page.
                            </p>

                            @error('whatsapp_number')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="admin-form-label">
                                Duration
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="duration"
                                value="{{ old('duration', $product->duration ?? '') }}"
                                placeholder="Example: 3 hours / Full day"
                                class="admin-input {{ $errors->has('duration') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >

                            <p class="mt-2 text-xs text-slate-400">
                                Example: 3 hours, Half day, or Full day.
                            </p>

                            @error('duration')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="admin-form-label">
                                Meeting Point
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="meeting_point"
                                value="{{ old('meeting_point', $product->meeting_point ?? '') }}"
                                placeholder="Enter meeting point"
                                class="admin-input {{ $errors->has('meeting_point') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >

                            @error('meeting_point')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="admin-form-label">
                                Short Description
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                name="short_description"
                                rows="4"
                                placeholder="Input short description"
                                class="admin-textarea {{ $errors->has('short_description') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >{{ old('short_description', $product->short_description ?? '') }}</textarea>

                            @error('short_description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="admin-form-label">
                                Description
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                name="description"
                                rows="5"
                                class="admin-textarea {{ $errors->has('description') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                                placeholder="Enter product description"
                            >{{ old('description', $product->description ?? '') }}</textarea>

                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="admin-form-label">
                                Status
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="status"
                                class="admin-select {{ $errors->has('status') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                            >
                                <option value="draft"
                                    @selected(old('status', $product->status ?? 'draft') == 'draft')>
                                    Draft
                                </option>

                                <option value="published"
                                    @selected(old('status', $product->status ?? '') == 'published')>
                                    Published
                                </option>
                            </select>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="font-semibold text-slate-700">
                                        Featured Product
                                    </h4>

                                    <p class="text-sm text-slate-500">
                                        Show on featured section.
                                    </p>
                                </div>

                                <label class="inline-flex cursor-pointer">
                                    <input
                                        type="hidden"
                                        name="is_featured"
                                        value="0"
                                    >

                                    <input
                                        type="checkbox"
                                        name="is_featured"
                                        value="1"
                                        class="sr-only peer"
                                        {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}
                                    >

                                    <div class="relative h-6 w-12 rounded-full bg-slate-300
                                        peer peer-checked:bg-indigo-600
                                        after:absolute after:left-[2px]
                                        after:top-[2px]
                                        after:h-5 after:w-5
                                        after:rounded-full after:bg-white
                                        after:transition-all after:content-['']
                                        peer-checked:after:translate-x-full">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="text-lg font-extrabold text-slate-900">
                            Pricing
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Set product prices for local and international markets.
                        </p>
                    </div>

                    <div class="admin-card-body grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- IDR Price --}}
                        <div>
                            <label class="admin-form-label">
                                IDR Price
                                <span class="text-red-500">*</span>
                            </label>

                            <input type="number"
                                name="idr_price"
                                value="{{ old(
                                    'idr_price',
                                    $product->idr_price ?? ''
                                ) }}"
                                placeholder="500000"
                                class="admin-input {{ $errors->has('idr_price') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}">

                            <p class="mt-2 text-xs text-slate-400">
                                Main local market price.
                            </p>

                            @error('idr_price')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SGD Price --}}
                        <div>
                            <label class="admin-form-label">
                                SGD Price
                                <span class="text-red-500">*</span>
                            </label>

                            <input type="number"
                                name="sgd_price"
                                value="{{ old(
                                    'sgd_price',
                                    $product->sgd_price ?? ''
                                ) }}"
                                class="admin-input {{ $errors->has('sgd_price') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}">

                            <p class="mt-2 text-xs text-slate-400">
                                Singapore/international market price.
                            </p>

                            @error('sgd_price')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        {{-- Thumbnail Upload --}}
                        <div class="md:col-span-2">

                            <label class="block mb-2 font-medium">
                                Thumbnail
                            </label>

                            <input type="file"
                                name="thumbnail"
                                class="w-full border rounded-lg p-3">

                            @error('thumbnail')
                                <p class="text-red-500 text-sm mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-2 text-xs text-slate-400">
                                Leave thumbnail empty to auto-use first gallery image.
                            </p>

                        </div>

                        {{-- Gallery Images --}}
                        <div class="md:col-span-2">

                            <label class="block mb-2 font-medium">
                                Gallery Images
                            </label>

                            <input
                                type="file"
                                name="gallery[]"
                                multiple
                                class="w-full border rounded-lg p-3">

                            <p class="text-sm text-gray-500 mt-2">
                                Multiple upload supported.
                                Auto optimized to WEBP.
                            </p>

                        </div>

                        @if(isset($product)
                        && $product->images->count())

                        <div class="md:col-span-2">

                            <label class="block mb-4 font-medium">
                                Product Gallery
                            </label>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                                @foreach($product->images as $image)

                                    <div class="relative overflow-hidden rounded-xl border bg-white shadow">

                                        <img
                                            src="{{ asset(
                                                'storage/' .
                                                $image->image
                                            ) }}"
                                            class="w-full aspect-[16/9]
                                            object-cover">

                                        <div class="space-y-2 p-3">
                                            @if(($product->thumbnail ?? null) === $image->image)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Current Thumbnail
                                                </span>
                                            @else
                                                <button
                                                    type="submit"
                                                    form="set-thumbnail-{{ $image->id }}"
                                                    class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                                >
                                                    Set as Thumbnail
                                                </button>
                                            @endif

                                            <button
                                                type="submit"
                                                form="delete-gallery-image-{{ $image->id }}"
                                                class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                                onclick="return confirm('Delete this gallery image?')"
                                            >
                                                Delete Image
                                            </button>
                                        </div>


                                    </div>

                                @endforeach

                            </div>

                        </div>

                        @endif

                        {{-- Preview Image --}}
                        @if(isset($product) && $product->thumbnail)

                        <div class="md:col-span-2">

                            <label class="block mb-2 font-medium">
                                Current Thumbnail
                            </label>

                            <img src="{{ asset('storage/'.$product->thumbnail) }}"
                                class="w-48 rounded-lg border shadow">

                            <button
                                type="submit"
                                form="delete-product-thumbnail"
                                class="mt-3 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                                onclick="return confirm('Remove current thumbnail?')"
                            >
                                Remove Thumbnail
                            </button>

                        </div>

                        @endif
                    </div>
                </section>

            </div>

        </details>


        {{-- ACTION BUTTON --}}
        <div
            class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center"
        >

            <button
                type="submit"
                class="admin-btn-primary w-full sm:w-auto"
            >
                {{ isset($product)
                    ? 'Update Product'
                    : 'Create Product' }}
            </button>

            <a
                href="{{ route('admin.products.index') }}"
                class="admin-btn-secondary w-full sm:w-auto"
            >
                Cancel
            </a>

        </div>

    </div>

        </form>
    </div>

    @if(isset($product) && $product->exists)
        @foreach($product->images as $image)
            <form
                id="set-thumbnail-{{ $image->id }}"
                method="POST"
                action="{{ route('admin.products.images.thumbnail', $image) }}"
                data-preserve-scroll
            >
                @csrf
                @method('PATCH')
            </form>

            <form
                id="delete-gallery-image-{{ $image->id }}"
                method="POST"
                action="{{ route('admin.products.images.destroy', $image) }}"
                data-preserve-scroll
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <form
            id="delete-product-thumbnail"
            method="POST"
            action="{{ route('admin.products.thumbnail.destroy', $product) }}"
            data-preserve-scroll
        >
            @csrf
            @method('DELETE')
        </form>
    @endif

    @if(isset($product) && $product->exists)

        @php
            $featureLabels = [
                'included' => 'Included',
                'excluded' => 'Excluded',
                'optional' => 'Optional',
                'addon' => 'Addon',
                'important' => 'Important',
            ];

            $contentChecklist = [
                [
                    'label' => 'Overview',
                    'target' => 'product-info-section',
                    'ready' => filled($product->short_description)
                        && filled($product->description),
                ],
                [
                    'label' => 'Highlights',
                    'target' => 'highlights-section',
                    'ready' => $product->highlights->isNotEmpty(),
                ],
                [
                    'label' => 'Included',
                    'target' => 'features-section',
                    'ready' => $product->features
                        ->where('label', 'included')
                        ->isNotEmpty(),
                ],
                [
                    'label' => 'Excluded',
                    'target' => 'features-section',
                    'ready' => $product->features
                        ->where('label', 'excluded')
                        ->isNotEmpty(),
                ],
                [
                    'label' => 'Itinerary',
                    'target' => 'itineraries-section',
                    'ready' => $product->itineraries->isNotEmpty(),
                ],
                [
                    'label' => 'FAQ',
                    'target' => 'faqs-section',
                    'ready' => $product->faqs->isNotEmpty(),
                ],
                [
                    'label' => 'Notes',
                    'target' => 'notes-section',
                    'ready' => $product->notes->isNotEmpty(),
                ],
                [
                    'label' => 'Gallery',
                    'target' => 'product-info-section',
                    'ready' => $product->images->isNotEmpty()
                        || filled($product->thumbnail),
                ],
                [
                    'label' => 'Pricing',
                    'target' => 'product-info-section',
                    'ready' => filled($product->idr_price)
                        && filled($product->sgd_price),
                ],
                [
                    'label' => 'WhatsApp CTA',
                    'target' => 'product-info-section',
                    'ready' => filled($product->whatsapp_number),
                ],
            ];

            $missingChecklist = collect($contentChecklist)
                ->where('ready', false)
                ->pluck('label');

            $includedCount = $product->features
                ->where('label', 'included')
                ->count();

            $excludedCount = $product->features
                ->where('label', 'excluded')
                ->count();
        @endphp

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Product Detail Content
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage highlights, features, FAQs, itineraries, and notes for this product.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-800">
                            Frontend Readiness
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Use this checklist before publishing so the frontend content is complete.
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold
                        {{ $missingChecklist->isEmpty()
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-amber-100 text-amber-700' }}">
                        {{ $missingChecklist->isEmpty()
                            ? 'Ready to publish'
                            : $missingChecklist->count() . ' item(s) need attention' }}
                    </span>
                </div>

                @if($includedCount === 0 || $excludedCount === 0)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Please add at least one Included and one Excluded feature first.
                        These two sections are usually the most important for guests before booking.
                    </div>
                @endif

                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($contentChecklist as $item)
                        <a
                            href="#{{ $item['target'] }}"
                            class="flex items-center gap-3 rounded-xl border bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-sm
                            {{ $item['ready']
                                ? 'border-emerald-200'
                                : 'border-amber-200' }}">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $item['ready']
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700' }}">
                                {{ $item['ready'] ? 'OK' : '!' }}
                            </span>

                            <span class="text-sm font-medium text-slate-700">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">
                    Tour Package Structure
                </h3>

                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    @foreach($contentChecklist as $item)
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2">
                                <span class="h-px w-5 bg-slate-300"></span>
                                {{ $item['label'] }}
                            </span>

                            <span class="text-xs font-semibold
                                {{ $item['ready']
                                    ? 'text-emerald-600'
                                    : 'text-amber-600' }}">
                                {{ $item['ready'] ? 'Ready' : 'Missing' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- PRODUCT HIGHLIGHTS --}}
            <details
                id="highlights-section"
                class="card-section mt-6"
                data-product-accordion
                open
            >
                <summary class="accordion-summary">
                    <span>Product Highlights & Features</span>
                    <span class="text-xs font-medium text-slate-400">
                        {{ $product->highlights->count() }} highlights /
                        {{ $product->features->count() }} features
                    </span>
                </summary>

                <div class="card-body">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @include('backend.products.partials.highlights')
                        @include('backend.products.partials.features')
                    </div>
                </div>
            </details>

        {{-- PRODUCT FAQs --}}
            <details
                id="faqs-section"
                class="card-section mt-6"
                data-product-accordion
            >
                <summary class="accordion-summary">
                    <span>Product FAQs</span>
                    <span class="text-xs font-medium text-slate-400">
                        {{ $product->faqs->count() }} FAQ
                    </span>
                </summary>

                <div class="card-body">
                    @include('backend.products.partials.faqs')
                </div>
            </details>

        {{-- PRODUCT ITINERARIES & NOTES --}}
            <details
                id="itineraries-section"
                class="card-section mt-6"
                data-product-accordion
            >
                <summary class="accordion-summary">
                    <span>Product Itineraries & Notes</span>
                    <span class="text-xs font-medium text-slate-400">
                        {{ $product->itineraries->count() }} itineraries /
                        {{ $product->notes->count() }} notes
                    </span>
                </summary>

                <div class="card-body">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @include('backend.products.partials.itineraries')
                        @include('backend.products.partials.notes')
                    </div>
                </div>
            </details>

        {{-- SEARCH & BOOKING SETTINGS --}}
        <details
            id="search-booking-section"
            class="card-section mt-6"
            data-product-accordion
        >
            <summary class="accordion-summary">
                Search & Booking Settings
            </summary>

            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('admin.products.search-booking.update', $product) }}"
                    class="space-y-6"
                    data-preserve-scroll
                >
                    @csrf
                    @method('PUT')

                    @include('backend.products.partials.search-booking')

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            Save these settings separately from the main product information.
                        </p>

                        <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                            Save Search & Booking
                        </button>
                    </div>
                </form>
            </div>
        </details>

    @else

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h2 class="font-semibold text-amber-800">
                Save product first
            </h2>

            <p class="mt-2 text-sm text-amber-700">
                Highlights, features, FAQs, itineraries, and notes can be added after the product is created.
            </p>
        </div>

    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const accordions = [...document.querySelectorAll('[data-product-accordion]')];
    const detailForms = document.querySelectorAll('[data-preserve-scroll]');
    const modalOpenButtons = document.querySelectorAll('[data-modal-open]');
    const modalCloseButtons = document.querySelectorAll('[data-modal-close]');

    function openTargetFromHash(hash) {
        if (!hash) {
            return;
        }

        const target = document.querySelector(hash);

        if (!target) {
            return;
        }

        const accordion = target.matches('[data-product-accordion]')
            ? target
            : target.closest('[data-product-accordion]');

        if (accordion) {
            accordions.forEach(item => {
                item.open = item === accordion;
            });
        }

        window.setTimeout(() => {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }, 50);
    }

    accordions.forEach(accordion => {
        accordion.addEventListener('toggle', () => {
            if (!accordion.open) {
                return;
            }

            accordions.forEach(item => {
                if (item !== accordion) {
                    item.open = false;
                }
            });
        });
    });

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', event => {
            const hash = link.getAttribute('href');

            if (!hash || hash === '#') {
                return;
            }

            event.preventDefault();
            history.replaceState(null, '', hash);
            openTargetFromHash(hash);
        });
    });

    detailForms.forEach(form => {
        form.addEventListener('submit', () => {
            sessionStorage.setItem(
                'productDetailScrollY',
                String(window.scrollY)
            );
        });
    });

    modalOpenButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.modalOpen);

            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    });

    modalCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-modal]');

            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-modal]').forEach(modal => {
            modal.classList.add('hidden');
        });
    });

    openTargetFromHash(window.location.hash);

    const storedScrollY = sessionStorage.getItem('productDetailScrollY');

    if (storedScrollY && !window.location.hash) {
        sessionStorage.removeItem('productDetailScrollY');

        window.setTimeout(() => {
            window.scrollTo({
                top: Number(storedScrollY),
                behavior: 'smooth'
            });
        }, 50);
    }
});
</script>
