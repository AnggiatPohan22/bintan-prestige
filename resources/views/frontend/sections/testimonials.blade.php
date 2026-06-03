@php
    $section = $sections['home.testimonials'] ?? null;

    $testimonials = [
        [
            'name' => 'Floyd Miles',
            'role' => 'Guest Traveller',
            'text' => 'Our Bintan trip was smooth from pickup to the tour arrangement. Everything felt organized, comfortable, and professional.',
            'rating' => 4,
            'initials' => 'FM',
        ],
        [
            'name' => 'Esther Howard',
            'role' => 'Family Traveller',
            'text' => 'The service was very helpful and easy to communicate with. The team made our island activity feel simple and enjoyable.',
            'rating' => 4,
            'initials' => 'EH',
        ],
        [
            'name' => 'Albert Flores',
            'role' => 'Resort Guest',
            'text' => 'Great experience with clear booking support and reliable transfer service. Highly recommended for guests visiting Bintan.',
            'rating' => 4,
            'initials' => 'AF',
        ],
    ];
@endphp

<section class="bp-testimonials" id="home-testimonials" data-section-key="home.testimonials" aria-labelledby="testimonials-title">
    <div class="home-container">
        <div class="bp-testimonials__header">
            <span class="bp-testimonials__label">
                {{ $section->label ?? 'Clients Feedback About Us' }}
            </span>

            <h2 id="testimonials-title" class="bp-testimonials__title title-section">
                {{ $section->title ?? 'See Those Lovely Words From Clients' }}
            </h2>

            <p class="bp-testimonials__text text-muted">
                {{ $section->description ?? 'Read what our guests say about their Bintan travel experience with Bintan Prestige.' }}
            </p>
        </div>

        <div class="bp-testimonials__grid">
            @foreach($testimonials as $testimonial)
                <article class="bp-testimonial-card">
                    <div class="bp-testimonial-card__top">
                        <div class="bp-testimonial-card__avatar" aria-label="{{ $testimonial['name'] }} avatar placeholder">
                            {{ $testimonial['initials'] }}
                        </div>

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
                            <span class="{{ $star <= $testimonial['rating'] ? 'is-filled' : '' }}" aria-hidden="true">
                                ★
                            </span>
                        @endfor
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
