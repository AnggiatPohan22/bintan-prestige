@php
    $context = $context ?? 'home';
    $label = $label ?? 'Start from';
    $emptyLabel = $emptyLabel ?? 'Price on request';
    $priceState = $priceState ?? null;
    $idrPrice = $priceState['idr_amount'] ?? $product->idr_price;
    $sgdPrice = $priceState['sgd_amount'] ?? $product->sgd_price;
    $hasIdrPrice = $priceState['has_idr'] ?? ($idrPrice !== null);
    $hasSgdPrice = $priceState['has_sgd'] ?? ($sgdPrice !== null);
    $idrFormatted = $priceState['idr_formatted'] ?? ($hasIdrPrice ? 'Rp ' . number_format($idrPrice, 0, ',', '.') : null);
    $sgdFormatted = $priceState['sgd_formatted'] ?? ($hasSgdPrice ? 'SGD ' . number_format($sgdPrice, 0) : null);
    $secondaryFormatted = $priceState['secondary_formatted'] ?? ($hasIdrPrice && $hasSgdPrice ? $sgdFormatted : null);
    $idrAccessibleLabel = 'Indonesian Rupiah';
    $sgdAccessibleLabel = 'Singapore Dollar';
@endphp

@if($context === 'listing')
    <div class="product-card__price">
        <p class="product-card__price-main {{ ! $hasIdrPrice && ! $hasSgdPrice ? 'product-card__price-main--empty' : '' }}">
            @if($hasIdrPrice)
                <span class="sr-only">Price starts from {{ $idrAccessibleLabel }} </span>
                {{ $idrFormatted }}
            @elseif($hasSgdPrice)
                <span class="sr-only">Price starts from {{ $sgdAccessibleLabel }} </span>
                {{ $sgdFormatted }}
            @else
                {{ $emptyLabel }}
            @endif
        </p>

        @if($hasIdrPrice && $hasSgdPrice)
            <p class="product-card__price-secondary">
                <span class="sr-only">Secondary price {{ $sgdAccessibleLabel }} </span>
                {{ $secondaryFormatted }}
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
                <span class="sr-only">{{ $label }} {{ $idrAccessibleLabel }} </span>
                {{ $idrFormatted }}
            @elseif($hasSgdPrice)
                <span class="sr-only">{{ $label }} {{ $sgdAccessibleLabel }} </span>
                {{ $sgdFormatted }}
            @else
                {{ $emptyLabel }}
            @endif
        </p>

        @if($hasIdrPrice && $hasSgdPrice)
            <p class="product-detail-price-card__secondary">
                <span class="sr-only">Secondary price {{ $sgdAccessibleLabel }} </span>
                {{ $secondaryFormatted }}
            </p>
        @endif
    </div>
@elseif($context === 'booking')
    <div class="product-detail-booking-card__price-row">
        <span>{{ $label }}</span>
        <strong class="{{ ! $hasIdrPrice && ! $hasSgdPrice ? 'product-detail-booking-card__price-empty' : '' }}">
            @if($hasIdrPrice)
                <span class="sr-only">{{ $label }} {{ $idrAccessibleLabel }} </span>
                {{ $idrFormatted }}
            @elseif($hasSgdPrice)
                <span class="sr-only">{{ $label }} {{ $sgdAccessibleLabel }} </span>
                {{ $sgdFormatted }}
            @else
                {{ $emptyLabel }}
            @endif
        </strong>
    </div>

    @if($hasIdrPrice && $hasSgdPrice)
        <p class="product-detail-booking-card__secondary-price">
            <span class="sr-only">Secondary price {{ $sgdAccessibleLabel }} </span>
            {{ $secondaryFormatted }}
        </p>
    @endif
@else
    <div class="bp-product-card__price">
        <span>{{ $label }}</span>
        <strong class="{{ ! $hasIdrPrice && ! $hasSgdPrice ? 'bp-product-card__price-empty' : '' }}">
            @if($hasIdrPrice)
                <span class="sr-only">{{ $label }} {{ $idrAccessibleLabel }} </span>
                {{ $idrFormatted }}
            @elseif($hasSgdPrice)
                <span class="sr-only">{{ $label }} {{ $sgdAccessibleLabel }} </span>
                {{ $sgdFormatted }}
            @else
                {{ $emptyLabel }}
            @endif
        </strong>

        @if($hasIdrPrice && $hasSgdPrice)
            <small class="bp-product-card__price-secondary">
                <span class="sr-only">Secondary price {{ $sgdAccessibleLabel }} </span>
                {{ $secondaryFormatted }}
            </small>
        @endif
    </div>
@endif
