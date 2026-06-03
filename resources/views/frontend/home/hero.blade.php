@php
    $section = $sections['home.hero'] ?? null;
    $heroImageUrl = $section?->image_url ?? $heroBackgroundUrl;
    $heroMedia = $section?->activeMedia?->take(10) ?? collect();
    $heroSlides = $heroMedia->count()
        ? $heroMedia->map(fn ($media) => ['url' => $media->url, 'alt' => $media->alt ?? ($section?->title ?? 'Bintan Prestige')])
        : collect($heroImageUrl ? [['url' => $heroImageUrl, 'alt' => $section?->title ?? 'Bintan Prestige']] : []);
    $heroSettings = $heroSettings ?? [];
    $heroAnimation = $section?->extra_data['animation'] ?? ($heroSettings['animation'] ?? 'ken-burns');
@endphp

<section
    id="home-hero"
    class="home-hero"
    data-section-key="home.hero"
    data-hero-background="{{ $heroImageUrl }}"
    data-hero-slider
    data-hero-animation="{{ $heroAnimation }}"
    data-hero-duration="{{ $heroSettings['slide_duration'] ?? 6500 }}"
    data-hero-fit="{{ $heroSettings['image_fit'] ?? 'cover' }}"
    data-hero-position="{{ $heroSettings['image_position'] ?? 'center center' }}"
    data-hero-overlay="{{ $heroSettings['overlay_opacity'] ?? 0.72 }}"
    data-hero-align="{{ $heroSettings['text_alignment'] ?? 'left' }}"
>
    @if($heroSlides->count())
        <div class="home-hero__media" aria-hidden="true">
            @foreach($heroSlides as $slide)
                <img
                    src="{{ $slide['url'] }}"
                    alt="{{ $slide['alt'] }}"
                    class="home-hero__slide {{ $loop->first ? 'is-active' : '' }}"
                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                    decoding="async"
                    data-hero-slide
                >
            @endforeach
        </div>
    @endif

    <div class="home-container">
        <div class="home-hero__stage">
            <div class="home-hero__copy">
                <span class="home-eyebrow">
                    {{ $section->label ?? 'Luxury Bintan Travel' }}
                </span>

                <h1 class="home-hero__title title-hero">
                    {{ $section->title ?? 'BINTAN PRESTIGE' }}
                </h1>

                <p class="home-hero__text text-body">
                    {{ $section->description ?? 'Private tours, island transfers, and curated experiences designed for a smoother premium escape.' }}
                </p>
            </div>
        </div>
    </div>
</section>
