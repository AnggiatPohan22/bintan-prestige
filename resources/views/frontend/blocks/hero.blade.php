@php
    $data       = $block->data ?? [];
    $title      = $data['title'] ?? '';
    $sub        = $data['subtitle'] ?? '';
    $img        = $data['image'] ?? '';
    $ctaTxt     = $data['cta_text'] ?? '';
    $ctaUrl     = $data['cta_url'] ?? '';
    $bgColor    = $data['background_color'] ?? '#0f0f0f';
    $overlayPct = (int) ($data['overlay_opacity'] ?? 40);
    $overlayDec = $overlayPct / 100;
    $hasOverlay = filter_var($data['has_overlay'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $minHeight  = match ($data['min_height'] ?? 'large') {
        'small'  => '400px',
        'medium' => '600px',
        default  => 'clamp(520px, 72vh, 760px)',
    };
    $imgUrl = $img ? (str_starts_with($img, 'http') ? $img : asset('storage/' . $img)) : '';

    // Section inline style
    $sectionStyle = "position:relative;overflow:hidden;min-height:{$minHeight};background-color:{$bgColor};display:flex;align-items:flex-end;";
    if ($imgUrl) {
        $sectionStyle .= "background-image:url('{$imgUrl}');background-size:cover;background-position:center;background-repeat:no-repeat;";
    }
@endphp

@if($title || $imgUrl)
<section
    class="bp-block-hero"
    style="{{ $sectionStyle }}"
    aria-label="{{ $title ?: 'Hero section' }}"
>
    {{-- Gradient overlay --}}
    @if($hasOverlay)
        <div
            aria-hidden="true"
            style="position:absolute;inset:0;pointer-events:none;z-index:1;background:linear-gradient(180deg, rgba(0,0,0,{{ round($overlayDec * 0.14, 2) }}) 0%, rgba(0,0,0,{{ $overlayDec }}) 100%), linear-gradient(90deg, rgba(0,0,0,{{ round($overlayDec * 0.56, 2) }}) 0%, rgba(0,0,0,0) 52%, rgba(0,0,0,{{ round($overlayDec * 0.46, 2) }}) 100%);"
        ></div>
    @endif

    {{-- Content --}}
    <div
        class="relative mx-auto w-full max-w-7xl px-4 pb-20 pt-36 sm:px-6 sm:pb-24 sm:pt-44 lg:px-8 lg:pb-28"
        style="z-index:2;"
    >
        <div class="max-w-3xl">

            @if($title)
                <h1
                    class="text-4xl font-extrabold uppercase leading-tight tracking-tight sm:text-5xl lg:text-7xl"
                    style="color:rgba(255,255,255,0.92);text-shadow:0 24px 80px rgba(0,0,0,0.34);"
                >
                    {{ $title }}
                </h1>
            @endif

            @if($sub)
                <p
                    class="mt-5 max-w-2xl text-base font-semibold leading-7 sm:text-xl lg:text-2xl"
                    style="color:rgba(255,255,255,0.78);"
                >
                    {{ $sub }}
                </p>
            @endif

            @if($ctaTxt && $ctaUrl)
                <div class="mt-8">
                    <a
                        href="{{ $ctaUrl }}"
                        class="inline-block rounded-full px-10 py-4 text-sm font-bold uppercase tracking-widest transition hover:-translate-y-0.5 hover:shadow-lg"
                        style="background:var(--frontend-gold,#c8a24a);color:var(--frontend-black,#090806);"
                    >
                        {{ $ctaTxt }}
                    </a>
                </div>
            @endif

        </div>
    </div>
</section>
@endif
