@php
    $data = $block->data ?? [];
    $children = $block->relationLoaded('children') ? $block->children : collect();
    $width = $data['width'] ?? 'wide';
    $widthClass = match ($width) {
        'contained' => 'max-w-5xl',
        'full' => 'max-w-none',
        default => 'max-w-7xl',
    };
    $padX = $width === 'full' ? '' : 'px-4 sm:px-6';
    $columnCount = max(2, min(4, (int) ($data['columns'] ?? 2)));
    $gridClass = match ($columnCount) {
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
        default => 'lg:grid-cols-2',
    };
    $mobileClass = (bool) ($data['stack_mobile'] ?? true) ? 'grid-cols-1' : 'grid-cols-2';
    $gapClass = match ($data['gap'] ?? 'md') {
        'none' => 'gap-0',
        'sm' => 'gap-3',
        'lg' => 'gap-8',
        default => 'gap-6',
    };
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
    $adv = \App\Support\BlockStyle::advanced($data, $block->id);
@endphp

@if($children->isNotEmpty())
@if($adv['css'])<style>{!! $adv['css'] !!}</style>@endif
<section
    @if($adv['id']) id="{{ $adv['id'] }}" @endif
    class="{{ $padX }} py-8 {{ $adv['classes'] }}"
    style="{{ $bgStyle }}{{ $adv['style'] }}"
    aria-label="{{ $block->label ?: 'Columns' }}"
>
    <div class="mx-auto grid {{ $widthClass }} {{ $mobileClass }} {{ $gridClass }} {{ $gapClass }}">
        @foreach($children as $child)
            <div class="min-w-0">
                @include('frontend.pages._blocks', ['blocks' => collect([$child])])
            </div>
        @endforeach
    </div>
</section>
@endif
