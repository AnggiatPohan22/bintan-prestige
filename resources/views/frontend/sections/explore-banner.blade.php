@php
    $section = $sections['home.explore_banner'] ?? null;
    $extraData = $section?->extra_data ?? [];
    $background = $section?->mediaSlot('background', 'desktop_background');
@endphp

<section class="bp-explore-banner" id="home-explore-banner" data-section-key="home.explore_banner" aria-labelledby="explore-banner-title">
    <div class="bp-explore-banner__bg" aria-hidden="true">
        @if($background?->url || $section?->image_url)
            <img src="{{ $background?->url ?? $section->image_url }}" alt="{{ $background?->alt ?: ($section->title ?? 'Explore banner image') }}" loading="lazy" decoding="async">
        @else
            <div class="bp-explore-banner__placeholder">
                NO IMAGE
            </div>
        @endif
    </div>

    <div class="bp-explore-banner__overlay" aria-hidden="true"></div>

    <div class="home-container bp-explore-banner__content">
        <span class="bp-explore-banner__label">
            {{ $section?->label ?? 'Next Adventure Destination' }}
        </span>

        <h2 id="explore-banner-title" class="bp-explore-banner__title title-section">
            {{ $section?->title ?? 'Popular Travel Destinations Available Worldwide' }}
        </h2>

        <a href="{{ $section?->button_url ?: route('products.index') }}" class="btn btn-primary btn-lg bp-explore-banner__cta">
            {{ $section?->button_text ?? 'BOOK YOUR TRIP NOW' }}
            <svg class="bp-explore-banner__cta-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 17L17 7"></path>
                <path d="M9 7h8v8"></path>
            </svg>
        </a>
    </div>

    <div class="bp-explore-banner__outline-text" aria-hidden="true">
        {{ $extraData['outline_text'] ?? 'EXPLORE THE WORLD' }}
    </div>
</section>
