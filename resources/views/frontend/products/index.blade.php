@extends('layouts.frontend')

@section('content')

@php
    $heroPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'hero');
    $heroPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'hero');
    $productHeroBackgroundSize = match ($heroPlaceholderFit) {
        'fill' => '100% 100%',
        'scale-down' => 'contain',
        default => $heroPlaceholderFit,
    };
    $listingHero = $listingContent['hero'] ?? [];
    $listingCatalog = $listingContent['catalog'] ?? [];
    $productHeroBackground = $listingHero['image_url']
        ?? $products->getCollection()->firstWhere('thumbnail_url')?->thumbnail_url
        ?? $heroPlaceholder?->url;
@endphp

<div
    class="product-page"
    data-page-key="products.index"
    x-data="{
        filterOpen: false,
        sortOpen: false,
        mediaOpen: false,
        mediaType: 'image',
        mediaTitle: '',
        mediaItems: [],
        mediaIndex: 0,
        openProductMedia(event) {
            const detail = event.detail || {};

            this.mediaType = detail.type || 'image';
            this.mediaTitle = detail.title || '';
            this.mediaItems = detail.items || [];
            this.mediaIndex = 0;
            this.mediaOpen = true;
        },
        closeProductMedia() {
            this.mediaOpen = false;
            this.mediaItems = [];
            this.mediaIndex = 0;
        },
        nextProductMedia() {
            if (! this.mediaItems.length) return;
            this.mediaIndex = (this.mediaIndex + 1) % this.mediaItems.length;
        },
        previousProductMedia() {
            if (! this.mediaItems.length) return;
            this.mediaIndex = (this.mediaIndex - 1 + this.mediaItems.length) % this.mediaItems.length;
        },
    }"
    x-on:open-product-media.window="openProductMedia($event)"
    x-on:keydown.escape.window="filterOpen = false; sortOpen = false; closeProductMedia()"
    x-on:keydown.arrow-right.window="if (mediaOpen) nextProductMedia()"
    x-on:keydown.arrow-left.window="if (mediaOpen) previousProductMedia()"
