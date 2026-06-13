@php
    $homepageContent = $homepageContent ?? \App\Support\HomepageContent::fromSections(collect($sections ?? []));
    $section = ($homepageContent ?? [])['sections']['home.testimonials'] ?? [];
    $avatarPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'avatar');
    $avatarPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'avatar');
    $testimonials = collect(($homepageContent ?? [])['testimonials']['fallback_items'] ?? []);
@endphp

<section class="bp-testimonials" id="home-testimonials" data-section-key="home.testimonials" aria-labelledby="testimonials-title">
    <div class="home-container">
        <div class="bp-testimonials__header">
            <span class="bp-testimonials__label">
                {{ $section['label'] ?? 'Clients Feedback About Us' }}
            </span>

            <h2 id="testimonials-title" class="bp-testimonials__title title-section">
                {{ $section['title'] ?? 'See Those Lovely Words From Clients' }}
            </h2>

            <p class="bp-testimonials__text text-muted">
                {{ $section['description'] ?? 'Read what our guests say about their Bintan travel experience with Bintan Prestige.' }}
            </p>
        </div>

        @if($testimonials->isNotEmpty())
            <div class="bp-testimonials__grid">
                @foreach($testimonials as $testimonial)
                    <article class="bp-testimonial-card">
                        <div class="bp-testimonial-card__top">
                            @if($avatarPlaceholder?->url)
                                <img src="{{ $avatarPlaceholder->url }}" alt="{{ $avatarPlaceholder->alt ?: $testimonial['name'] . ' avatar placeholder' }}" class="bp-testimonial-card__avatar" style="object-fit: {{ $avatarPlaceholderFit }}" loading="lazy" decoding="async">
                            @else
                                <div class="bp-testimonial-card__avatar" aria-label="{{ $testimonial['name'] }} avatar placeholder">
                                    {{ $testimonial['initials'] }}
                                </div>
                            @endif

                            <div class="bp-testimonial-card__meta">
                                <h3 class="bp-testimonial-card__name title-card">
                                    {{ $testimonial['name'] }}
                                </h3>

                                <p>
                                    {{ $testimonial['role'] }}
                                </p>
                            </div>

                            <span class="bp-testimonial-card__quote" aria-hidden="true">
                                &ldquo;
                            </span>
                        </div>

                        <p class="bp-testimonial-card__text">
                            {{ $testimonial['text'] }}
                        </p>

                        <div class="bp-testimonial-card__stars" aria-label="{{ $testimonial['rating'] }} out of 5 stars">
                            @for($star = 1; $star <= 5; $star++)
                                <span class="{{ $star <= $testimonial['rating'] ? 'is-filled' : '' }}" aria-hidden="true">&#9733;</span>
                            @endfor
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
