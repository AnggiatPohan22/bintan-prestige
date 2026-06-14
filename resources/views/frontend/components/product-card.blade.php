@php
    $variant = $variant ?? 'home';
    $productDetailUrl = route('products.show', $product);
    $productCategoryName = $product->category?->name;
    $productDestinationName = $product->destination?->name;
    $productDuration = filled($product->duration) ? $product->duration : null;
    $productShortDescription = trim((string) ($product->short_description ?? ''));
    $productPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'product');
    $productPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'product');
    $productImageUrl = $product->thumbnail_url ?: $productPlaceholder?->url;
    $productImageAlt = trim($product->name . ($productDestinationName ? ' in ' . $productDestinationName : ''));
    $productImageAlt = $productImageAlt !== ''
        ? $productImageAlt
        : ($productPlaceholder->alt ?? 'Bintan product package');
    $usesPlaceholderImage = ! $product->thumbnail_url && $productPlaceholder?->url;
    $productMediaItems = collect();
    $productVideoItems = collect($productVideoItems ?? []);

    if ($productImageUrl) {
        $productMediaItems->push([
            'url' => $productImageUrl,
            'alt' => $productImageAlt,
        ]);
    }

    $product->images
        ->sortBy('sort_order')
        ->each(function ($image) use ($productMediaItems, $productImageAlt) {
            $productMediaItems->push([
                'url' => asset('storage/' . $image->image),
                'alt' => $productImageAlt,
            ]);
        });

    $productMediaItems = $productMediaItems->unique('url')->values();
@endphp

@if($variant === 'listing')
    <article class="product-card">
        <div class="product-card__media">
            @if($productImageUrl)
                <img
                    src="{{ $productImageUrl }}"
                    alt="{{ $productImageAlt }}"
                    class="product-card__image"
                    width="640"
                    height="800"
                    @if($usesPlaceholderImage) style="object-fit: {{ $productPlaceholderFit }}" @endif
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="product-card__placeholder">
                    No Image
                </div>
            @endif
        </div>

        <div class="product-card__body">
            @if($productCategoryName)
                <span class="product-card__category">
                    {{ $productCategoryName }}
                </span>
            @endif

            <h3 class="product-card__title title-card">
                <a href="{{ $productDetailUrl }}" class="product-card__title-link">
                    {{ $product->name }}
                </a>
            </h3>

            <div class="product-card__meta">
                @if($productDestinationName)
                    <span>{{ $productDestinationName }}</span>
                @endif

                @if($productDestinationName && $productDuration)
                    <span class="product-card__meta-dot" aria-hidden="true"></span>
                @endif

                @if($productDuration)
                    <span>{{ $productDuration }}</span>
                @endif
            </div>

            @if($productShortDescription !== '')
                <p class="product-card__description">
                    {{ $productShortDescription }}
                </p>
            @endif

            <div class="product-card__footer">
                @include('frontend.components.product-price', [
                    'product' => $product,
                    'context' => 'listing',
                ])

                <div class="product-card__controls">
                    @if($productMediaItems->isNotEmpty() || $productVideoItems->isNotEmpty())
                        <div class="product-card__actions" aria-label="{{ $product->name }} media actions">
                            @if($productMediaItems->isNotEmpty())
                                <button
                                    type="button"
                                    class="product-card__icon-button"
                                    aria-label="Open {{ $product->name }} image"
                                    x-on:click="$dispatch('open-product-media', {
                                        type: 'image',
                                        title: @js($product->name),
                                        items: @js($productMediaItems),
                                    })"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 8a2 2 0 0 1 2-2h2l1.5-2h5L16 6h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"></path>
                                        <circle cx="12" cy="13" r="3.5"></circle>
                                    </svg>
                                </button>
                            @endif

                            @if($productVideoItems->isNotEmpty())
                                <button
                                    type="button"
                                    class="product-card__icon-button"
                                    aria-label="Open {{ $product->name }} video"
                                    x-on:click="$dispatch('open-product-media', {
                                        type: 'video',
                                        title: @js($product->name),
                                        items: @js($productVideoItems),
                                    })"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="6" width="11" height="12" rx="2"></rect>
                                        <path d="m15 10 5-3v10l-5-3"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endif

                    <a
                        href="{{ $productDetailUrl }}"
                        class="btn btn-primary btn-sm product-card__cta"
                        aria-label="View details for {{ $product->name }}"
                    >
                        Details
                    </a>
                </div>
            </div>
        </div>
    </article>
@else
    <article
        class="bp-product-card"
        data-product-card
        data-category-id="{{ $product->category?->id }}"
    >
        <a href="{{ $productDetailUrl }}" class="bp-product-card__image" aria-label="View {{ $product->name }}">
            @if($productImageUrl)
                <img
                    src="{{ $productImageUrl }}"
                    alt="{{ $productImageAlt }}"
                    width="640"
                    height="420"
                    @if($usesPlaceholderImage) style="object-fit: {{ $productPlaceholderFit }}" @endif
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="bp-product-card__placeholder">
                    No Image
                </div>
            @endif
        </a>

        <div class="bp-product-card__body">
            @if($productCategoryName)
                <span class="bp-product-card__category">
                    {{ $productCategoryName }}
                </span>
            @endif

            <h3 class="bp-product-card__title title-card">
                <a href="{{ $productDetailUrl }}">
                    {{ $product->name }}
                </a>
            </h3>

            @if($productShortDescription !== '')
                <p class="bp-product-card__description">
                    {{ $productShortDescription }}
                </p>
            @endif

            <div class="bp-product-card__meta">
                @if($productDestinationName)
                    <span>{{ $productDestinationName }}</span>
                @endif

                @if($productDestinationName && $productDuration)
                    <span class="bp-product-card__dot" aria-hidden="true"></span>
                @endif

                @if($productDuration)
                    <span>{{ $productDuration }}</span>
                @endif
            </div>

            <div class="bp-product-card__footer">
                @include('frontend.components.product-price', [
                    'product' => $product,
                    'context' => 'home',
                ])

                <a href="{{ $productDetailUrl }}" class="btn btn-primary btn-sm bp-product-card__button">
                    Details
                </a>
            </div>
        </div>
    </article>
@endif
