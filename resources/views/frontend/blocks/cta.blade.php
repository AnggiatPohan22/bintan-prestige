@php
    $data   = $block->data ?? [];
    $title  = $data['title'] ?? '';
    $desc   = $data['description'] ?? '';
    $btnTxt = $data['button_text'] ?? '';
    $btnUrl = $data['button_url'] ?? '';
    $style  = $data['style'] ?? 'dark';

    $bgClass  = match($style) {
        'light' => 'bg-white text-slate-900',
        'gold'  => 'bg-[var(--frontend-gold,#c8a24a)] text-[var(--frontend-black,#090806)]',
        default => 'bg-slate-900 text-white',
    };
    $btnClass = match($style) {
        'light' => 'bg-slate-900 text-white hover:bg-slate-700',
        'gold'  => 'bg-[var(--frontend-black,#090806)] text-white hover:opacity-90',
        default => 'bg-yellow-500 text-black hover:bg-yellow-400',
    };
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; $bgClass = preg_replace('/bg-\[[^\]]+\]|bg-\S+/', '', $bgClass); }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($title || $desc || ($btnTxt && $btnUrl))
<section
    class="{{ $bgClass }} py-14 sm:py-20"
    style="{{ $bgStyle }}"
    @if($title) aria-labelledby="cta-title-{{ $block->id }}" @else aria-label="Call to action" @endif
>
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
        @if($title)
            <h2 id="cta-title-{{ $block->id }}" class="text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        @endif

        @if($desc)
            <p class="mx-auto mt-4 max-w-xl text-lg opacity-80">{{ $desc }}</p>
        @endif

        @if($btnTxt && $btnUrl)
            <div class="mt-8">
                <a
                    href="{{ $btnUrl }}"
                    class="inline-flex max-w-full justify-center break-words rounded-full px-6 py-4 text-sm font-bold uppercase tracking-widest transition focus:outline-none focus:ring-2 focus:ring-offset-2 sm:px-10 {{ $btnClass }}"
                >
                    {{ $btnTxt }}
                </a>
            </div>
        @endif
    </div>
</section>
@endif
