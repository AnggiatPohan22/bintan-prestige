@php
    $section = $sections['home.faq'] ?? null;
@endphp

<section id="home-faq" class="home-faq-preview" data-section-key="home.faq" aria-labelledby="home-faq-title">
    <span id="faq-preview" class="sr-only" aria-hidden="true"></span>

    <div class="home-container home-faq-preview__grid">
        <div>
            <span class="home-section__kicker">{{ $section->label ?? 'Before your journey' }}</span>
            <h2 id="home-faq-title" class="home-section__title title-section">
                {{ $section->title ?? 'All you should know before embarking on your Bintan journey' }}
            </h2>

            @if($section?->description)
                <p class="mt-4 text-sm leading-7 text-stone-600">{{ $section->description }}</p>
            @endif

            <div class="home-image-placeholder home-faq-preview__image">
                @if($section?->image_url)
                    <img src="{{ $section->image_url }}" alt="{{ $section->title ?? 'Bintan Prestige FAQ' }}" loading="lazy" decoding="async">
                @else
                    Travel Guide Image
                @endif
            </div>
        </div>

        <div class="home-faq-list">
            @forelse($faqs as $faq)
                <details {{ $loop->first ? 'open' : '' }}>
                    <summary>{{ $faq->question }}</summary>
                    <p>{{ $faq->answer }}</p>
                </details>
            @empty
                <details open>
                    <summary>Can I arrange pickup from ferry terminal or resort?</summary>
                    <p>Yes, pickup options can be arranged depending on package, meeting point, and route availability.</p>
                </details>
            @endforelse
        </div>
    </div>
</section>
