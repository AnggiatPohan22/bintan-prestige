@php
    $section = $sections['home.popular_products_intro'] ?? null;
    $sectionContent = ($homepageContent ?? [])['popular_products'] ?? [];
@endphp

<section class="bp-product-section" id="home-popular-products" data-section-key="home.popular_products_intro" aria-labelledby="popular-products-title">
    <div class="home-container">
        <div class="bp-product-section__header">
            <span class="bp-product-section__label">
                {{ $section?->label ?? 'Most Popular Tour Packages' }}
            </span>

            <h2 id="popular-products-title" class="bp-product-section__title title-section">
                {{ $section?->title ?? 'Something Amazing Waiting For You' }}
            </h2>
        </div>

        <div class="bp-product-filterbar">
            <div class="bp-product-tabs" role="tablist" aria-label="Filter popular products">
                <button
                    type="button"
                    class="bp-product-tabs__button is-active"
                    data-product-filter="all"
                    aria-pressed="true"
                >
                    All
                </button>

                @foreach($homeProductCategories as $category)
                    <button
                        type="button"
                        class="bp-product-tabs__button"
                        data-product-filter="{{ $category->id }}"
                        aria-pressed="false"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <div class="bp-product-actions">
                <a href="{{ $sectionContent['view_all_url'] ?? route('products.index') }}" class="btn btn-primary bp-product-view-all">
                   <span>{{ $sectionContent['view_all_text'] ?? 'View All Package' }}</span>
                    <svg class="bp-product-view-all__icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17L17 7"></path>
                        <path d="M9 7h8v8"></path>
                    </svg>
                </a>

                <div class="bp-product-pagination" data-product-pagination hidden>
                    <button
                        type="button"
                        class="btn btn-icon carousel-arrow"
                        data-product-prev
                        aria-label="Previous packages"
                    >
                        <svg class="carousel-arrow__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>

                    <span class="bp-product-pagination__status" data-product-page-status>
                        1 / 1
                    </span>

                    <button
                        type="button"
                        class="btn btn-icon carousel-arrow"
                        data-product-next
                        aria-label="Next packages"
                    >
                        <svg class="carousel-arrow__icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        @if($homeProducts->count())
            <div class="bp-product-grid" data-product-grid data-products-per-page="8">
                @foreach($homeProducts as $product)
                    @include('frontend.components.product-card', [
                        'product' => $product
                    ])
                @endforeach
            </div>
        @else
            <div class="product-empty">
                <h3 class="product-empty__title">
                    {{ $sectionContent['empty_title'] ?? 'Products coming soon' }}
                </h3>

                @if(filled($sectionContent['empty_text'] ?? null))
                    <p class="product-empty__text">
                        {{ $sectionContent['empty_text'] }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</section>
