@php
    $data = $block->data ?? [];
    $children = $block->relationLoaded('children') ? $block->children : collect();
    $width = $data['width'] ?? 'contained';
    $widthClass = match ($width) {
        'wide' => 'max-w-7xl',
        'full' => 'max-w-none',
        default => 'max-w-5xl',
    };
    // Full width = truly edge-to-edge (no horizontal padding).
    $padX = $width === 'full' ? '' : 'px-4 sm:px-6';
    $spacingClass = match ($data['spacing'] ?? 'md') {
        'none' => 'py-0',
        'sm' => 'py-4',
        'lg' => 'py-16',
        default => 'py-8',
    };
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
    // Advanced tab: margin/padding/z-index/id/classes/hide/custom-css (all optional).
    $adv = \App\Support\BlockStyle::advanced($data, $block->id);
@endphp

@if($children->isNotEmpty())
@if($adv['css'])<style>{!! $adv['css'] !!}</style>@endif
<section
    @if($adv['id']) id="{{ $adv['id'] }}" @endif
    class="{{ $padX }} {{ $spacingClass }} {{ $adv['classes'] }}"
    style="{{ $bgStyle }}{{ $adv['style'] }}"
    aria-label="{{ $block->label ?: 'Content group' }}"
>
    <div class="mx-auto {{ $widthClass }}">
        @include('frontend.pages._blocks', ['blocks' => $children])
    </div>
</section>
@endif
