@php
    $sectionModel = $section ?? null;
    $mediaItems = collect($mediaItems ?? $sectionModel?->activeMedia ?? [])
        ->take($limit ?? \App\Models\PageSection::MEDIA_LIMIT);
    $wrapperClass = $wrapperClass ?? 'section-media-slider';
    $slideClass = $slideClass ?? 'section-media-slider__slide';
    $imageAlt = $imageAlt ?? $sectionModel?->title ?? 'Section image';
@endphp

@if($mediaItems->count())
    <div class="{{ $wrapperClass }}" aria-hidden="true">
        @foreach($mediaItems as $media)
            <div class="{{ $slideClass }} {{ $loop->first ? 'is-active' : '' }}" data-section-slide>
                <img src="{{ $media->url }}" alt="{{ $media->alt ?: $imageAlt }}" decoding="async" @if(! $loop->first) loading="lazy" @endif>
            </div>
        @endforeach
    </div>
@endif
