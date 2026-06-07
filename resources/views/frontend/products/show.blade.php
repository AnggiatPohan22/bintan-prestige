@extends('layouts.frontend')

@section('content')

@php
    $bookingCtaSettings = $bookingCtaSettings ?? \App\Support\BookingCtaSettings::valuesFromSettings(collect());
    $productPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'product');
    $productPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'product');
    $usesGlobalProductCta = \App\Support\BookingCtaSettings::isEnabledFor($bookingCtaSettings, 'product');
    $productCtaContext = [
        'site_name' => $businessIdentity['brand_name'] ?? config('app.name'),
        'product_name' => $product->name,
        'product_url' => route('products.show', $product),
        'page_url' => url()->current(),
    ];
    $waNumber = $usesGlobalProductCta
        ? \App\Support\BookingCtaSettings::whatsappNumber($bookingCtaSettings, $contactInformation ?? [], $product->whatsapp_number)
        : preg_replace('/[^0-9]/', '', $product->whatsapp_number);

    $waMessageText = $usesGlobalProductCta
        ? \App\Support\BookingCtaSettings::renderMessage($bookingCtaSettings['product_message_template'] ?? '', $productCtaContext)
        : "Hello, I want to ask about:\n\n" . $product->name . "\n" . route('products.show', $product);

    $bookingMessageText = $usesGlobalProductCta
        ? \App\Support\BookingCtaSettings::renderMessage($bookingCtaSettings['product_message_template'] ?? '', $productCtaContext)
        : 'Hello, I want to book ' . $product->name;

    $waMessage = urlencode($waMessageText);
    $productChatLabel = $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_chat_label'] ?? 'Chat via WhatsApp') : 'Chat via WhatsApp');
    $productBookingLabel = $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_booking_label'] ?? 'Book via WhatsApp') : 'Book via WhatsApp');
    $addonOptions = $product->features
        ->where('label', 'addon')
        ->pluck('value')
        ->filter()
        ->values();

    if ($product->pickup_available) {
        $addonOptions = $addonOptions
            ->merge([
                $product->pickup_type ?: 'Pickup',
                'Drop off',
            ])
            ->unique()
            ->values();
    }

    $galleryImages = collect();

    if ($product->thumbnail_url) {
        $galleryImages->push([
            'url' => $product->thumbnail_url,
            'alt' => $product->name,
            'is_placeholder' => false,
        ]);
    }

    $product->images
        ->sortBy('sort_order')
        ->each(function ($image) use ($galleryImages, $product) {
            $galleryImages->push([
                'url' => asset('storage/' . $image->image),
                'alt' => $product->name,
                'is_placeholder' => false,
            ]);
        });

    if ($galleryImages->isEmpty() && $productPlaceholder?->url) {
        $galleryImages->push([
            'url' => $productPlaceholder->url,
            'alt' => $productPlaceholder->alt ?: 'Product placeholder image',
            'is_placeholder' => true,
        ]);
    }

    $galleryImages = $galleryImages->unique('url')->values();
@endphp

<div
    class="product-page product-detail-page"
    data-page-key="products.show"
    x-data="{
        bookingDate: '',
        adults: 1,
        children: 0,
        addons: [],
        waNumber: @js($waNumber),
        baseMessage: @js($bookingMessageText),
        productName: @js($product->name),
        productUrl: @js(route('products.show', $product)),
        meetingPoint: @js($product->meeting_point ?: '-'),
        duration: @js($product->duration ?: '-'),
        galleryIndex: 0,
        galleryImages: @js($galleryImages),
        increment(field) {
            this[field] = Math.max(0, this[field] + 1);
        },
        decrement(field) {
            const minimum = field === 'adults' ? 1 : 0;
            this[field] = Math.max(minimum, this[field] - 1);
        },
        bookingWhatsappUrl() {
            const lines = [
                this.baseMessage,
                '',
                'Booking Information:',
                `Product: ${this.productName}`,
                `Date: ${this.bookingDate || '-'}`,
                `Adults: ${this.adults}`,
                `Children: ${this.children}`,
                `Duration: ${this.duration}`,
                `Meeting Point: ${this.meetingPoint}`,
                `Add-ons: ${this.addons.length ? this.addons.join(', ') : '-'}`,
                `Product URL: ${this.productUrl}`,
            ];

            return `https://wa.me/${this.waNumber}?text=${encodeURIComponent(lines.join('\n'))}`;
        },
        nextGalleryImage() {
            if (! this.galleryImages.length) return;
            this.galleryIndex = (this.galleryIndex + 1) % this.galleryImages.length;
        },
        previousGalleryImage() {
            if (! this.galleryImages.length) return;
            this.galleryIndex = (this.galleryIndex - 1 + this.galleryImages.length) % this.galleryImages.length;
        },
    }"
