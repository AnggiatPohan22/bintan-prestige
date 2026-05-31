@php
    $inputClass = 'w-full rounded-xl border bg-white px-4 py-3 text-sm
    transition duration-200 outline-none focus:ring-4';

    $normalClass = 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-100 shadow-sm';

    $errorClass = 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-100 shadow-md shadow-red-100';
@endphp

<div class="bg-white rounded-xl shadow p-6">

    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold">
                {{ isset($product)
                    ? 'Edit Product'
                    : 'Create Product' }}
            </h1>

            <p class="text-gray-500 text-sm mt-1">
                {{ isset($product)
                    ? 'Update product information'
                    : 'Create a new product' }}
            </p>
        </div>

        <a href="{{ route('admin.products.index') }}"
           class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition">
            Cancel
        </a>

    </div>

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

    <div class="space-y-5">

        {{-- PRODUCT INFO --}}
        <details
            id="product-info-section"
            open
            class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
            data-product-accordion
        >

            <summary
                class="cursor-pointer px-6 py-5 font-semibold text-slate-800 bg-slate-50 border-b"
            >
                Product Information
            </summary>

            <div class="p-6">

                {{-- Product Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    @include(
                    'backend.products.partials.products'
                    )

                </div>

            </div>

        </details>


        {{-- ACTION BUTTON --}}
        <div
            class="pt-5 border-t flex items-center gap-3"
        >

            <button
                type="submit"
                class="btn-primary"
            >
                {{ isset($product)
                    ? 'Update Product'
                    : 'Create Product' }}
            </button>

            <a
                href="{{ route('admin.products.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

        </div>

    </div>

    </form>

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

        <div class="mt-8 border-t border-slate-200 pt-6">
            <h2 class="text-xl font-bold text-slate-800">
                Product Detail Content
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Manage highlights, features, FAQs, itineraries, and notes for this product.
            </p>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
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

                        <button type="submit" class="btn-primary">
                            Save Search & Booking
                        </button>
                    </div>
                </form>
            </div>
        </details>

    @else

        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
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
