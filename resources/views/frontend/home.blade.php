@extends('layouts.frontend')

@section('content')

<div class="home-page">

    <section
        class="home-hero"
        data-hero-background="{{ $heroBackgroundUrl }}"
    >
        <div class="home-container">
            <div class="home-hero__stage">
                <div class="home-hero__copy">
                    <span class="home-eyebrow">
                        Luxury Bintan Travel
                    </span>

                    <h1 class="home-hero__title title-hero">
                        BINTAN PRESTIGE
                    </h1>

                    <p class="home-hero__text text-body">
                        Private tours, island transfers, and curated experiences designed for a smoother premium escape.
                    </p>
                </div>

            </div>
        </div>
    </section>

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

                    <button type="submit" class="home-button home-button--dark home-button--search">
                        Find Packages
                    </button>
                </form>

                <p class="home-hero__softcopy text-muted">
                    Discover premium Bintan packages with local assistance, flexible pickup, and simple WhatsApp booking.
                </p>
            </div>
        </div>
    </section>

    <section class="home-section">
        <div class="home-container">
            <div class="home-section__header">
                <div>
                    <span class="home-section__kicker">Curated stays and tours</span>
                    <h2 class="home-section__title title-section">
                        Your journey to a refined Bintan escape begins here
                    </h2>
                </div>

                <a href="{{ route('products.index') }}" class="home-section__link">
                    View all packages
                </a>
            </div>

            @if($featuredProducts->count())
                <div class="home-featured-row">
                    @foreach($featuredProducts->take(3) as $product)
                        <article class="home-feature-card">
                            <a href="{{ route('products.show', $product) }}" class="home-feature-card__media">
                                @if($product->thumbnail_url)
                                    <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="home-feature-card__image" loading="lazy">
                                @else
                                    <span class="home-image-placeholder">Package Image</span>
                                @endif
                            </a>

                            <div class="home-feature-card__body">
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
                        </article>
                    @endforeach
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

    <section class="home-story">
        <div class="home-container home-story__grid">
            <div>
                <span class="home-section__kicker">Bintan Prestige</span>
                <h2 class="home-section__title title-section">
                    Travel feels better when every detail is arranged.
                </h2>

                <p class="home-section__text text-muted">
                    From ferry terminal pickup to resort transfers and private sightseeing, we help guests move through Bintan with confidence and a more polished travel rhythm.
                </p>

                <a href="{{ route('products.index') }}" class="home-button home-button--dark">
                    Discover Packages
                </a>
            </div>

            <div class="home-story__gallery">
                <div class="home-image-placeholder home-story__image home-story__image--large">Island View</div>
                <div class="home-image-placeholder home-story__image">Resort Transfer</div>
                <div class="home-image-placeholder home-story__image">Coastal Tour</div>
            </div>
        </div>
    </section>

    <section class="home-section" id="categories">
        <div class="home-container">
            <div class="home-section__header">
                <div>
                    <span class="home-section__kicker">Browse by style</span>
                    <h2 class="home-section__title title-section">
                        Categories
                    </h2>
                </div>
            </div>

            <div class="home-category-grid">
                @foreach($categories->take(4) as $category)
                    <a href="{{ route('products.index', ['category' => [$category->id]]) }}" class="home-mini-card">
                        <div class="home-mini-card__image">
                            Category Image
                        </div>

                        <h3 class="home-mini-card__title title-card">
                            {{ $category->name }}
                        </h3>

                        <p class="home-mini-card__text">
                            {{ $category->products_count }} packages available
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-stats">
        <div class="home-container">
            <div class="home-stats__intro">
                <span class="home-section__kicker">Our promise</span>
                <h2 class="home-stats__title title-section">
                    Premium island travel, arranged with consistency.
                </h2>
            </div>

            <div class="home-orbit">
                <span>Private pickup</span>
                <span>Local planning</span>
                <span>Flexible tours</span>
                <span>WhatsApp booking</span>
                <div class="home-orbit__globe"></div>
            </div>

            <div class="home-stats__grid">
                <div class="home-stat-card">
                    <strong>300+</strong>
                    <span>guest arrangements supported</span>
                </div>

                <div class="home-stat-card">
                    <strong>12k+</strong>
                    <span>travel moments planned</span>
                </div>

                <div class="home-stat-card">
                    <strong>100%</strong>
                    <span>direct WhatsApp assistance</span>
                </div>
            </div>
        </div>
    </section>

    <section class="home-section" id="destinations">
        <div class="home-container">
            <div class="home-section__header">
                <div>
                    <span class="home-section__kicker">Places to explore</span>
                    <h2 class="home-section__title title-section">
                        Destinations
                    </h2>
                </div>
            </div>

            <div class="home-destination-grid">
                @foreach($destinations->take(6) as $destination)
                    <a href="{{ route('products.index', ['destination' => [$destination->id]]) }}" class="home-mini-card home-mini-card--destination">
                        <div class="home-mini-card__image">
                            Destination Image
                        </div>

                        <h3 class="home-mini-card__title title-card">
                            {{ $destination->name }}
                        </h3>

                        <p class="home-mini-card__text">
                            {{ $destination->products_count }} packages available
                        </p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

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

    <section class="home-faq-preview" id="faq-preview">
        <div class="home-container home-faq-preview__grid">
            <div>
                <span class="home-section__kicker">Before your journey</span>
                <h2 class="home-section__title title-section">
                    All you should know before embarking on your Bintan journey
                </h2>

                <div class="home-image-placeholder home-faq-preview__image">
                    Travel Guide Image
                </div>
            </div>

            <div class="home-faq-list">
                <details open>
                    <summary>Can I arrange pickup from ferry terminal or resort?</summary>
                    <p>Yes, pickup options can be arranged depending on package, meeting point, and route availability.</p>
                </details>

                <details>
                    <summary>How do I confirm a booking?</summary>
                    <p>Choose a package and contact us through WhatsApp to confirm date, guests, pickup, and availability.</p>
                </details>

                <details>
                    <summary>Can packages be customized?</summary>
                    <p>Many tours and transfers can be adjusted for timing, route, or pickup location.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="home-whatsapp" id="whatsapp-cta">
        <div class="home-container">
            <div class="home-whatsapp__panel">
                <div>
                    <span class="home-section__kicker">Start planning</span>
                    <h2 class="home-whatsapp__title title-section">
                        Plan your perfect Bintan escape today.
                    </h2>

                    <p class="home-whatsapp__text text-body">
                        Tell us your arrival point, travel date, and preferred experience. Our team will help you choose the right package.
                    </p>
                </div>

                <div class="home-whatsapp__action">
                    <a
                        href="https://wa.me/?text={{ urlencode('Hello Bintan Prestige, I want to plan a Bintan trip.') }}"
                        target="_blank"
                        class="home-button home-button--primary"
                    >
                        Chat via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection
