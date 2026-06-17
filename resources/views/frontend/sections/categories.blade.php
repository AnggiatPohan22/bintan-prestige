@php
    $homepageContent = $homepageContent ?? \App\Support\HomepageContent::fromSections(collect($sections ?? []));
    $section = ($homepageContent ?? [])['sections']['home.categories_intro'] ?? [];
    $destinationPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'destination');
    $destinationPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'destination');
    $sectionContent = ($homepageContent ?? [])['destinations'] ?? (($homepageContent ?? [])['categories'] ?? []);
    $destinationCards = collect($homeDestinations ?? []);
@endphp

<section class="bp-category-section" id="home-categories" data-section-key="home.categories_intro" aria-labelledby="categories-title">
    <div class="home-container">
        <div class="bp-category-header">
            <span class="bp-category-header__label">
                {{ $section['label'] ?? 'Next Adventure Destination' }}
            </span>

            <h2 id="categories-title" class="bp-category-header__title title-section">
                {{ $section['title'] ?? 'Popular Travel Destinations Available In Bintan' }}
            </h2>

            <p class="bp-category-header__text text-muted">
                {{ $section['description'] ?? 'Explore Bintan by destination and discover curated packages that match your journey.' }}
            </p>
        </div>

        @if($destinationCards->count())
            <div class="bp-category-grid">
                @foreach($destinationCards as $destination)
                    <a href="{{ $destination['url'] }}" class="bp-category-card" data-destination-slug="{{ $destination['slug'] ?? '' }}" aria-label="{{ $destination['aria_label'] }}">
                        <div class="bp-category-image-frame">
                            @if($destination['image_url'] ?? null)
                                <img src="{{ $destination['image_url'] }}" alt="{{ $destination['name'] }}" loading="lazy" decoding="async">
                            @elseif($destinationPlaceholder?->url)
                                <img src="{{ $destinationPlaceholder->url }}" alt="{{ $destinationPlaceholder->alt ?: 'Destination placeholder image' }}" style="object-fit: {{ $destinationPlaceholderFit }}" loading="lazy" decoding="async">
                            @else
                                <div class="bp-category-placeholder">
                                    Destination Image
                                </div>
                            @endif

                            <span class="bp-category-count-badge">
                                {{ $destination['package_label'] }}
                            </span>
                        </div>

                        <h3 class="bp-category-name title-card">
                            {{ $destination['name'] }}
                        </h3>
                    </a>
                @endforeach
            </div>
        @else
            <div class="product-empty">
                <h3 class="product-empty__title">
                    {{ $sectionContent['empty_title'] ?? 'No destinations available yet.' }}
                </h3>
            </div>
        @endif
    </div>
</section>
