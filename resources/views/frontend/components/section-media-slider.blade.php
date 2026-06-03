@props([
    'mediaItems' => collect(),
    'wrapperClass' => 'section-media-slider',
    'slideClass' => 'section-media-slider__slide',
    'imageAlt' => 'Section image',
])

@php
    $items = collect($mediaItems)->filter(fn ($media) => filled($media->url ?? null))->values();
@endphp

@if($items->count())
    <div class="{{ $wrapperClass }}" aria-hidden="true">
        @foreach($items as $index => $media)
            <div class="{{ $slideClass }} {{ $index === 0 ? 'is-active' : '' }}" data-section-slide>
                <img
                    src="{{ $media->url }}"
                    alt="{{ $media->alt ?: $imageAlt }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                >
            </div>
        @endforeach
    </div>
@endif