>
    <section
        id="products-index-hero"
        class="product-hero"
        data-section-key="products.index.hero"
        @if($productHeroBackground)
            style="--product-hero-image: url('{{ $productHeroBackground }}'); --product-hero-media-fit: {{ $productHeroBackgroundSize }}"
        @endif
    >
        <div class="product-hero__container">

            <div class="product-hero__content">
                @if(! empty($listingHero['label']))
                    <p class="product-modal__eyebrow">
                        {{ $listingHero['label'] }}
                    </p>
                @endif

                <h1 class="product-hero__title title-section">
                    {{ $listingHero['title'] }}
                </h1>

                <p class="product-hero__description text-body">
                    {{ $listingHero['description'] }}
                </p>

                @if(! empty($listingHero['subtitle']))
                    <p class="product-hero__description text-body">
                        {{ $listingHero['subtitle'] }}
                    </p>
                @endif
            </div>

        </div>
    </section>

    <section id="products-index-catalog" class="product-section" data-section-key="products.index.catalog">
        <div class="product-container">

            <nav class="product-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('products.index') }}" class="product-breadcrumb__link">
                    Products
                </a>
                <span class="product-breadcrumb__separator" aria-hidden="true">/</span>
                <span class="product-breadcrumb__current">
                    All Tour
                </span>
            </nav>

            <div class="product-toolbar">
                <div>
                    <h2 class="product-toolbar__title title-card">
                        {{ $listingCatalog['title'] }}
                    </h2>

                    <p class="product-toolbar__text text-muted">
                        {{ $products->total() }} packages available for your next Bintan experience.
                    </p>

                    @if(! empty($listingCatalog['description']))
                        <p class="product-toolbar__text text-muted">
                            {{ $listingCatalog['description'] }}
                        </p>
                    @endif
                </div>

                <div class="product-actions">
                    <button
                        type="button"
                        class="btn btn-outline btn-sm product-action-button"
                        x-on:click="filterOpen = true"
                    >
                        Filter
                        @if($activeFilterCount)
                            <span class="product-action-button__badge">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline btn-sm product-action-button"
                        x-on:click="sortOpen = true"
                    >
                        Urutkan
                        <span class="product-action-button__label">
                            {{ $sortOptions[$sort] ?? 'Tour Terbaru' }}
                        </span>
                    </button>
                </div>
            </div>

            @if($products->count())

                <div class="product-grid-shell">
                    <div class="product-grid">
                        @foreach($products as $product)
                            @include('frontend.products.partials.card', [
                                'product' => $product
                            ])
                        @endforeach
                    </div>
                </div>

                <div class="product-pagination">
                    {{ $products->links() }}
                </div>

            @else

                <div class="product-empty">
                    <h3 class="product-empty__title">
                        No products available
                    </h3>

                    <p class="product-empty__text">
                        Try clearing filters or choose another destination.
                    </p>
                </div>

            @endif

            @if(! empty($listingCatalog['has_cta']))
                <div class="product-empty">
                    @if(! empty($listingCatalog['subtitle']))
                        <h3 class="product-empty__title">
                            {{ $listingCatalog['subtitle'] }}
                        </h3>
                    @endif

                    <a href="{{ $listingCatalog['cta_url'] }}" class="btn btn-submit">
                        {{ $listingCatalog['cta_text'] }}
                    </a>
                </div>
            @endif

        </div>
    </section>

    <div
        id="products-index-filter-modal"
        class="product-modal"
        data-section-key="products.index.filter_modal"
        x-cloak
        x-show="filterOpen"
        x-transition.opacity
        aria-modal="true"
        role="dialog"
    >
        <button
            type="button"
            class="product-modal__backdrop"
            aria-label="Close filter"
            x-on:click="filterOpen = false"
        ></button>

        <form
            method="GET"
            action="{{ route('products.index') }}"
            class="product-modal__panel"
            x-transition
        >
            <input type="hidden" name="sort" value="{{ $sort }}">

            <div class="product-modal__header">
                <div>
                    <p class="product-modal__eyebrow">
                        Refine packages
                    </p>

                    <h3 class="product-modal__title title-card">
                        Filter
                    </h3>
                </div>

                <button
                    type="button"
                    class="btn btn-ghost btn-icon product-modal__close"
                    x-on:click="filterOpen = false"
                    aria-label="Close filter"
                >
                    X
                </button>
            </div>

            <div class="product-filter">
                <section class="product-filter__group">
                    <h4 class="product-filter__title">
                        Rentang Harga {{ $priceCurrency }}
                    </h4>

                    <div class="product-filter__price-grid">
                        <label class="product-field">
                            <span class="product-field__label">Minimum</span>
                            <input
                                type="number"
                                name="min_price"
                                value="{{ $minPrice ?? '' }}"
                                placeholder="{{ $priceRange['min'] ? number_format($priceRange['min'], 0, ',', '.') : '0' }}"
                                class="product-field__input"
                            >
                        </label>

                        <label class="product-field">
                            <span class="product-field__label">Maximum</span>
                            <input
                                type="number"
                                name="max_price"
                                value="{{ $maxPrice ?? '' }}"
                                placeholder="{{ $priceRange['max'] ? number_format($priceRange['max'], 0, ',', '.') : '0' }}"
                                class="product-field__input"
                            >
                        </label>
                    </div>
                </section>

                <section class="product-filter__group">
                    <h4 class="product-filter__title">
                        Durasi
                    </h4>

                    <div class="product-filter__options">
                        @forelse($durations as $duration)
                            <label class="product-check">
                                <input
                                    type="checkbox"
                                    name="duration[]"
                                    value="{{ $duration }}"
                                    @checked(in_array($duration, $selectedDurations))
                                >
                                <span>{{ $duration }}</span>
                            </label>
                        @empty
                        <p class="product-filter__empty text-muted">No duration options yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="product-filter__group">
                    <h4 class="product-filter__title">
                        Destinations
                    </h4>

                    <div class="product-filter__options">
                        @foreach($destinations as $destination)
                            <label class="product-check">
                                <input
                                    type="checkbox"
                                    name="destination[]"
                                    value="{{ $destination->id }}"
                                    @checked(in_array((string) $destination->id, $selectedDestinations))
                                >
                                <span>{{ $destination->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="product-filter__group">
                    <h4 class="product-filter__title">
                        Jenis Tour
                    </h4>

                    <div class="product-filter__options">
                        @foreach($categories as $category)
                            <label class="product-check">
                                <input
                                    type="checkbox"
                                    name="category[]"
                                    value="{{ $category->id }}"
                                    @checked(in_array((string) $category->id, $selectedCategories))
                                >
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="product-filter__group">
                    <h4 class="product-filter__title">
                        Jenis Mobil
                    </h4>

                    <div class="product-filter__options">
                        @forelse($vehicleTypes as $vehicleType)
                            <label class="product-check">
                                <input
                                    type="checkbox"
                                    name="vehicle_type[]"
                                    value="{{ $vehicleType }}"
                                    @checked(in_array($vehicleType, $selectedVehicleTypes))
                                >
                                <span>{{ $vehicleType }}</span>
                            </label>
                        @empty
                            <p class="product-filter__empty text-muted">No vehicle options yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="product-modal__footer">
                <a href="{{ route('products.index', ['sort' => $sort]) }}" class="btn btn-outline product-modal__reset">
                    Hapus Filter
                </a>

                <button type="submit" class="btn btn-submit product-modal__submit">
                    Tampilkan {{ $filteredPackageCount }} Package
                </button>
            </div>
        </form>
    </div>

    <div
        id="products-index-sort-modal"
        class="product-modal"
        data-section-key="products.index.sort_modal"
        x-cloak
        x-show="sortOpen"
        x-transition.opacity
        aria-modal="true"
        role="dialog"
    >
        <button
            type="button"
            class="product-modal__backdrop"
            aria-label="Close sort"
            x-on:click="sortOpen = false"
        ></button>

        <form
            method="GET"
            action="{{ route('products.index') }}"
            class="product-modal__panel product-modal__panel--small"
            x-transition
        >
            @if($minPrice !== null)
                <input type="hidden" name="min_price" value="{{ $minPrice }}">
            @endif

            @if($maxPrice !== null)
                <input type="hidden" name="max_price" value="{{ $maxPrice }}">
            @endif

            @foreach($selectedDurations as $duration)
                <input type="hidden" name="duration[]" value="{{ $duration }}">
            @endforeach

            @foreach($selectedDestinations as $destination)
                <input type="hidden" name="destination[]" value="{{ $destination }}">
            @endforeach

            @foreach($selectedCategories as $category)
                <input type="hidden" name="category[]" value="{{ $category }}">
            @endforeach

            @foreach($selectedVehicleTypes as $vehicleType)
                <input type="hidden" name="vehicle_type[]" value="{{ $vehicleType }}">
            @endforeach

            <div class="product-modal__header">
                <div>
                    <p class="product-modal__eyebrow">
                        Sort packages
                    </p>

                    <h3 class="product-modal__title title-card">
                        Urutkan
                    </h3>
                </div>

                <button
                    type="button"
                    class="btn btn-ghost btn-icon product-modal__close"
                    x-on:click="sortOpen = false"
                    aria-label="Close sort"
                >
                    X
                </button>
            </div>

            <div class="product-sort-list">
                @foreach($sortOptions as $value => $label)
                    <label class="product-sort-option">
                        <input
                            type="radio"
                            name="sort"
                            value="{{ $value }}"
                            @checked($sort === $value)
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="product-modal__footer">
                <a href="{{ route('products.index', $filterQueryParameters) }}" class="btn btn-outline product-modal__reset">
                    Reset
                </a>

                <button type="submit" class="btn btn-submit product-modal__submit">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    <div
        id="products-index-media-modal"
        class="product-media-modal"
        x-cloak
        x-show="mediaOpen"
        x-transition.opacity
        aria-modal="true"
        role="dialog"
        aria-label="Product media viewer"
    >
        <button
            type="button"
            class="product-media-modal__backdrop"
            aria-label="Close media viewer"
            x-on:click="closeProductMedia()"
        ></button>

        <div class="product-media-modal__panel" x-transition>
            <button
                type="button"
                class="product-media-modal__close"
                aria-label="Close media viewer"
                x-on:click="closeProductMedia()"
            >
                X
            </button>

            <template x-if="mediaItems.length">
                <div class="product-media-modal__frame">
                    <template x-for="(item, index) in mediaItems" :key="item.url">
                        <img
                            x-show="mediaIndex === index"
                            x-transition.opacity
                            :src="item.url"
                            :alt="item.alt || mediaTitle"
                            class="product-media-modal__image"
                        >
                    </template>

                    <div class="product-media-modal__count" x-text="(mediaIndex + 1) + ' of ' + mediaItems.length"></div>
                </div>
            </template>

            <template x-if="! mediaItems.length">
                <div class="product-media-modal__empty">
                    <p x-text="mediaType === 'video' ? 'Video is not available yet.' : 'Image is not available yet.'"></p>
                </div>
            </template>

            <button
                type="button"
                class="product-media-modal__nav product-media-modal__nav--previous"
                aria-label="Previous media"
                x-show="mediaItems.length > 1"
                x-on:click="previousProductMedia()"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>

            <button
                type="button"
                class="product-media-modal__nav product-media-modal__nav--next"
                aria-label="Next media"
                x-show="mediaItems.length > 1"
                x-on:click="nextProductMedia()"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m9 6 6 6-6 6"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

@endsection
