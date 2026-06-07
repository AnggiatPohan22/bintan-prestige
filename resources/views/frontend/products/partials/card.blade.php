@php
    $productPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'product');
    $productPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'product');
    $productImageUrl = $product->thumbnail_url ?: $productPlaceholder?->url;
    $productImageAlt = $product->thumbnail_url ? $product->name : ($productPlaceholder->alt ?? 'Product placeholder image');
    $usesPlaceholderImage = ! $product->thumbnail_url && $productPlaceholder?->url;
    $productMediaItems = collect();

    if ($productImageUrl) {
        $productMediaItems->push([
            'url' => $productImageUrl,
            'alt' => $productImageAlt,
        ]);
    }

    $product->images
        ->sortBy('sort_order')
        ->each(function ($image) use ($productMediaItems, $product) {
            $productMediaItems->push([
                'url' => asset('storage/' . $image->image),
                'alt' => $product->name,
            ]);
        });

    $productMediaItems = $productMediaItems->unique('url')->values();
@endphp

<article class="product-card">

    <div class="product-card__media">
        @if($productImageUrl)
            <img
                src="{{ $productImageUrl }}"
                alt="{{ $productImageAlt }}"
                class="product-card__image"
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

        @if($product->category)
            <span class="product-card__category">
                {{ $product->category->name }}
            </span>
        @endif

        <div class="product-card__price">
            <p class="product-card__price-main">
                Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
            </p>

            @if($product->sgd_price)
                <p class="product-card__price-secondary">
                    SGD {{ number_format($product->sgd_price, 0) }}
                </p>
            @endif
        </div>

        <h2 class="product-card__title title-card">
            <a href="{{ route('products.show', $product) }}" class="product-card__title-link">
                {{ $product->name }}
            </a>
        </h2>

        <div class="product-card__footer">
            <div class="product-card__meta">
                @if($product->destination?->name)
                    <span>{{ $product->destination->name }}</span>
                @endif

                @if($product->destination?->name && $product->duration)
                    <span class="product-card__meta-dot" aria-hidden="true"></span>
                @endif

                @if($product->duration)
                    <span>{{ $product->duration }}</span>
                @endif
            </div>

            <div class="product-card__actions" aria-label="{{ $product->name }} media actions">
                @if($productImageUrl)
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

                <button
                    type="button"
                    class="product-card__icon-button"
                    aria-label="Open {{ $product->name }} video"
                    x-on:click="$dispatch('open-product-media', {
                        type: 'video',
                        title: @js($product->name),
                        items: [],
                    })"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="6" width="11" height="12" rx="2"></rect>
                        <path d="m15 10 5-3v10l-5-3"></path>
                    </svg>
                </button>
            </div>

        </div>

    </div>

</article>
