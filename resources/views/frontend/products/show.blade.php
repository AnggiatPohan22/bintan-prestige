@extends('layouts.frontend')

@section('content')

<div
    class="product-page product-detail-page"
    data-page-key="products.show"
    x-data="{
        bookingDate: '',
        adults: 1,
        children: 0,
        addons: [],
        galleryIndex: 0,
        galleryImages: @js($mediaState['items']),
        activeGalleryImage() {
            return this.galleryImages[this.galleryIndex] || null;
        },
        activeGalleryStyle() {
            const image = this.activeGalleryImage();

            return image && image.fit ? `object-fit: ${image.fit}` : '';
        },
        selectGalleryImage(index) {
            if (index < 0 || index >= this.galleryImages.length) {
                return;
            }

            this.galleryIndex = index;
        },
        increment(field) {
            this[field] = Math.max(0, this[field] + 1);
        },
        decrement(field) {
            const minimum = field === 'adults' ? 1 : 0;
            this[field] = Math.max(minimum, this[field] - 1);
        },
        nextGalleryImage() {
            if (! this.galleryImages.length) return;
            this.selectGalleryImage((this.galleryIndex + 1) % this.galleryImages.length);
        },
        previousGalleryImage() {
            if (! this.galleryImages.length) return;
            this.selectGalleryImage((this.galleryIndex - 1 + this.galleryImages.length) % this.galleryImages.length);
        },
    }"
