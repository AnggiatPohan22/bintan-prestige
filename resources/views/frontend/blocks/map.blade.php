@php
    $data      = $block->data ?? [];
    $embedUrl  = $data['embed_url'] ?? '';
    $address   = $data['address'] ?? '';
    $bg        = $data['background'] ?? [];
    $bgStyle   = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($embedUrl)
<section class="py-12" style="{{ $bgStyle }}">
    <div class="mx-auto max-w-5xl px-6">
        @if($address)
            <p class="mb-4 text-center text-sm text-slate-500">
                <i class="fa-solid fa-location-dot mr-1 text-yellow-500"></i>
                {{ $address }}
            </p>
        @endif

        <div class="overflow-hidden rounded-2xl shadow-lg">
            <iframe
                src="{{ $embedUrl }}"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="{{ $address ?: 'Location map' }}"
            ></iframe>
        </div>
    </div>
</section>
@endif
