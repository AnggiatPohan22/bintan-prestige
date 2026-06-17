@php
    $homepageContent = $homepageContent ?? \App\Support\HomepageContent::fromSections(collect($sections ?? []));
    $sectionContent = ($homepageContent ?? [])['sections']['home.manual_ads'] ?? [];
    $section = $sectionContent['model'] ?? null;
    $extraData = $sectionContent['extra'] ?? [];
    $buttonUrl = $sectionContent['button_url'] ?? route('products.index');
    $overlayTitle = $extraData['overlay_title'] ?? "Let's Discover The Whole World!";
    $mainVisual = $section?->mediaSlot('frame', 'main_visual');
    $sectionPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'section');
    $sectionPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'section');
@endphp

<section class="bp-manual-ads" id="home-manual-ads" data-section-key="home.manual_ads">
    <div class="home-container">
        <div class="bp-manual-ads__inner">
            <div class="bp-manual-ads__content">
                <span class="bp-manual-ads__label">
                    {{ $sectionContent['label'] ?? 'Special Offer' }}
                </span>

                <h2 class="bp-manual-ads__title title-section">
                    {{ $sectionContent['title'] ?? 'Plan Your Bintan Journey With Us' }}
                </h2>

                <p class="bp-manual-ads__text text-muted">
                    {{ $sectionContent['description'] ?? 'Find curated tours, resort transfers, and flexible island experiences with simple booking support.' }}
                </p>

                <a href="{{ $buttonUrl }}" class="btn btn-primary bp-manual-ads__button">
                    <span>{{ $sectionContent['button_text'] ?? 'SEE DETAILS' }}</span>
                    <svg class="bp-manual-ads__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17L17 7"></path>
                        <path d="M9 7h8v8"></path>
                    </svg>
                </a>
            </div>

            <div class="bp-manual-ads__visual">
                @if($mainVisual?->url || $section?->image_url || $sectionPlaceholder?->url)
                    <img src="{{ $mainVisual?->url ?? $section?->image_url ?? $sectionPlaceholder->url }}" alt="{{ $mainVisual?->alt ?: ($section?->title ?? ($sectionPlaceholder?->alt ?: 'Section placeholder image')) }}" @if($mainVisual?->url) style="{{ $mainVisual->image_style }}" @elseif(! $section?->image_url) style="object-fit: {{ $sectionPlaceholderFit }}" @endif loading="lazy" decoding="async">
                @else
                    <div class="bp-manual-ads__placeholder">
                        NO IMAGE
                    </div>
                @endif

                <div class="bp-manual-ads__overlay-title">
                    {{ $overlayTitle }}
                </div>
            </div>
        </div>
    </div>
</section>
