@php
    $section = $sections['home.popular_tour'] ?? null;
@endphp

<section class="bp-popular-tour" id="home-popular-tour" data-section-key="home.popular_tour" aria-labelledby="popular-tour-title">
    <div class="bp-popular-tour__inner">
        <div class="bp-popular-tour__media bp-popular-tour__media--left" aria-hidden="true">
            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--wide">
                <div class="bp-popular-tour__placeholder">No Image</div>
            </div>

            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--small">
                <div class="bp-popular-tour__placeholder">No Image</div>
            </div>
        </div>

        <div class="bp-popular-tour__content">
            <div class="bp-popular-tour__logo-frame">
                LOGO HERE
            </div>

            <span class="bp-popular-tour__label">
                {{ $section?->label ?? 'Most Popular Tour' }}
            </span>

            <h2 id="popular-tour-title" class="bp-popular-tour__title title-section">
                {{ $section?->title ?? "Let's Discover Bintan With Our Excellent Trips" }}
            </h2>

            <p class="bp-popular-tour__text text-muted">
                {{ $section?->description ?? "Whether you're looking for a private island escape, resort transfer, family-friendly activity, or curated Bintan journey, Bintan Prestige provides thoughtfully arranged travel experiences with comfort, quality, and local insight." }}
            </p>

            <a href="{{ $section?->button_url ?: route('products.index') }}" class="btn btn-primary bp-popular-tour__button">
                <span>{{ $section?->button_text ?? 'TAKE A TOUR' }}</span>
                <svg class="bp-popular-tour__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>

        <div class="bp-popular-tour__media bp-popular-tour__media--right" aria-hidden="true">
            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--wide">
                <div class="bp-popular-tour__placeholder">No Image</div>
            </div>

            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--small">
                <div class="bp-popular-tour__placeholder">No Image</div>
            </div>
        </div>
    </div>
</section>
