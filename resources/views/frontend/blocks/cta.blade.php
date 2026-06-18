@php
    $data   = $block->data ?? [];
    $title  = $data['title'] ?? '';
    $desc   = $data['description'] ?? '';
    $btnTxt = $data['button_text'] ?? '';
    $btnUrl = $data['button_url'] ?? '';
    $style  = $data['style'] ?? 'dark';

    $bgClass  = match($style) {
        'light' => 'bg-white text-slate-900',
        'gold'  => 'bg-yellow-500 text-black',
        default => 'bg-slate-900 text-white',
    };
    $btnClass = match($style) {
        'light' => 'bg-slate-900 text-white hover:bg-slate-700',
        'gold'  => 'bg-black text-white hover:bg-slate-800',
        default => 'bg-yellow-500 text-black hover:bg-yellow-400',
    };
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; $bgClass = str_replace(['bg-white','bg-yellow-500','bg-slate-900'], '', $bgClass); }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($title || $btnTxt)
<section class="{{ $bgClass }} py-20" style="{{ $bgStyle }}">
    <div class="mx-auto max-w-3xl px-6 text-center">
        @if($title)
            <h2 class="text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        @endif

        @if($desc)
            <p class="mx-auto mt-4 max-w-xl text-lg opacity-80">{{ $desc }}</p>
        @endif

        @if($btnTxt && $btnUrl)
            <div class="mt-8">
                <a
                    href="{{ $btnUrl }}"
                    class="inline-block rounded-full px-10 py-4 text-sm font-bold uppercase tracking-widest transition {{ $btnClass }}"
                >
                    {{ $btnTxt }}
                </a>
            </div>
        @endif
    </div>
</section>
@endif
