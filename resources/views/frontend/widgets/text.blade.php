@php
    $data    = $widget->data ?? [];
    $heading = $data['heading'] ?? '';
    $content = $data['content'] ?? '';
@endphp

@if($heading || $content)
    <div class="widget widget--text">
        @if($heading)
            <h3 class="widget-heading">{{ $heading }}</h3>
        @endif
        @if($content)
            <div class="widget-content">{!! \App\Support\InlineContentSanitizer::richtext($content) !!}</div>
        @endif
    </div>
@endif
