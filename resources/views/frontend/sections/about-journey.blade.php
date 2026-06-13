@php
    $section = $sections['home.about_journey'] ?? null;
    $mainVisual = $section?->mediaSlot('frame', 'main_visual');
    $secondaryVisual = $section?->mediaSlot('frame', 'secondary_visual');
    $sectionPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'section');
    $sectionPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'section');
    $buttonUrl = \App\Support\PageSectionCta::safeUrl($section?->button_url, route('products.index'));
@endphp

<section class="bp-journey-section" id="home-about-journey" data-section-key="home.about_journey" aria-labelledby="journey-title">
    <div class="home-container bp-journey-section__grid">
        <div class="bp-journey-content">
            <span class="bp-journey-label">
                {{ $section?->label ?? 'Dream Your Next Trip' }}
            </span>

            <h2 id="journey-title" class="bp-journey-title title-section">
                {{ $section?->title ?? 'Discover When Even You Want To Go' }}
            </h2>

            <p class="bp-journey-text text-muted">
                {{ $section?->description ?? 'Are you tired of the typical tourist destinations and looking to step out of your comfort zone? Adventure travel may be the perfect solution for you! Here are four.' }}
            </p>

            <div class="bp-journey-features">
                <article class="bp-journey-feature">
                    <span class="bp-journey-feature__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3l7 4v5c0 4.3-2.9 8.3-7 9.4-4.1-1.1-7-5.1-7-9.4V7l7-4z"></path>
                            <path d="M9 12l2 2 4-5"></path>
                        </svg>
                    </span>

                    <div>
                        <h3 class="bp-journey-feature__title title-card">
                            Best Travel Agency
                        </h3>

                        <p class="bp-journey-feature__text text-muted">
                            Thoughtfully arranged Bintan travel experiences for guests who want comfort, quality, and reliable service.
                        </p>
                    </div>
                </article>

                <article class="bp-journey-feature">
                    <span class="bp-journey-feature__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 12a8 8 0 0 1 16 0"></path>
                            <path d="M6 12v5a2 2 0 0 0 2 2h2"></path>
                            <path d="M18 12v5a2 2 0 0 1-2 2h-2"></path>
                            <path d="M10 19h4"></path>
                        </svg>
                    </span>

                    <div>
                        <h3 class="bp-journey-feature__title title-card">
                            Secure Journey With Us
                        </h3>

                        <p class="bp-journey-feature__text text-muted">
                            Travel with confidence through organized transfers, curated tours, and clear guest support.
                        </p>
                    </div>
                </article>
            </div>

            <a href="{{ $buttonUrl }}" class="btn btn-primary btn-lg bp-journey-cta">
                {{ $section?->button_text ?? 'BOOK YOUR TRIP' }}
                <svg class="bp-journey-cta__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>

        <div class="bp-journey-visual" aria-label="Bintan Prestige journey visuals">
            <span class="bp-journey-compass" aria-hidden="true">
                <svg viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="42"></circle>
                    <path d="M60 8v18"></path>
                    <path d="M60 94v18"></path>
                    <path d="M8 60h18"></path>
                    <path d="M94 60h18"></path>
                    <path d="M72 48 54 72l-6-24 24 6z"></path>
                </svg>
            </span>

            <span class="bp-journey-vertical-text" aria-hidden="true">
                TRAVEL
            </span>

            <div class="bp-journey-main-frame">
                @if($mainVisual?->url || $sectionPlaceholder?->url)
                    <img src="{{ $mainVisual?->url ?: $sectionPlaceholder->url }}" alt="{{ $mainVisual?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $mainVisual?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-journey-placeholder">
                        NO IMAGE
                    </div>
                @endif
            </div>

            <div class="bp-journey-small-frame">
                @if($secondaryVisual?->url || $sectionPlaceholder?->url)
                    <img src="{{ $secondaryVisual?->url ?: $sectionPlaceholder->url }}" alt="{{ $secondaryVisual?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $secondaryVisual?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-journey-placeholder">
                        NO IMAGE
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
