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
    $bookingMessage = urlencode($bookingMessageText);
    $productChatLabel = $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_chat_label'] ?? 'Chat via WhatsApp') : 'Chat via WhatsApp');
    $productBookingLabel = $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_booking_label'] ?? 'Book via WhatsApp') : 'Book via WhatsApp');
@endphp

<div class="product-page">

    <section class="product-detail-hero">
        <div class="product-detail-container">

            <div class="product-detail-hero__grid">

                <div class="product-detail-gallery">
                    <div class="product-detail-gallery__main">
                        @if($product->thumbnail_url || $productPlaceholder?->url)
                            <img
                                src="{{ $product->thumbnail_url ?: $productPlaceholder->url }}"
                                alt="{{ $product->thumbnail_url ? $product->name : ($productPlaceholder->alt ?: 'Product placeholder image') }}"
                                class="product-detail-gallery__image"
                                @if(! $product->thumbnail_url) style="object-fit: {{ $productPlaceholderFit }}" @endif
                                decoding="async"
                            >
                        @else
                            <div class="product-detail-gallery__placeholder">
                                No Image
                            </div>
                        @endif
                    </div>

                    @if($product->images->count())
                        <div class="product-detail-gallery__thumbs">
                            @foreach($product->images->take(4) as $image)
                                <img
                                    src="{{ asset('storage/' . $image->image) }}"
                                    class="product-detail-gallery__thumb"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="product-detail-summary">
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

    <section class="product-detail-content">
        <div class="product-detail-container product-detail-content__grid">

            <div class="product-detail-main">

                <section class="product-detail-panel">
                    <h2 class="product-detail-panel__title title-card">
                        Overview
                    </h2>

                    <div class="product-detail-richtext text-body">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </section>

                @if($product->features->count())
                    <section class="product-detail-panel">
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
                    <section class="product-detail-panel">
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
                    <section class="product-detail-panel">
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
                    <section class="product-detail-panel">
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

            <aside class="product-detail-sidebar">
                <div class="product-detail-booking-card">
                    <h3 class="product-detail-booking-card__title title-card">
                        Booking Information
                    </h3>

                    <div class="product-detail-booking-card__list">
                        <div class="product-detail-booking-card__row">
                            <span>Duration</span>
                            <strong>{{ $product->duration ?: '-' }}</strong>
                        </div>

                        <div class="product-detail-booking-card__row">
                            <span>Meeting Point</span>
                            <strong>{{ $product->meeting_point ?: '-' }}</strong>
                        </div>

                        @if($product->pickup_available)
                            <div class="product-detail-booking-card__row">
                                <span>Pickup</span>
                                <strong>{{ $product->pickup_type ?: 'Available' }}</strong>
                            </div>

                            @if($product->pickup_note)
                                <p class="product-detail-booking-card__note">
                                    {{ $product->pickup_note }}
                                </p>
                            @endif
                        @endif
                    </div>

                    <a
                        href="https://wa.me/{{ $waNumber }}?text={{ $bookingMessage }}"
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