>

    <section
        id="products-show-hero"
        class="product-detail-hero"
        data-section-key="products.show.hero"
    >
        <div class="product-detail-container">

            <div class="product-detail-hero__grid">

                <div
                    id="products-show-gallery"
                    class="product-detail-gallery"
                    data-section-key="products.show.gallery"
                >
                    <div class="product-detail-gallery__main">
                        @if($galleryImages->count())
                            <template x-for="(image, index) in galleryImages" :key="image.url">
                                <img
                                    x-show="galleryIndex === index"
                                    x-transition.opacity
                                    :src="image.url"
                                    :alt="image.alt"
                                    class="product-detail-gallery__image"
                                    :style="image.is_placeholder ? 'object-fit: {{ $productPlaceholderFit }}' : ''"
                                    decoding="async"
                                >
                            </template>

                            @if($galleryImages->count() > 1)
                                <button
                                    type="button"
                                    class="product-detail-gallery__nav product-detail-gallery__nav--previous"
                                    aria-label="Previous product image"
                                    x-on:click="previousGalleryImage()"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m15 18-6-6 6-6"></path>
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="product-detail-gallery__nav product-detail-gallery__nav--next"
                                    aria-label="Next product image"
                                    x-on:click="nextGalleryImage()"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m9 6 6 6-6 6"></path>
                                    </svg>
                                </button>

                                <div class="product-detail-gallery__count" x-text="(galleryIndex + 1) + ' / ' + galleryImages.length"></div>
                            @endif
                        @else
                            <div class="product-detail-gallery__placeholder">
                                No Image
                            </div>
                        @endif
                    </div>

                    @if($galleryImages->count() > 1)
                        <div class="product-detail-gallery__thumbs">
                            @foreach($galleryImages->take(4) as $image)
                                <button
                                    type="button"
                                    class="product-detail-gallery__thumb"
                                    :class="{ 'is-active': galleryIndex === {{ $loop->index }} }"
                                    x-on:click="galleryIndex = {{ $loop->index }}"
                                    aria-label="Show product image {{ $loop->iteration }}"
                                >
                                    <img
                                        src="{{ $image['url'] }}"
                                        alt="{{ $image['alt'] }}"
                                        @if($image['is_placeholder']) style="object-fit: {{ $productPlaceholderFit }}" @endif
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div
                    id="products-show-summary"
                    class="product-detail-summary"
                    data-section-key="products.show.summary"
                >
                    <div class="product-detail-badges">
                        @if($product->category?->name)
                            <span class="product-detail-badge product-detail-badge--primary">
                                {{ $product->category->name }}
                            </span>
                        @endif

                        @if($product->destination?->name)
                            <span class="product-detail-badge">
                                {{ $product->destination->name }}
                            </span>
                        @endif
                    </div>

                    <h1 class="product-detail-title title-section">
                        {{ $product->name }}
                    </h1>

                    @if($product->short_description)
                        <p class="product-detail-description text-body">
                            {{ $product->short_description }}
                        </p>
                    @endif

                    <div class="product-detail-meta">
                        <div class="product-detail-meta__item">
                            <span class="product-detail-meta__label">Duration</span>
                            <span class="product-detail-meta__value">{{ $product->duration ?: '-' }}</span>
                        </div>

                        <div class="product-detail-meta__item">
                            <span class="product-detail-meta__label">Pickup</span>
                            <span class="product-detail-meta__value">
                                {{ $product->pickup_available ? ($product->pickup_type ?: 'Available') : 'Not included' }}
                            </span>
                        </div>
                    </div>

                    <div class="product-detail-price-card">
                        <p class="product-detail-price-card__label">
                            Start from
                        </p>

                        <p class="product-detail-price-card__main">
                            Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                        </p>

                        @if($product->sgd_price)
                            <p class="product-detail-price-card__secondary">
                                SGD {{ number_format($product->sgd_price, 0) }}
                            </p>
                        @endif

                        <a
                            href="https://wa.me/{{ $waNumber }}?text={{ $waMessage }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-whatsapp product-detail-button"
                            data-whatsapp-tracking="product"
                            data-tracking-label="{{ $productChatLabel }}"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-product-slug="{{ $product->slug }}"
                        >
                            {{ $productChatLabel }}
                        </a>

                        @if($product->cta_title || $product->cta_description)
                            <div class="product-detail-cta-note">
                                @if($product->cta_title)
                                    <p class="product-detail-cta-note__title">
                                        {{ $product->cta_title }}
                                    </p>
                                @endif

                                @if($product->cta_description)
                                    <p class="product-detail-cta-note__text">
                                        {{ $product->cta_description }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($product->highlights->count())
                        <div class="product-detail-highlights">
                            @foreach($product->highlights as $highlight)
                                <div class="product-detail-highlight">
                                    @if($highlight->icon)
                                        <i class="fa-solid {{ $highlight->icon }} product-detail-highlight__icon"></i>
                                    @else
                                        <span class="product-detail-highlight__dot"></span>
                                    @endif

                                    <span class="product-detail-highlight__text">
                                        {{ $highlight->title }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>
    </section>

    <section
        id="products-show-content"
        class="product-detail-content"
        data-section-key="products.show.content"
    >
        <div class="product-detail-container product-detail-content__grid">

            <div class="product-detail-main">

                <section
                    id="products-show-overview"
                    class="product-detail-panel"
                    data-section-key="products.show.overview"
                >
                    <h2 class="product-detail-panel__title title-card">
                        Overview
                    </h2>

                    <div class="product-detail-richtext text-body">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </section>

                @if($product->features->count())
                    <section
                        id="products-show-features"
                        class="product-detail-panel"
                        data-section-key="products.show.features"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            What's Included
                        </h2>

                        <div class="product-detail-feature-grid">
                            @foreach([
                                'included' => 'Included',
                                'excluded' => 'Excluded',
                                'optional' => 'Optional',
                                'addon' => 'Add-ons',
                                'important' => 'Important'
                            ] as $label => $title)

                                @php
                                    $items = $product->features->where('label', $label);
                                @endphp

                                @if($items->count())
                                    <div class="product-detail-feature-group">
                                        <h3 class="product-detail-feature-group__title title-card">
                                            {{ $title }}
                                        </h3>

                                        <ul class="product-detail-list">
                                            @foreach($items as $item)
                                                <li class="product-detail-list__item">
                                                    <span class="product-detail-list__marker"></span>
                                                    <span>{{ $item->value }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                            @endforeach
                        </div>
                    </section>
                @endif

                @if($product->itineraries->count())
                    <section
                        id="products-show-itinerary"
                        class="product-detail-panel"
                        data-section-key="products.show.itinerary"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            Itinerary
                        </h2>

                        <div class="product-detail-timeline">
                            @foreach($product->itineraries as $itinerary)
                                <div class="product-detail-timeline__item">
                                    <div class="product-detail-timeline__time">
                                        {{ $itinerary->time ?: '-' }}
                                    </div>

                                    <div class="product-detail-timeline__content">
                                        <h3 class="product-detail-timeline__title title-card">
                                            {{ $itinerary->title }}
                                        </h3>

                                        @if($itinerary->description)
                                            <p class="product-detail-timeline__text">
                                                {{ $itinerary->description }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($product->notes->count())
                    <section
                        id="products-show-notes"
                        class="product-detail-panel"
                        data-section-key="products.show.notes"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            Important Notes
                        </h2>

                        <div class="product-detail-note-list">
                            @foreach($product->notes as $note)
                                <div class="product-detail-note">
                                    @if($note->title)
                                        <h3 class="product-detail-note__title title-card">
                                            {{ $note->title }}
                                        </h3>
                                    @endif

                                    <p class="product-detail-note__text">
                                        {{ $note->description }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($product->faqs->count())
                    <section
                        id="products-show-faq"
                        class="product-detail-panel"
                        data-section-key="products.show.faq"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            FAQ
                        </h2>

                        <div class="product-detail-faq-list">
                            @foreach($product->faqs as $faq)
                                <details class="product-detail-faq">
                                    <summary class="product-detail-faq__question">
                                        {{ $faq->question }}
                                    </summary>

                                    <p class="product-detail-faq__answer">
                                        {{ $faq->answer }}
                                    </p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

            </div>

            <aside
                id="products-show-booking"
                class="product-detail-sidebar"
                data-section-key="products.show.booking"
            >
                <div class="product-detail-booking-card">
                    <div class="product-detail-booking-card__heading">
                        <span class="product-detail-booking-card__accent" aria-hidden="true"></span>
                        <h3 class="product-detail-booking-card__title title-card">
                            Booking Information
                        </h3>
                    </div>

                    <div class="product-detail-booking-card__list">
                        <div class="product-detail-booking-card__price-row">
                            <span>Price from</span>
                            <strong>Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}</strong>
                        </div>

                        @if($product->sgd_price)
                            <p class="product-detail-booking-card__secondary-price">
                                SGD {{ number_format($product->sgd_price, 0) }}
                            </p>
                        @endif

                        <label class="product-detail-booking-field">
                            <span>Date</span>
                            <input type="date" x-model="bookingDate">
                        </label>

                        <div class="product-detail-booking-counter">
                            <div>
                                <span>Adults</span>
                                <p>Over 18</p>
                            </div>

                            <div class="product-detail-booking-stepper" aria-label="Adults quantity">
                                <button type="button" x-on:click="decrement('adults')" aria-label="Decrease adults">
                                    -
                                </button>
                                <strong x-text="adults"></strong>
                                <button type="button" x-on:click="increment('adults')" aria-label="Increase adults">
                                    +
                                </button>
                            </div>
                        </div>

                        <div class="product-detail-booking-counter">
                            <div>
                                <span>Children</span>
                                <p>Under 18</p>
                            </div>

                            <div class="product-detail-booking-stepper" aria-label="Children quantity">
                                <button type="button" x-on:click="decrement('children')" aria-label="Decrease children">
                                    -
                                </button>
                                <strong x-text="children"></strong>
                                <button type="button" x-on:click="increment('children')" aria-label="Increase children">
                                    +
                                </button>
                            </div>
                        </div>

                        <div class="product-detail-booking-card__row">
                            <span>Duration</span>
                            <strong>{{ $product->duration ?: '-' }}</strong>
                        </div>

                        <div class="product-detail-booking-card__row">
                            <span>Meeting Point</span>
                            <strong>{{ $product->meeting_point ?: '-' }}</strong>
                        </div>

                        @if($addonOptions->count())
                            <fieldset class="product-detail-booking-addons">
                                <legend>Add-ons</legend>
                                <p>Select extra services for your reservation.</p>

                                <div class="product-detail-booking-addons__list">
                                    @foreach($addonOptions as $addon)
                                        <label class="product-detail-booking-check">
                                            <input type="checkbox" value="{{ $addon }}" x-model="addons">
                                            <span>{{ $addon }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        @if($product->pickup_available && $product->pickup_note)
                            <p class="product-detail-booking-card__note">
                                {{ $product->pickup_note }}
                            </p>
                        @endif
                    </div>

                    <a
                        x-bind:href="bookingWhatsappUrl()"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-whatsapp product-detail-button"
                        data-whatsapp-tracking="product"
                        data-tracking-label="{{ $productBookingLabel }}"
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-product-slug="{{ $product->slug }}"
                    >
                        {{ $productBookingLabel }}
                    </a>
                </div>
            </aside>

        </div>
    </section>

</div>

@endsection
