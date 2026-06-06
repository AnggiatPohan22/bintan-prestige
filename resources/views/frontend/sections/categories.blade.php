@php
    $section = $sections['home.categories_intro'] ?? null;
    $destinationPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'destination');
    $destinationPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'destination');
@endphp

<section class="bp-category-section" id="home-categories" data-section-key="home.categories_intro" aria-labelledby="categories-title">
    <div class="home-container">
        <div class="bp-category-header">
            <span class="bp-category-header__label">
                {{ $section?->label ?? 'Next Adventure Destination' }}
            </span>

            <h2 id="categories-title" class="bp-category-header__title title-section">
                {{ $section?->title ?? 'Popular Travel Categories Available In Bintan' }}
            </h2>

            <p class="bp-category-header__text text-muted">
                {{ $section?->description ?? 'Explore Bintan by travel style and discover curated packages that match your journey.' }}
            </p>
        </div>

        @if($categories->count())
            <div class="bp-category-grid">
                @foreach($categories->take(4) as $category)
                    @php
                        $packageCount = (int) ($category->products_count ?? 0);
                        $packageLabel = str_pad($packageCount, 2, '0', STR_PAD_LEFT) . ' ' . \Illuminate\Support\Str::plural('Package', $packageCount);
                    @endphp

                    <a href="{{ route('products.index', ['category' => [$category->id]]) }}" class="bp-category-card">
                        <div class="bp-category-image-frame">
                            @if($destinationPlaceholder?->url)
                                <img src="{{ $destinationPlaceholder->url }}" alt="{{ $destinationPlaceholder->alt ?: 'Destination placeholder image' }}" style="object-fit: {{ $destinationPlaceholderFit }}" loading="lazy" decoding="async">
                            @else
                                <div class="bp-category-placeholder">
                                    Category Image
                                </div>
                            @endif

                            <span class="bp-category-count-badge">
                                {{ $packageLabel }}
                            </span>
                        </div>

                        <h3 class="bp-category-name title-card">
                            {{ $category->name }}
                        </h3>
                    </a>
                @endforeach
            </div>
        @else
            <div class="product-empty">
                <h3 class="product-empty__title">
                    No categories available yet.
                </h3>
            </div>
        @endif
    </div>
</section>
