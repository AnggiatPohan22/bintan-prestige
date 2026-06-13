@php
    $context = $context ?? 'home';
    $label = $label ?? 'Start from';
    $emptyLabel = $emptyLabel ?? 'Price on request';
    $idrPrice = $product->idr_price;
    $sgdPrice = $product->sgd_price;
    $hasIdrPrice = $idrPrice !== null;
    $hasSgdPrice = $sgdPrice !== null;
@endphp

@if($context === 'listing')
    <div class="product-card__price">
        <p class="product-card__price-main {{ ! $hasIdrPrice && ! $hasSgdPrice ? 'product-card__price-main--empty' : '' }}">
            @if($hasIdrPrice)
                Rp {{ number_format($idrPrice, 0, ',', '.') }}
            @elseif($hasSgdPrice)
                SGD {{ number_format($sgdPrice, 0) }}
            @else
                {{ $emptyLabel }}
            @endif
        </p>

        @if($hasIdrPrice && $hasSgdPrice)
            <p class="product-card__price-secondary">
                SGD {{ number_format($sgdPrice, 0) }}
            </p>
        @endif
    </div>
@elseif($context === 'detail')
    <div class="product-detail-price-card__price">
        <p class="product-detail-price-card__label">
            {{ $label }}
        </p>

        <p class="product-detail-price-card__main {{ ! $hasIdrPrice && ! $hasSgdPrice ? 'product-detail-price-card__main--empty' : '' }}">
            @if($hasIdrPrice)
                Rp {{ number_format($idrPrice, 0, ',', '.') }}
            @elseif($hasSgdPrice)
                SGD {{ number_format($sgdPrice, 0) }}
            @else
                {{ $emptyLabel }}
            @endif
        </p>

        @if($hasIdrPrice && $hasSgdPrice)
            <p class="product-detail-price-card__secondary">
                SGD {{ number_format($sgdPrice, 0) }}
            </p>
        @endif
    </div>
@elseif($context === 'booking')
    <div class="product-detail-booking-card__price-row">
        <span>{{ $label }}</span>
        <strong class="{{ ! $hasIdrPrice && ! $hasSgdPrice ? 'product-detail-booking-card__price-empty' : '' }}">
            @if($hasIdrPrice)
                Rp {{ number_format($idrPrice, 0, ',', '.') }}
            @elseif($hasSgdPrice)
                SGD {{ number_format($sgdPrice, 0) }}
            @else
                {{ $emptyLabel }}
            @endif
        </strong>
    </div>

    @if($hasIdrPrice && $hasSgdPrice)
        <p class="product-detail-booking-card__secondary-price">
            SGD {{ number_format($sgdPrice, 0) }}
        </p>
    @endif
@else
    <div class="bp-product-card__price">
        <span>{{ $label }}</span>
        <strong class="{{ ! $hasIdrPrice && ! $hasSgdPrice ? 'bp-product-card__price-empty' : '' }}">
            @if($hasIdrPrice)
                Rp {{ number_format($idrPrice, 0, ',', '.') }}
            @elseif($hasSgdPrice)
                SGD {{ number_format($sgdPrice, 0) }}
            @else
                {{ $emptyLabel }}
            @endif
        </strong>

        @if($hasIdrPrice && $hasSgdPrice)
            <small class="bp-product-card__price-secondary">
                SGD {{ number_format($sgdPrice, 0) }}
            </small>
        @endif
    </div>
@endif
