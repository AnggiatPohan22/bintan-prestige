@php
    $data    = $block->data ?? [];
    $style   = $data['style'] ?? 'line';
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
@endphp

@if($style === 'space')
    <div class="py-8" aria-hidden="true" style="{{ $bgStyle }}"></div>
@elseif($style === 'gold-line')
    <div class="py-8" aria-hidden="true" style="{{ $bgStyle }}">
        <div class="mx-auto w-24 border-t-2 border-yellow-500"></div>
    </div>
@else
    <div class="py-8" aria-hidden="true" style="{{ $bgStyle }}">
        <hr class="border-slate-200">
    </div>
@endif
