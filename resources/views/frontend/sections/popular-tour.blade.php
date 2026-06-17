@php
    $homepageContent = $homepageContent ?? \App\Support\HomepageContent::fromSections(collect($sections ?? []));
    $sectionContent = ($homepageContent ?? [])['sections']['home.popular_tour'] ?? [];
    $section = $sectionContent['model'] ?? null;
    $siteLogo = $siteAssets['site.logo'] ?? null;
    $sectionPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'section');
    $sectionPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'section');
    $buttonUrl = $sectionContent['button_url'] ?? route('products.index');
    $popularTourMedia = [
        'left_wide' => $section?->mediaSlot('frame', 'left_wide'),
        'left_small' => $section?->mediaSlot('frame', 'left_small'),
        'right_wide' => $section?->mediaSlot('frame', 'right_wide'),
        'right_small' => $section?->mediaSlot('frame', 'right_small'),
    ];
@endphp

<section class="bp-popular-tour" id="home-popular-tour" data-section-key="home.popular_tour" aria-labelledby="popular-tour-title">
    <div class="bp-popular-tour__inner">
        <div class="bp-popular-tour__media bp-popular-tour__media--left" aria-hidden="true">
            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--wide">
                @if($popularTourMedia['left_wide']?->url || $sectionPlaceholder?->url)
                    <img src="{{ $popularTourMedia['left_wide']?->url ?: $sectionPlaceholder->url }}" alt="{{ $popularTourMedia['left_wide']?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $popularTourMedia['left_wide']?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-popular-tour__placeholder">No Image</div>
                @endif
            </div>

            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--small">
                @if($popularTourMedia['left_small']?->url || $sectionPlaceholder?->url)
                    <img src="{{ $popularTourMedia['left_small']?->url ?: $sectionPlaceholder->url }}" alt="{{ $popularTourMedia['left_small']?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $popularTourMedia['left_small']?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-popular-tour__placeholder">No Image</div>
                @endif
            </div>
        </div>

        <div class="bp-popular-tour__content">
            <div class="bp-popular-tour__logo-frame">
                @if($siteLogo?->url)
                    <img src="{{ $siteLogo->url }}" alt="{{ $siteLogo->alt ?: 'Bintan Prestige logo' }}" loading="lazy" decoding="async">
                @else
                    LOGO HERE
                @endif
            </div>

            <span class="bp-popular-tour__label">
                {{ $sectionContent['label'] ?? 'Most Popular Tour' }}
            </span>

            <h2 id="popular-tour-title" class="bp-popular-tour__title title-section">
                {{ $sectionContent['title'] ?? "Let's Discover Bintan With Our Excellent Trips" }}
            </h2>

            <p class="bp-popular-tour__text text-muted">
                {{ $sectionContent['description'] ?? "Whether you're looking for a private island escape, resort transfer, family-friendly activity, or curated Bintan journey, Bintan Prestige provides thoughtfully arranged travel experiences with comfort, quality, and local insight." }}
            </p>

            <a href="{{ $buttonUrl }}" class="btn btn-primary bp-popular-tour__button">
                <span>{{ $sectionContent['button_text'] ?? 'TAKE A TOUR' }}</span>
                <svg class="bp-popular-tour__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>

        <div class="bp-popular-tour__media bp-popular-tour__media--right" aria-hidden="true">
            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--wide">
                @if($popularTourMedia['right_wide']?->url || $sectionPlaceholder?->url)
                    <img src="{{ $popularTourMedia['right_wide']?->url ?: $sectionPlaceholder->url }}" alt="{{ $popularTourMedia['right_wide']?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $popularTourMedia['right_wide']?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-popular-tour__placeholder">No Image</div>
                @endif
            </div>

            <div class="bp-popular-tour__media-frame bp-popular-tour__media-frame--small">
                @if($popularTourMedia['right_small']?->url || $sectionPlaceholder?->url)
                    <img src="{{ $popularTourMedia['right_small']?->url ?: $sectionPlaceholder->url }}" alt="{{ $popularTourMedia['right_small']?->alt ?: ($sectionPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $popularTourMedia['right_small']?->image_style ?? 'object-fit: ' . $sectionPlaceholderFit }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-popular-tour__placeholder">No Image</div>
                @endif
            </div>
        </div>
    </div>
</section>
