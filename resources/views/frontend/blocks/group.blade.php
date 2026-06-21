@php
    $data = $block->data ?? [];
    $children = $block->relationLoaded('children') ? $block->children : collect();
    $widthClass = match ($data['width'] ?? 'contained') {
        'wide' => 'max-w-7xl',
        'full' => 'max-w-none',
        default => 'max-w-5xl',
    };
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
@endphp

@if($children->isNotEmpty())
<section class="px-4 sm:px-6 {{ $spacingClass }}" style="{{ $bgStyle }}" aria-label="{{ $block->label ?: 'Content group' }}">
    <div class="mx-auto {{ $widthClass }}">
        @include('frontend.pages._blocks', ['blocks' => $children])
    </div>
</section>
@endif
