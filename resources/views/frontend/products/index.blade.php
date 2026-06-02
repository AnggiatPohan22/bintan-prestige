@extends('layouts.frontend')

@section('content')

@php
    $selectedDurations = (array) request('duration', []);
    $selectedDestinations = (array) request('destination', []);
    $selectedCategories = (array) request('category', []);
    $selectedVehicleTypes = (array) request('vehicle_type', []);
@endphp

<div
    class="product-page"
    x-data="{ filterOpen: false, sortOpen: false }"
    x-on:keydown.escape.window="filterOpen = false; sortOpen = false"
>
    <section class="product-hero">
        <div class="product-hero__container">

            <div class="product-hero__content">
                <span class="product-hero__eyebrow">
                    Bintan Travel Experience
                </span>

                <h1 class="product-hero__title title-section">
                    Explore Tours, Taxi & Activities in Bintan
                </h1>

                <p class="product-hero__description text-body">
                    Choose curated island tours, private transfers, and activities with easy WhatsApp booking support.
                </p>
            </div>

            <div class="product-hero__soft-card">
                <p class="product-hero__soft-title">
                    Travel made simple
                </p>

                <p class="product-hero__soft-text">
                    Local team, flexible pickup, and packages prepared for guests who want a smooth Bintan trip.
                </p>
            </div>

        </div>
    </section>

    <section class="product-section">
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
                        Available Products
                    </h2>

                    <p class="product-toolbar__text text-muted">
                        {{ $products->total() }} packages available for your next Bintan experience.
                    </p>
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

        </div>
    </section>

    <div
        class="product-modal"
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
                        Rentang Harga
                    </h4>

                    <div class="product-filter__price-grid">
                        <label class="product-field">
                            <span class="product-field__label">Minimum</span>
                            <input
                                type="number"
                                name="min_price"
                                value="{{ request('min_price') }}"
                                placeholder="{{ $priceRange['min'] ? number_format($priceRange['min'], 0, ',', '.') : '0' }}"
                                class="product-field__input"
                            >
                        </label>

                        <label class="product-field">
                            <span class="product-field__label">Maximum</span>
                            <input
                                type="number"
                                name="max_price"
                                value="{{ request('max_price') }}"
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
        class="product-modal"
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
            @if(request('min_price'))
                <input type="hidden" name="min_price" value="{{ request('min_price') }}">
            @endif

            @if(request('max_price'))
                <input type="hidden" name="max_price" value="{{ request('max_price') }}">
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
                <a href="{{ route('products.index', request()->except('sort', 'page')) }}" class="btn btn-outline product-modal__reset">
                    Reset
                </a>

                <button type="submit" class="btn btn-submit product-modal__submit">
                    Terapkan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
