@php
    $entity = $entityContext['entity'] ?? [];
    $breadcrumbs = collect($entityContext['breadcrumbs'] ?? []);
    $descriptionState = $entityContext['descriptionState'] ?? [];
    $media = $entityContext['media'] ?? null;
    $isDestination = ($entity['type'] ?? null) === 'destination';
@endphp

<section
    class="product-entity"
    data-entity-type="{{ $entity['type'] ?? 'listing' }}"
    aria-labelledby="product-entity-title"
>
    <nav class="product-breadcrumb product-entity__breadcrumb" aria-label="Breadcrumb">
        <ol class="product-breadcrumb__list">
            @foreach($breadcrumbs as $item)
                <li class="product-breadcrumb__item">
                    @if(! $loop->first)
                        <span class="product-breadcrumb__separator" aria-hidden="true">/</span>
                    @endif

                    @if(! empty($item['url']) && empty($item['current']))
                        <a href="{{ $item['url'] }}" class="product-breadcrumb__link">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="product-breadcrumb__current" @if(! empty($item['current'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="product-entity__layout @if($isDestination && ! empty($media['available'])) product-entity__layout--media @endif">
        <div class="product-entity__content">
            <p class="product-entity__eyebrow">
                {{ $isDestination ? 'Destination' : 'Category' }}
            </p>

            <h1 id="product-entity-title" class="product-entity__title">
                {{ $entityContext['pageTitle'] }}
            </h1>

            @if(! empty($descriptionState['has_description']))
                <p class="product-entity__description">
                    {{ $descriptionState['text'] }}
                </p>
            @endif

            <div class="product-entity__meta" aria-label="Current product context">
                <span>{{ $entityContext['productCount'] }} {{ \Illuminate\Support\Str::plural('package', $entityContext['productCount']) }}</span>
                <span>{{ $sortOptions[$sort] ?? 'Tour Terbaru' }}</span>
                <span>Price context: {{ $priceCurrency }}</span>
            </div>
        </div>

        @if($isDestination && ! empty($media['available']))
            <figure class="product-entity__media">
                <img
                    src="{{ $media['url'] }}"
                    alt="{{ $media['alt'] }}"
                    @if(! empty($media['fit'])) style="object-fit: {{ $media['fit'] }}" @endif
                    loading="eager"
                    decoding="async"
                >
            </figure>
        @endif
    </div>
</section>
