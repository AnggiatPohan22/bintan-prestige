@extends('layouts.frontend')

@section('content')

@php
    $heroSection = $sections['home.hero'] ?? null;
    $heroSlides = $heroSection?->galleryMedia() ?? collect();
    $heroPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'hero');
    $heroMobilePlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'hero_mobile');
    $sectionPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'section');
    $heroPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'hero');
    $heroMobilePlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'hero_mobile');
    $sectionPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'section');
    $heroBackground = $heroSection?->mediaUrl('background', 'desktop_background') ?? $heroSection?->image_url ?? $heroBackgroundUrl ?? $heroPlaceholder?->url;
    $heroMobileBackground = $heroSection?->mediaUrl('background', 'mobile_background') ?? $heroSection?->mobile_image_url ?? $heroMobilePlaceholder?->url;
    $heroAnimation = $heroSection?->animation ?? 'ken-burns';
    $heroCtaUrl = \App\Support\PageSectionCta::safeUrl($heroSection?->button_url);
    $heroCtaText = $heroSection?->button_text;
    $faqSection = $sections['home.faq'] ?? null;
    $faqVisual = $faqSection?->mediaSlot('frame', 'main_visual');
    $fallbackFaqs = collect([
        [
            'question' => 'Can I arrange pickup from ferry terminal or resort?',
            'answer' => 'Yes, pickup options can be arranged depending on package, meeting point, and route availability.',
        ],
        [
            'question' => 'How do I confirm a booking?',
            'answer' => 'Choose a package and contact us through WhatsApp to confirm date, guests, pickup, and availability.',
        ],
        [
            'question' => 'Can packages be customized?',
            'answer' => 'Many tours and transfers can be adjusted for timing, route, or pickup location.',
        ],
    ]);
    $faqItems = isset($faqs) && $faqs->count() ? $faqs : $fallbackFaqs;
@endphp

<div class="home-page">

    <section
        id="home-hero"
        class="home-hero"
        data-section-key="home.hero"
        data-hero-background="{{ $heroBackground }}"
        data-hero-mobile-background="{{ $heroMobileBackground }}"
        data-hero-background-fit="{{ $heroPlaceholderFit }}"
        data-hero-mobile-background-fit="{{ $heroMobilePlaceholderFit }}"
        data-section-animation="{{ $heroAnimation }}"
        @if($heroSlides->count())
            data-section-slider
            data-section-has-media
        @endif
    >
        @if($heroSlides->count())
            @include('frontend.components.section-media-slider', [
                'mediaItems' => $heroSlides,
                'wrapperClass' => 'home-hero__slider',
                'slideClass' => 'home-hero__slide',
                'imageAlt' => $heroSection?->title ?? 'Bintan Prestige',
            ])
        @endif

        <div class="home-container">
            <div class="home-hero__stage">
                <div class="home-hero__copy">
                    <span class="home-eyebrow">
                        {{ $heroSection?->label ?? 'Luxury Bintan Travel' }}
                    </span>

                    <h1 class="home-hero__title title-hero">
                        {{ $heroSection?->title ?? 'BINTAN PRESTIGE' }}
                    </h1>

                    <p class="home-hero__text text-body">
                        {{ $heroSection?->description ?? 'Private tours, island transfers, and curated experiences designed for a smoother premium escape.' }}
                    </p>

                    @if(\App\Support\PageSectionCta::hasButton($heroCtaText, $heroCtaUrl))
                        <a href="{{ $heroCtaUrl }}" class="btn btn-primary btn-lg">
                            {{ $heroCtaText }}
                        </a>
                    @endif
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

    @include('frontend.sections.popular-tour')

    @include('frontend.sections.popular-products')

    @include('frontend.partials.manual-ads')

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

    @include('frontend.sections.about-journey')

    @include('frontend.sections.categories')

    @include('frontend.sections.explore-banner')

    @include('frontend.sections.testimonials')

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

    <section class="home-faq-preview" id="faq-preview" data-section-key="home.faq">
        <div class="home-container home-faq-preview__grid">
            <div>
                <span class="home-section__kicker">{{ $faqSection?->label ?? 'Before your journey' }}</span>
                <h2 class="home-section__title title-section">
                    {{ $faqSection?->title ?? 'All you should know before embarking on your Bintan journey' }}
                </h2>

                @if($faqVisual?->url || $sectionPlaceholder?->url)
                    <img src="{{ $faqVisual?->url ?: $sectionPlaceholder->url }}" alt="{{ $faqVisual?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" class="home-image-placeholder home-faq-preview__image" style="{{ $faqVisual?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="home-image-placeholder home-faq-preview__image">
                        Travel Guide Image
                    </div>
                @endif
            </div>

            <div class="home-faq-list">
                @foreach($faqItems as $faqIndex => $faq)
                    <details @if($faqIndex === 0) open @endif>
                        <summary>{{ is_array($faq) ? $faq['question'] : $faq->question }}</summary>
                        <p>{{ is_array($faq) ? $faq['answer'] : $faq->answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

</div>

@endsection
