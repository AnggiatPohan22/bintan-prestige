@extends('layouts.frontend')

@section('content')

<div class="home-page">

    @include('frontend.home.hero')

    <section class="home-search" aria-label="Search and filter packages">
        <div class="home-container">
            <div class="home-search__panel">
                <form method="GET" action="{{ route('products.index') }}" class="home-search__form">
                    <label class="home-field">
                        <span class="home-field__label">Destination</span>
                        <select name="destination[]" class="home-field__control">
                            <option value="">All Destinations</option>
                            @foreach($destinations as $destination)
                                <option value="{{ $destination->id }}">
                                    {{ $destination->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="home-field">
                        <span class="home-field__label">Package Type</span>
                        <select name="category[]" class="home-field__control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit" class="btn btn-submit home-button home-button--dark home-button--search">
                        Find Packages
                    </button>
                </form>

                <p class="home-hero__softcopy text-muted">
                    Discover premium Bintan packages with local assistance, flexible pickup, and simple WhatsApp booking.
                </p>
            </div>
        </div>
    </section>

    @include('frontend.home.popular-tour')

    @include('frontend.home.popular-products')

    {{-- Legacy package carousel replaced by the centered editorial popular-tour section.
    <section class="home-section home-section--legacy-featured" hidden>
        <div class="home-container">

            @if($featuredProducts->count())
                <div class="package-carousel" data-package-carousel>
                    <div class="package-carousel-track" data-carousel-track>
                    @foreach($featuredProducts as $product)
                        <article class="package-carousel-card home-feature-card">
                            <a href="{{ route('products.show', $product) }}" class="home-feature-card__media">
                                @if($product->thumbnail_url)
                                    <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="home-feature-card__image" loading="lazy" decoding="async">
                                @else
                                    <span class="home-image-placeholder">Package Image</span>
                                @endif
                            </a>

                            <div class="package-card-overlay home-feature-card__body">
                                <div class="package-card-content">
                                <span class="home-feature-card__tag">
                                    {{ $product->category?->name ?: 'Bintan Package' }}
                                </span>

                                <h3 class="home-feature-card__title title-card">
                                    {{ $product->name }}
                                </h3>

                                <p class="home-feature-card__text">
                                    {{ $product->destination?->name ?: 'Bintan' }} · {{ $product->duration ?: 'Flexible duration' }}
                                </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                    </div>
                </div>
            @else
                <div class="product-empty">
                    <h3 class="product-empty__title">
                        Featured products coming soon
                    </h3>

                    <p class="product-empty__text">
                        Published featured packages will appear here.
                    </p>
                </div>
            @endif
        </div>
    </section>
    --}}

    @include('frontend.home.categories')

    @include('frontend.partials.manual-ads')

    @include('frontend.home.about-journey')

    @include('frontend.home.explore-banner')

    @include('frontend.home.testimonials')

    <section class="home-section" id="why-choose-us">
        <div class="home-container">
            <div class="home-section__header home-section__header--center">
                <div>
                    <span class="home-section__kicker">Why choose us</span>
                    <h2 class="home-section__title title-section">
                        Designed for guests who value comfort.
                    </h2>
                </div>
            </div>

            <div class="home-why-grid">
                <div class="home-why-card">
                    <span class="home-why-card__number">01</span>
                    <h3 class="home-why-card__title title-card">Curated Packages</h3>
                    <p class="home-why-card__text">
                        Selected tours and transfers with clean details, practical routes, and guest-friendly schedules.
                    </p>
                </div>

                <div class="home-why-card">
                    <span class="home-why-card__number">02</span>
                    <h3 class="home-why-card__title title-card">Flexible Pickup</h3>
                    <p class="home-why-card__text">
                        Travel from hotels, ferry terminals, resorts, and meeting points with convenient pickup options.
                    </p>
                </div>

                <div class="home-why-card">
                    <span class="home-why-card__number">03</span>
                    <h3 class="home-why-card__title title-card">Fast WhatsApp Booking</h3>
                    <p class="home-why-card__text">
                        Ask questions, confirm availability, and arrange bookings quickly through WhatsApp.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.home.faq')

</div>

@endsection
