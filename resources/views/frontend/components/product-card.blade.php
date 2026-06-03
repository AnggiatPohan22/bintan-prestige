<article
    class="bp-product-card"
    data-product-card
    data-category-id="{{ $product->category?->id }}"
>
    <a href="{{ route('products.show', $product) }}" class="bp-product-card__image" aria-label="View {{ $product->name }}">
        @if($product->main_image_url)
            <img
                src="{{ $product->main_image_url }}"
                alt="{{ $product->name }}"
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
        @if($product->category?->name)
            <span class="bp-product-card__category">
                {{ $product->category->name }}
            </span>
        @endif

        <h3 class="bp-product-card__title title-card">
            <a href="{{ route('products.show', $product) }}">
                {{ $product->name }}
            </a>
        </h3>

        @if($product->short_description)
            <p class="bp-product-card__description">
                {{ $product->short_description }}
            </p>
        @endif

        <div class="bp-product-card__meta">
            @if($product->destination?->name)
                <span>{{ $product->destination->name }}</span>
            @endif

            @if($product->destination?->name && $product->duration)
                <span class="bp-product-card__dot" aria-hidden="true"></span>
            @endif

            @if($product->duration)
                <span>{{ $product->duration }}</span>
            @endif
        </div>

        <div class="bp-product-card__footer">
            <div class="bp-product-card__price">
                <span>Start from</span>
                <strong>
                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                </strong>
            </div>

            <a href="{{ route('products.show', $product) }}" class="btn btn-primary btn-sm bp-product-card__button">
                Details
            </a>
        </div>
    </div>
</article>
