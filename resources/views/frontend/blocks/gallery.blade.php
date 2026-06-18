@php
    $data = $block->data ?? [];

    // Resolve every image src to a full URL, drop empties.
    $images = collect($data['images'] ?? [])
        ->map(function ($img) {
            $src = $img['src'] ?? $img['url'] ?? '';
            $img['src'] = ($src && ! str_starts_with($src, 'http'))
                ? asset('storage/' . $src)
                : $src;
            return $img;
        })
        ->filter(fn ($img) => ! empty($img['src']))
        ->values();

    $columns  = max(1, min(4, (int) ($data['columns'] ?? 3)));   // images per row = one slide
    $gap      = $data['gap'] ?? 'md';
    $aspect   = $data['aspect_ratio'] ?? 'auto';
    $captions = (bool) ($data['show_captions'] ?? false);
    $lightbox = (bool) ($data['lightbox_enabled'] ?? true);
    $autoplay = (bool) ($data['autoplay'] ?? false);
    $globalCaption = $data['caption'] ?? '';

    $colClass = match ($columns) {
        1       => 'grid-cols-1',
        2       => 'grid-cols-2',
        4       => 'grid-cols-2 md:grid-cols-4',
        default => 'grid-cols-2 md:grid-cols-3',
    };
    $gapClass = match ($gap) {
        'sm'    => 'gap-2',
        'lg'    => 'gap-6',
        default => 'gap-4',
    };
    $aspectClass = match ($aspect) {
        'square' => 'aspect-square',
        '16-9'   => 'aspect-video',
        '4-3'    => 'aspect-[4/3]',
        default  => '',
    };
    $imgClass = $aspectClass ? 'h-full w-full object-cover' : 'w-full';

    // Each slide holds one full row; chunk() preserves global keys for the lightbox index.
    $slides     = $images->chunk($columns);
    $slideCount = $slides->count();
    $isCarousel = $slideCount > 1;

    // Background styling (shared pattern with other blocks).
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($images->isNotEmpty())
<section class="py-12" style="{{ $bgStyle }}">
    {{-- Self-contained Alpine component (no external script dependency) --}}
    <div
        class="mx-auto max-w-7xl px-6"
        x-data="{
            images: {{ Js::from($images->all()) }},
            slideCount: {{ $slideCount }},
            autoplay: {{ $autoplay && $isCarousel ? 'true' : 'false' }},
            lightbox: {{ $lightbox ? 'true' : 'false' }},
            slide: 0,
            lbOpen: false,
            lbIdx: 0,
            _sx: 0, _lsx: 0, _timer: null,
            init() {
                if (this.autoplay && this.slideCount > 1) {
                    this._timer = setInterval(() => { if (!this.lbOpen) this.next(); }, 5000);
                }
            },
            next() { this.slide = (this.slide + 1) % this.slideCount; },
            prev() { this.slide = (this.slide - 1 + this.slideCount) % this.slideCount; },
            go(i) { this.slide = i; },
            swipeStart(e) { this._sx = e.touches[0].clientX; },
            swipeEnd(e) { const d = this._sx - e.changedTouches[0].clientX; if (d > 50) this.next(); if (d < -50) this.prev(); },
            openLb(i) { if (!this.lightbox) return; this.lbIdx = i; this.lbOpen = true; document.body.style.overflow = 'hidden'; },
            closeLb() { this.lbOpen = false; document.body.style.overflow = ''; },
            lbPrev() { this.lbIdx = (this.lbIdx - 1 + this.images.length) % this.images.length; },
            lbNext() { this.lbIdx = (this.lbIdx + 1) % this.images.length; },
            onKey(e) { if (!this.lbOpen) return; if (e.key === 'ArrowLeft') this.lbPrev(); if (e.key === 'ArrowRight') this.lbNext(); if (e.key === 'Escape') this.closeLb(); },
            lbSwipeStart(e) { this._lsx = e.touches[0].clientX; },
            lbSwipeEnd(e) { const d = this._lsx - e.changedTouches[0].clientX; if (d > 50) this.lbNext(); if (d < -50) this.lbPrev(); }
        }"
        @keydown.window="onKey"
    >
        <div class="relative">

            @if($isCarousel)
                <button type="button" @click="prev()"
                        class="absolute -left-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center
                               rounded-full bg-white/90 text-slate-700 shadow-md ring-1 ring-slate-200
                               transition hover:bg-white md:-left-4"
                        aria-label="Previous images">
                    <i class="fa-solid fa-chevron-left text-sm"></i>
                </button>
                <button type="button" @click="next()"
                        class="absolute -right-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center
                               rounded-full bg-white/90 text-slate-700 shadow-md ring-1 ring-slate-200
                               transition hover:bg-white md:-right-4"
                        aria-label="Next images">
                    <i class="fa-solid fa-chevron-right text-sm"></i>
                </button>
            @endif

            {{-- Slides --}}
            @foreach($slides as $s => $slide)
                <div
                    @if($isCarousel)
                        x-show="slide === {{ $s }}"
                        x-transition:enter="transition-opacity duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        @touchstart.passive="swipeStart"
                        @touchend.passive="swipeEnd"
                        @if(! $loop->first) style="display:none" @endif
                    @endif
                    class="grid {{ $colClass }} {{ $gapClass }}"
                >
                    @foreach($slide as $gi => $img)
                        <figure
                            class="group relative overflow-hidden rounded-xl {{ $aspectClass }} {{ $lightbox ? 'cursor-pointer' : '' }}"
                            @if($lightbox) @click="openLb({{ $gi }})" @endif
                        >
                            <img
                                src="{{ $img['src'] }}"
                                alt="{{ $img['alt'] ?? '' }}"
                                class="{{ $imgClass }} transition duration-500 group-hover:scale-105"
                                loading="lazy"
                            >
                            @if($captions && ! empty($img['caption']))
                                <figcaption class="absolute inset-x-0 bottom-0 bg-black/60 px-3 py-2 text-xs
                                                   text-white opacity-0 transition-opacity group-hover:opacity-100">
                                    {{ $img['caption'] }}
                                </figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Dots --}}
        @if($isCarousel)
            <div class="mt-5 flex items-center justify-center gap-2">
                <template x-for="i in slideCount" :key="i">
                    <button type="button" @click="go(i - 1)"
                            :class="slide === (i - 1) ? 'w-6 bg-slate-800' : 'w-2 bg-slate-300 hover:bg-slate-400'"
                            class="h-2 rounded-full transition-all"
                            :aria-label="'Go to slide ' + i"></button>
                </template>
            </div>
        @endif

        {{-- Global caption --}}
        @if($globalCaption)
            <p class="mt-4 text-center text-sm text-slate-500">{{ $globalCaption }}</p>
        @endif

        {{-- Lightbox --}}
        @if($lightbox)
            <div
                x-show="lbOpen"
                x-transition:enter="transition duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-150"
                x-transition:leave-end="opacity-0"
                @click.self="closeLb"
                @touchstart.passive="lbSwipeStart"
                @touchend.passive="lbSwipeEnd"
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/90"
                style="display:none"
            >
                <button @click="closeLb"
                        class="absolute right-4 top-4 z-10 grid h-10 w-10 place-items-center rounded-full
                               bg-white/10 text-white transition hover:bg-white/25"
                        aria-label="Close">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <button @click="lbPrev"
                        class="absolute left-4 top-1/2 z-10 grid h-12 w-12 -translate-y-1/2 place-items-center
                               rounded-full bg-white/10 text-white transition hover:bg-white/25"
                        aria-label="Previous">
                    <i class="fa-solid fa-chevron-left text-xl"></i>
                </button>

                <div class="flex max-h-[85vh] max-w-[85vw] flex-col items-center px-16">
                    <template x-for="(img, i) in images" :key="i">
                        <div x-show="lbIdx === i" class="text-center">
                            <img :src="img.src" :alt="img.alt || ''"
                                 class="max-h-[78vh] max-w-full rounded-lg object-contain">
                            <p x-show="img.caption" x-text="img.caption" class="mt-3 text-sm text-white/70"></p>
                        </div>
                    </template>
                </div>

                <button @click="lbNext"
                        class="absolute right-4 top-1/2 z-10 grid h-12 w-12 -translate-y-1/2 place-items-center
                               rounded-full bg-white/10 text-white transition hover:bg-white/25"
                        aria-label="Next">
                    <i class="fa-solid fa-chevron-right text-xl"></i>
                </button>

                <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                    <template x-for="(img, i) in images" :key="i">
                        <button @click="lbIdx = i"
                                :class="lbIdx === i ? 'scale-125 bg-white' : 'bg-white/40'"
                                class="h-2 w-2 rounded-full transition-all"></button>
                    </template>
                </div>
            </div>
        @endif
    </div>
</section>
@endif
