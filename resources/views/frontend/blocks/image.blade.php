@php
    $data    = $block->data ?? [];
    $src     = $data['src'] ?? '';
    $alt     = $data['alt'] ?? '';
    $caption = $data['caption'] ?? '';
    $width   = $data['width_class'] ?? 'full';
    $imgUrl  = $src ? (str_starts_with($src, 'http') ? $src : asset('storage/' . $src)) : '';
    $widthClass = match($width) {
        'half'  => 'max-w-2xl',
        'third' => 'max-w-lg',
        default => 'max-w-5xl',
    };
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($imgUrl)
<section class="py-12" style="{{ $bgStyle }}">
    <figure class="mx-auto px-6 {{ $widthClass }}">
        <img
            src="{{ $imgUrl }}"
            alt="{{ $alt }}"
            class="w-full rounded-2xl object-cover shadow-lg"
            loading="lazy"
        >
        @if($caption)
            <figcaption class="mt-3 text-center text-sm text-slate-500">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
</section>
@endif
