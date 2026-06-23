@php
    $data = $block->data ?? [];
    $url = $data['url'] ?? '';
    $title = $data['title'] ?? 'Embedded video';
    $caption = $data['caption'] ?? '';
    $aspectClass = match ($data['aspect_ratio'] ?? '16-9') {
        '4-3' => 'aspect-[4/3]',
        '1-1' => 'aspect-square',
        default => 'aspect-video',
    };
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($url)
<section class="px-4 py-10 sm:px-6 sm:py-12" style="{{ $bgStyle }}">
    <figure class="mx-auto max-w-5xl">
        <div class="{{ $aspectClass }} overflow-hidden rounded-2xl bg-black shadow-lg">
            <iframe
                src="{{ $url }}"
                title="{{ $title }}"
                class="h-full w-full"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
        </div>
        @if($caption)
            <figcaption class="mt-3 text-center text-sm text-slate-500">{{ $caption }}</figcaption>
        @endif
    </figure>
</section>
@endif
