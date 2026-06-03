@extends('layouts.frontend')

@section('content')

<div class="home-page">

    @php
        $heroSlides = collect($heroSlides ?? [])->filter(fn ($slide) => !empty($slide['url']))->take(10)->values();
        $heroSettings = $heroSettings ?? [];
        $heroSectionId = $heroSettings['section_id'] ?? 'frontend-hero-section';
        $heroMediaId = $heroSettings['media_id'] ?? 'frontend-hero-media';
    @endphp

    <section
        id="{{ $heroSectionId }}"
        class="home-hero"
        data-hero-slider
        data-admin-setting-key="homepage_hero"
        data-hero-background="{{ $heroBackgroundUrl }}"
        data-hero-animation="{{ $heroSettings['animation'] ?? 'ken-burns' }}"
        data-hero-duration="{{ $heroSettings['slide_duration'] ?? 6500 }}"
        data-hero-fit="{{ $heroSettings['image_fit'] ?? 'cover' }}"
        data-hero-position="{{ $heroSettings['image_position'] ?? 'center center' }}"
        data-hero-overlay="{{ $heroSettings['overlay_opacity'] ?? 0.72 }}"
        data-hero-align="{{ $heroSettings['text_alignment'] ?? 'left' }}"
        data-hero-min-slides="2"
        data-hero-max-slides="10"
    >
        <div id="{{ $heroMediaId }}" class="home-hero__media" aria-hidden="true">
            @forelse($heroSlides as $index => $slide)
                <img
                    id="frontend-hero-slide-{{ $index + 1 }}"
                    class="home-hero__slide {{ $index === 0 ? 'is-active' : '' }}"
                    src="{{ $slide['url'] }}"
                    alt="{{ $slide['alt'] ?? '' }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                    data-hero-slide
                    data-slide-index="{{ $index }}"
                    data-slide-position="{{ $slide['position'] ?? ($heroSettings['image_position'] ?? 'center center') }}"
                >
            @empty
                @if(!empty($heroBackgroundUrl))
                    <img
                        id="frontend-hero-slide-1"
                        class="home-hero__slide is-active"
                        src="{{ $heroBackgroundUrl }}"
                        alt="{{ $heroSettings['title'] ?? 'Bintan Prestige' }}"
                        loading="eager"
                        decoding="async"
                        data-hero-slide
                        data-slide-index="0"
                        data-slide-position="{{ $heroSettings['image_position'] ?? 'center center' }}"
                    >
                @else
                    <div id="frontend-hero-placeholder" class="home-hero__placeholder">
                        NO IMAGE
                    </div>
                @endif
            @endforelse
        </div>

        <div class="home-hero__overlay" aria-hidden="true"></div>

        <div class="home-container">
            <div class="home-hero__stage">
                <div class="home-hero__copy">
                    <span class="home-eyebrow">
                        {{ $heroSettings['label'] ?? 'Luxury Bintan Travel' }}
                    </span>

                    <h1 class="home-hero__title title-hero">
                        {{ $heroSettings['title'] ?? 'BINTAN PRESTIGE' }}
                    </h1>

                    <p class="home-hero__text text-body">
                        {{ $heroSettings['subtitle'] ?? 'Private tours, island transfers, and curated experiences designed for a smoother premium escape.' }}
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section class="home-search" aria-label="Search and filter packages">
        <div class="home-container">
            <div class="home-search__panel">
                <form method="GET" action="{{ route('products.index') }}" class="home-search__form" data-home-search>
                    <div class="home-search-tabs" role="tablist" aria-label="Package type">
                        <span class="home-search-tabs__indicator" data-home-search-indicator aria-hidden="true"></span>

                        @foreach(['taxi' => 'Taxi', 'hotel' => 'Hotel', 'activity' => 'Activity'] as $typeValue => $typeLabel)
                            @php
                                $matchedCategory = $categories->first(fn ($category) => str_contains(strtolower($category->name), $typeValue));
                            @endphp

                            <button
                                type="button"
                                class="home-search-tabs__button {{ $loop->first ? 'is-active' : '' }}"
                                data-home-search-tab
                                data-category-value="{{ $matchedCategory?->id }}"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <span class="home-search-tabs__icon" aria-hidden="true">
                                    @if($typeValue === 'taxi')
                                        <svg viewBox="0 0 24 24"><path d="M5 11l1.5-4h11L19 11"></path><path d="M4 11h16v7H4z"></path><path d="M7 18v2"></path><path d="M17 18v2"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path></svg>
                                    @elseif($typeValue === 'hotel')
                                        <svg viewBox="0 0 24 24"><path d="M4 20V8"></path><path d="M20 20V10"></path><path d="M4 14h16"></path><path d="M7 14v-4h5v4"></path><path d="M20 10H4"></path></svg>
                                    @else
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"></circle><path d="m14.5 9.5-3 6-2-2 6-3z"></path></svg>
                                    @endif
                                </span>
                                {{ $typeLabel }}
                            </button>
                        @endforeach
                    </div>

                    <input type="hidden" name="category[]" value="" data-home-search-category>

                    <label class="home-field home-field--destination">
                        <span class="home-field__label">Destination:</span>
                        <select name="destination[]" class="home-field__control">
                            <option value="">Select City</option>
                            @foreach($destinations as $destination)
                                <option value="{{ $destination->id }}">
                                    {{ $destination->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit" class="btn btn-submit home-button home-button--search">
                        Find Package
                        <svg class="home-search__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 17L17 7"></path>
                            <path d="M9 7h8v8"></path>
                        </svg>
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

</div>

@endsection
