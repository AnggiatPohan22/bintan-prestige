@php
    $data    = $widget->data ?? [];
    $src     = $data['src'] ?? '';
    $alt     = $data['alt'] ?? '';
    $linkUrl = $data['link_url'] ?? '';
    $caption = $data['caption'] ?? '';
@endphp

@if($src)
    <figure class="widget widget--image">
        @if($linkUrl)
            <a href="{{ $linkUrl }}">
                <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="widget-image">
            </a>
        @else
            <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="widget-image">
        @endif

        @if($caption)
            <figcaption class="widget-caption">{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
