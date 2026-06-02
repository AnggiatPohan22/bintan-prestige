<article class="product-card">

    <div class="product-card__media">
        @if($product->thumbnail_url)
            <img
                src="{{ $product->thumbnail_url }}"
                alt="{{ $product->name }}"
                class="product-card__image"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="product-card__placeholder">
                No Image
            </div>
        @endif

        @if($product->category)
            <span class="product-card__category">
                {{ $product->category->name }}
            </span>
        @endif
    </div>

    <div class="product-card__body">

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

        <h2 class="product-card__title title-card">
            {{ $product->name }}
        </h2>

        <p class="product-card__description text-muted">
            {{ $product->short_description }}
        </p>

        @if($product->highlights->count())
            <div class="product-card__highlights">
                @foreach($product->highlights->take(3) as $highlight)
                    <span class="product-card__highlight">
                        {{ $highlight->title }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="product-card__footer">

            <div>
                <p class="product-card__price-label">
                    Start from
                </p>

                <p class="product-card__price-main">
                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                </p>

                @if($product->sgd_price)
                    <p class="product-card__price-secondary">
                        SGD {{ number_format($product->sgd_price, 0) }}
                    </p>
                @endif
            </div>

            <a href="{{ route('products.show', $product) }}"
               class="btn btn-primary btn-sm product-card__button">
                View
            </a>

        </div>

    </div>

</article>
