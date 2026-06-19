@php
    $data      = $block->data ?? [];
    $products  = $block->resolvedProducts ?? collect();
    $showPrice = (bool)($data['show_price'] ?? true);

    // More than one row → swipeable carousel; otherwise a plain grid.
    $isCarousel = $products->count() > 3;

    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($products->isNotEmpty())
    {{-- Hide the carousel scrollbar without depending on a Tailwind rebuild. --}}
    @once
        <style>
            .bp-pcarousel { scrollbar-width: none; -ms-overflow-style: none; }
            .bp-pcarousel::-webkit-scrollbar { display: none; }
        </style>
    @endonce

    <section class="py-16" style="{{ $bgStyle }}">
        <div class="mx-auto max-w-7xl px-6">

            @if($isCarousel)
                <div
                    x-data="{
                        atStart: true,
                        atEnd: false,
                        sync() {
                            const t = this.$refs.track;
                            this.atStart = t.scrollLeft <= 4;
                            this.atEnd   = t.scrollLeft + t.clientWidth >= t.scrollWidth - 4;
                        },
                        page(dir) {
                            const t = this.$refs.track;
                            t.scrollBy({ left: dir * t.clientWidth * 0.9, behavior: 'smooth' });
                        }
                    }"
                    x-init="$nextTick(() => sync())"
                    class="relative"
                >
                    {{-- Prev --}}
                    <button type="button" @click="page(-1)" x-show="!atStart"
                            class="absolute -left-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center
                                   rounded-full bg-white/95 text-slate-700 shadow-md ring-1 ring-slate-200
                                   transition hover:bg-white md:-left-4"
                            aria-label="Previous products" x-cloak>
                        <i class="fa-solid fa-chevron-left text-sm"></i>
                    </button>
                    {{-- Next --}}
                    <button type="button" @click="page(1)" x-show="!atEnd"
                            class="absolute -right-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center
                                   rounded-full bg-white/95 text-slate-700 shadow-md ring-1 ring-slate-200
                                   transition hover:bg-white md:-right-4"
                            aria-label="Next products" x-cloak>
                        <i class="fa-solid fa-chevron-right text-sm"></i>
                    </button>

                    {{-- Track: native horizontal swipe + scroll-snap --}}
                    <div
                        x-ref="track"
                        @scroll.passive.debounce.50ms="sync()"
                        class="bp-pcarousel flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth pb-2"
                    >
                        @foreach($products as $product)
                            <div class="w-4/5 shrink-0 snap-start sm:w-1/2 lg:w-1/3">
                                @include('frontend.components.product-card', [
                                    'product'   => $product,
                                    'showPrice' => $showPrice,
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($products as $product)
                        @include('frontend.components.product-card', [
                            'product'   => $product,
                            'showPrice' => $showPrice,
                        ])
                    @endforeach
                </div>
            @endif

        </div>
    </section>
@endif