>

    <section
        id="products-show-hero"
        class="product-detail-hero"
        data-section-key="products.show.hero"
    >
        <div class="product-detail-container">

            <nav class="product-breadcrumb product-detail-breadcrumb" aria-label="Breadcrumb">
                <ol class="product-breadcrumb__list">
                    @foreach($breadcrumbState as $item)
                        <li class="product-breadcrumb__item">
                            @if(! $loop->first)
                                <span class="product-breadcrumb__separator" aria-hidden="true">/</span>
                            @endif

                            @if($item['url'] && ! $item['current'])
                                <a href="{{ $item['url'] }}" class="product-breadcrumb__link">
                                    {{ $item['label'] }}
                                </a>
                            @else
                                <span class="product-breadcrumb__current" @if($item['current']) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="product-detail-hero__grid">

                <div
                    id="products-show-gallery"
                    class="product-detail-gallery"
                    data-section-key="products.show.gallery"
                >
                    <div class="product-detail-gallery__main">
                        @if($mediaState['has_gallery'] && $mediaState['primary'])
                            <img
                                src="{{ $mediaState['primary']['url'] }}"
                                alt="{{ $mediaState['primary']['alt'] }}"
                                class="product-detail-gallery__image"
                                width="1200"
                                height="900"
                                @if($mediaState['primary']['fit']) style="object-fit: {{ $mediaState['primary']['fit'] }}" @endif
                                x-bind:src="activeGalleryImage() ? activeGalleryImage().url : @js($mediaState['primary']['url'])"
                                x-bind:alt="activeGalleryImage() ? activeGalleryImage().alt : @js($mediaState['primary']['alt'])"
                                x-bind:style="activeGalleryStyle()"
                                decoding="async"
                            >

                            @if($mediaState['count'] > 1)
                                <button
                                    type="button"
                                    class="product-detail-gallery__nav product-detail-gallery__nav--previous"
                                    aria-label="Previous image of {{ $product->name }}"
                                    x-on:click="previousGalleryImage()"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m15 18-6-6 6-6"></path>
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="product-detail-gallery__nav product-detail-gallery__nav--next"
                                    aria-label="Next image of {{ $product->name }}"
                                    x-on:click="nextGalleryImage()"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m9 6 6 6-6 6"></path>
                                    </svg>
                                </button>

                                <div class="product-detail-gallery__count" aria-live="polite" aria-atomic="true">
                                    <span x-text="(galleryIndex + 1) + ' / ' + galleryImages.length">1 / {{ $mediaState['count'] }}</span>
                                </div>
                            @endif
                        @else
                            <div class="product-detail-gallery__placeholder">
                                No Image
                            </div>
                        @endif
                    </div>

                    @if($mediaState['count'] > 1)
                        <div class="product-detail-gallery__thumbs">
                            @foreach($mediaState['thumbnails'] as $image)
                                <button
                                    type="button"
                                    class="product-detail-gallery__thumb @if($loop->first) is-active @endif"
                                    :class="{ 'is-active': galleryIndex === {{ $loop->index }} }"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    x-bind:aria-pressed="galleryIndex === {{ $loop->index }} ? 'true' : 'false'"
                                    x-bind:aria-current="galleryIndex === {{ $loop->index }} ? 'true' : null"
                                    x-on:click="selectGalleryImage({{ $loop->index }})"
                                    aria-label="View image {{ $loop->iteration }} of {{ $product->name }}"
                                >
                                    <img
                                        src="{{ $image['url'] }}"
                                        alt=""
                                        aria-hidden="true"
                                        width="240"
                                        height="180"
                                        @if($image['fit']) style="object-fit: {{ $image['fit'] }}" @endif
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

                    @if($descriptionState['has_short_description'])
                        <p class="product-detail-description text-body">
                            {{ $descriptionState['short_description'] }}
                        </p>
                    @endif

                    <div class="product-detail-meta">
                        @if($durationState['has_value'])
                            <div class="product-detail-meta__item">
                                <span class="product-detail-meta__label">Duration</span>
                                <span class="product-detail-meta__value">{{ $durationState['display'] }}</span>
                            </div>
                        @endif

                        @if($meetingPointState['has_value'])
                            <div class="product-detail-meta__item">
                                <span class="product-detail-meta__label">Meeting Point</span>
                                <span class="product-detail-meta__value">{{ $meetingPointState['display'] }}</span>
                            </div>
                        @endif

                        <div class="product-detail-meta__item">
                            <span class="product-detail-meta__label">Pickup</span>
                            <span class="product-detail-meta__value">
                                {{ $pickupState['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="product-detail-price-card">
                        @include('frontend.components.product-price', [
                            'product' => $product,
                            'context' => 'detail',
                            'label' => 'Start from',
                            'priceState' => $priceState,
                        ])

                        @if($whatsappState['available'])
                            <a
                                href="{{ $whatsappState['chat_url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-whatsapp product-detail-button"
                                data-whatsapp-tracking="product"
                                data-tracking-label="{{ $whatsappState['chat_label'] }}"
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-product-slug="{{ $product->slug }}"
                                aria-label="{{ $whatsappState['chat_accessible_label'] }}"
                            >
                                {{ $whatsappState['chat_label'] }}
                            </a>
                            <p class="product-detail-whatsapp-note">
                                {{ $whatsappState['booking_note'] }}
                            </p>
                        @endif

                        @if($ctaState['has_content'])
                            <div class="product-detail-cta-note">
                                @if($ctaState['has_title'])
                                    <p class="product-detail-cta-note__title">
                                        {{ $ctaState['title'] }}
                                    </p>
                                @endif

                                @if($ctaState['has_description'])
                                    <p class="product-detail-cta-note__text">
                                        {{ $ctaState['description'] }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($sectionState['has_highlights'])
                        <div class="product-detail-highlights">
                            @foreach($highlightItems as $highlight)
                                <div class="product-detail-highlight">
                                    @if($highlight['icon'])
                                        <i class="fa-solid {{ $highlight['icon'] }} product-detail-highlight__icon"></i>
                                    @else
                                        <span class="product-detail-highlight__dot"></span>
                                    @endif

                                    <span class="product-detail-highlight__text">
                                        {{ $highlight['title'] }}
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

                @if($sectionState['has_overview'])
                    <section
                        id="products-show-overview"
                        class="product-detail-panel"
                        data-section-key="products.show.overview"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            Overview
                        </h2>

                        <div class="product-detail-richtext text-body">
                            {!! nl2br(e($descriptionState['plain_text'])) !!}
                        </div>
                    </section>
                @endif

                @if($sectionState['has_features'])
                    <section
                        id="products-show-features"
                        class="product-detail-panel"
                        data-section-key="products.show.features"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            What's Included
                        </h2>

                        <div class="product-detail-feature-grid">
                            @foreach($featureGroups as $group)
                                @if($group['items']->isNotEmpty())
                                    <div class="product-detail-feature-group">
                                        <h3 class="product-detail-feature-group__title title-card">
                                            {{ $group['title'] }}
                                        </h3>

                                        <ul class="product-detail-list">
                                            @foreach($group['items'] as $item)
                                                <li class="product-detail-list__item">
                                                    <span class="product-detail-list__marker"></span>
                                                    <span>{{ $item['value'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($sectionState['has_itineraries'])
                    <section
                        id="products-show-itinerary"
                        class="product-detail-panel"
                        data-section-key="products.show.itinerary"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            Itinerary
                        </h2>

                        <ol class="product-detail-timeline">
                            @foreach($itineraryItems as $itinerary)
                                <li class="product-detail-timeline__item @if(! $itinerary['has_time']) product-detail-timeline__item--no-time @endif">
                                    @if($itinerary['has_time'])
                                        <div class="product-detail-timeline__time">
                                            <span class="sr-only">Itinerary time: </span>
                                            {{ $itinerary['time'] }}
                                        </div>
                                    @endif

                                    <div class="product-detail-timeline__content">
                                        @if($itinerary['has_title'])
                                            <h3 class="product-detail-timeline__title title-card">
                                                {{ $itinerary['title'] }}
                                            </h3>
                                        @endif

                                        @if($itinerary['has_description'])
                                            <p class="product-detail-timeline__text">
                                                {{ $itinerary['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($sectionState['has_notes'])
                    <section
                        id="products-show-notes"
                        class="product-detail-panel"
                        data-section-key="products.show.notes"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            Important Notes
                        </h2>

                        <div class="product-detail-note-list">
                            @foreach($noteItems as $note)
                                <div class="product-detail-note">
                                    @if($note['has_title'])
                                        <h3 class="product-detail-note__title title-card">
                                            {{ $note['title'] }}
                                        </h3>
                                    @endif

                                    @if($note['has_description'])
                                        <p class="product-detail-note__text">
                                            {{ $note['description'] }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($sectionState['has_faqs'])
                    <section
                        id="products-show-faq"
                        class="product-detail-panel"
                        data-section-key="products.show.faq"
                    >
                        <h2 class="product-detail-panel__title title-card">
                            FAQ
                        </h2>

                        <div class="product-detail-faq-list">
                            @foreach($faqItems as $faq)
                                <details class="product-detail-faq">
                                    <summary class="product-detail-faq__question">
                                        {{ $faq['question'] }}
                                    </summary>

                                    @if($faq['has_answer'])
                                        <p class="product-detail-faq__answer">
                                            {{ $faq['answer'] }}
                                        </p>
                                    @endif
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
                    @if($whatsappState['available'])
                        <p class="product-detail-booking-card__intro">
                            {{ $whatsappState['booking_note'] }}
                        </p>
                    @endif

                    <div class="product-detail-booking-card__list">
                        @include('frontend.components.product-price', [
                            'product' => $product,
                            'context' => 'booking',
                            'label' => 'Price from',
                            'priceState' => $priceState,
                        ])

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

                        @if($durationState['has_value'])
                            <div class="product-detail-booking-card__row">
                                <span>Duration</span>
                                <strong>{{ $durationState['display'] }}</strong>
                            </div>
                        @endif

                        @if($meetingPointState['has_value'])
                            <div class="product-detail-booking-card__row">
                                <span>Meeting Point</span>
                                <strong>{{ $meetingPointState['display'] }}</strong>
                            </div>
                        @endif

                        @if($sectionState['has_addons'])
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

                        @if($pickupState['available'] && $pickupState['has_note'])
                            <p class="product-detail-booking-card__note">
                                {{ $pickupState['note'] }}
                            </p>
                        @endif
                    </div>

                    @if($whatsappState['available'])
                        <a
                            href="{{ $whatsappState['booking_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-whatsapp product-detail-button"
                            data-whatsapp-tracking="product"
                            data-tracking-label="{{ $whatsappState['booking_label'] }}"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-product-slug="{{ $product->slug }}"
                            aria-label="{{ $whatsappState['booking_accessible_label'] }}"
                        >
                            {{ $whatsappState['booking_label'] }}
                        </a>
                    @endif
                </div>
            </aside>

        </div>
    </section>

</div>

@endsection
