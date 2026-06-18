@php
    $data    = $block->data ?? [];
    $heading = $data['heading'] ?? '';
    $body    = $data['body_html'] ?? '';
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($heading || $body)
<section class="py-16" style="{{ $bgStyle }}">
    <div class="mx-auto max-w-3xl px-6">
        @if($heading)
            <h2 class="mb-6 text-3xl font-bold text-slate-900">
                {{ $heading }}
            </h2>
        @endif

        @if($body)
            <div class="prose prose-slate max-w-none">
                {!! $body !!}
            </div>
        @endif
    </div>
</section>
@endif
