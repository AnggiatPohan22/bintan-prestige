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
    @unless($entityContext ?? null)
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
    @endunless

    <section id="products-index-catalog" class="product-section" data-section-key="products.index.catalog">
        <div class="product-container">

            @if($entityContext ?? null)
                @include('frontend.products.partials.entity-context', [
                    'entityContext' => $entityContext,
                ])
            @else
                <nav class="product-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="product-breadcrumb__link">
                        Home
                    </a>
                    <span class="product-breadcrumb__separator" aria-hidden="true">/</span>
                    <span class="product-breadcrumb__current">
                        Products
                    </span>
                </nav>
            @endif

            <div class="product-toolbar">
                <div>
                    <h2 class="product-toolbar__title title-card">
                        {{ $listingCatalog['title'] }}
                    </h2>

                    <p class="product-toolbar__text text-muted">
                        {{ $resultSummary }} for your next Bintan experience.
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
                        x-bind:aria-expanded="filterOpen.toString()"
                        aria-controls="products-index-filter-modal"
                        aria-label="Open product listing filters"
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
                        x-bind:aria-expanded="sortOpen.toString()"
                        aria-controls="products-index-sort-modal"
                        aria-label="Open product listing sorting options"
                    >
                        Urutkan
                        <span class="product-action-button__label">
                            {{ $sortOptions[$sort] ?? 'Tour Terbaru' }}
                        </span>
                    </button>
                </div>
            </div>

            <section class="product-discovery" aria-labelledby="product-discovery-title">
                <div>
                    <p class="product-discovery__eyebrow">
                        Discovery controls
                    </p>

                    <h3 id="product-discovery-title" class="product-discovery__title">
                        Filter by destination, category, duration, vehicle, and {{ $priceCurrency }} price.
                    </h3>
                </div>

                <div class="product-discovery__meta" aria-label="Current listing state">
                    <span>{{ $resultSummary }}</span>
                    <span>{{ $sortOptions[$sort] ?? 'Tour Terbaru' }}</span>
                    <span>Price context: {{ $priceCurrency }}</span>
                </div>
            </section>

            @if($activeFilterSummary || $hasInvalidFilter)
                <section class="product-active-filters" aria-labelledby="product-active-filters-title">
                    <div class="product-active-filters__header">
                        <div>
                            <p class="product-active-filters__eyebrow">
                                Active listing state
                            </p>

                            <h3 id="product-active-filters-title" class="product-active-filters__title">
                                Current filters
                            </h3>
                        </div>

                        <a href="{{ $resetListingUrl }}" class="product-active-filters__clear">
                            Clear all
                        </a>
                    </div>

                    @if($activeFilterSummary)
                        <ul class="product-active-filters__list" aria-label="Active filters">
                            @foreach($activeFilterSummary as $filter)
                                <li class="product-active-filter">
                                    <span class="product-active-filter__label">{{ $filter['label'] }}</span>
                                    <strong>{{ $filter['value'] }}</strong>
                                    <a
                                        href="{{ $filter['remove_url'] }}"
                                        class="product-active-filter__remove"
                                        aria-label="{{ $filter['remove_label'] }}: {{ $filter['value'] }}"
                                    >
                                        Remove
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($hasInvalidFilter)
                        <p class="product-active-filters__notice">
                            Some query values were ignored because they are not available filter options.
                        </p>
                    @endif
                </section>
            @endif

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
                    @if($products->firstItem() && $products->lastItem())
                        <p class="product-pagination__summary">
                            Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} packages.
                        </p>
                    @endif

                    {{ $products->links('frontend.components.product-pagination-links') }}
                </div>

            @else

                <section class="product-empty" aria-labelledby="product-empty-title">
                    <h3 id="product-empty-title" class="product-empty__title">
                        {{ $emptyState['title'] }}
                    </h3>

                    <p class="product-empty__text">
                        {{ $emptyState['description'] }}
                    </p>

                    <a href="{{ $emptyState['action_url'] ?? $resetListingUrl }}" class="btn btn-submit product-empty__action">
                        {{ $emptyState['action'] }}
                    </a>
                </section>

            @endif

            @if(! empty($listingCatalog['has_cta']))
                <section
                    class="product-empty product-final-cta"
                    @if(! empty($listingCatalog['subtitle'])) aria-labelledby="product-final-cta-title" @endif
                >
                    @if(! empty($listingCatalog['subtitle']))
                        <h3 id="product-final-cta-title" class="product-empty__title">
                            {{ $listingCatalog['subtitle'] }}
                        </h3>
                    @endif

                    <a href="{{ $listingCatalog['cta_url'] }}" class="btn btn-submit">
                        {{ $listingCatalog['cta_text'] }}
                    </a>
                </section>
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
        aria-labelledby="products-index-filter-title"
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
            aria-labelledby="products-index-filter-title"
            x-transition
        >
            <input type="hidden" name="sort" value="{{ $sort }}">

            @if(($entityContext['entity']['type'] ?? null) === 'category')
                <input type="hidden" name="category[]" value="{{ $entityContext['entity']['id'] }}">
            @endif

            @if(($entityContext['entity']['type'] ?? null) === 'destination')
                <input type="hidden" name="destination[]" value="{{ $entityContext['entity']['id'] }}">
            @endif

            <div class="product-modal__header">
                <div>
                    <p class="product-modal__eyebrow">
                        Refine packages
                    </p>

                    <h3 id="products-index-filter-title" class="product-modal__title title-card">
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
                <fieldset class="product-filter__group">
                    <legend class="product-filter__title">
                        Rentang Harga {{ $priceCurrency }}
                    </legend>

                    <div class="product-filter__price-grid">
                        <label class="product-field" for="product-filter-min-price">
                            <span class="product-field__label">Minimum</span>
                            <input
                                id="product-filter-min-price"
                                type="number"
                                name="min_price"
                                value="{{ $minPrice ?? '' }}"
                                placeholder="{{ $priceRange['min'] ? number_format($priceRange['min'], 0, ',', '.') : '0' }}"
                                inputmode="numeric"
                                min="0"
                                class="product-field__input"
                            >
                        </label>

                        <label class="product-field" for="product-filter-max-price">
                            <span class="product-field__label">Maximum</span>
                            <input
                                id="product-filter-max-price"
                                type="number"
                                name="max_price"
                                value="{{ $maxPrice ?? '' }}"
                                placeholder="{{ $priceRange['max'] ? number_format($priceRange['max'], 0, ',', '.') : '0' }}"
                                inputmode="numeric"
                                min="0"
                                class="product-field__input"
                            >
                        </label>
                    </div>
                </fieldset>

                <fieldset class="product-filter__group">
                    <legend class="product-filter__title">
                        Durasi
                    </legend>

                    <div class="product-filter__options">
                        @forelse($durations as $duration)
                            @php($durationId = 'product-filter-duration-' . md5($duration))
                            <label class="product-check" for="{{ $durationId }}">
                                <input
                                    id="{{ $durationId }}"
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
                </fieldset>

                @if(($entityContext['entity']['type'] ?? null) !== 'destination')
                    <fieldset class="product-filter__group">
                        <legend class="product-filter__title">
                            Destinations
                        </legend>

                        <div class="product-filter__options">
                            @foreach($destinations as $destination)
                                <label class="product-check" for="product-filter-destination-{{ $destination->id }}">
                                    <input
                                        id="product-filter-destination-{{ $destination->id }}"
                                        type="checkbox"
                                        name="destination[]"
                                        value="{{ $destination->id }}"
                                        @checked(in_array((string) $destination->id, $selectedDestinations))
                                    >
                                    <span>{{ $destination->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                @if(($entityContext['entity']['type'] ?? null) !== 'category')
                    <fieldset class="product-filter__group">
                        <legend class="product-filter__title">
                            Jenis Tour
                        </legend>

                        <div class="product-filter__options">
                            @foreach($categories as $category)
                                <label class="product-check" for="product-filter-category-{{ $category->id }}">
                                    <input
                                        id="product-filter-category-{{ $category->id }}"
                                        type="checkbox"
                                        name="category[]"
                                        value="{{ $category->id }}"
                                        @checked(in_array((string) $category->id, $selectedCategories))
                                    >
                                    <span>{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset class="product-filter__group">
                    <legend class="product-filter__title">
                        Jenis Mobil
                    </legend>

                    <div class="product-filter__options">
                        @forelse($vehicleTypes as $vehicleType)
                            @php($vehicleTypeId = 'product-filter-vehicle-' . md5($vehicleType))
                            <label class="product-check" for="{{ $vehicleTypeId }}">
                                <input
                                    id="{{ $vehicleTypeId }}"
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
                </fieldset>
            </div>

            <div class="product-modal__footer">
                <a href="{{ $resetListingUrl }}" class="btn btn-outline product-modal__reset">
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
        aria-labelledby="products-index-sort-title"
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
            aria-labelledby="products-index-sort-title"
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

                    <h3 id="products-index-sort-title" class="product-modal__title title-card">
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

            <fieldset class="product-sort-list">
                <legend class="sr-only">Sort product listing</legend>

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
            </fieldset>

            <div class="product-modal__footer">
                <a
                    href="{{ route('products.index', $filterQueryParameters) }}"
                    class="btn btn-outline product-modal__reset"
                    aria-label="Reset sorting to newest while keeping filters"
                >
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
