@php
    $data    = $widget->data ?? [];
    $heading = $data['heading'] ?? '';
    $links   = collect($data['links'] ?? [])->filter(fn ($l) => filled($l['label'] ?? '') || filled($l['url'] ?? ''));
@endphp

@if($links->isNotEmpty())
    <nav class="widget widget--navigation" aria-label="{{ $heading ?: 'Navigation' }}">
        @if($heading)
            <h3 class="widget-heading">{{ $heading }}</h3>
        @endif
        <ul class="widget-nav-list">
            @foreach($links as $link)
                <li>
                    <a href="{{ $link['url'] ?? '#' }}" class="widget-nav-link">
                        {{ $link['label'] ?? '' }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
