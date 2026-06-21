@php
    $data = $block->data ?? [];
    $buttons = collect($data['buttons'] ?? [])->filter(fn ($button) => ! empty($button['text']) && ! empty($button['url']));
    $alignment = match ($data['alignment'] ?? 'left') {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($buttons->isNotEmpty())
<section class="px-6 py-8" style="{{ $bgStyle }}" aria-label="Action links">
    <div class="mx-auto flex max-w-7xl flex-wrap gap-3 {{ $alignment }}">
        @foreach($buttons as $button)
            @php
                $buttonClass = match ($button['style'] ?? 'primary') {
                    'secondary' => 'border border-[var(--frontend-charcoal,#17130c)] bg-transparent text-[var(--frontend-charcoal,#17130c)] hover:bg-[var(--frontend-charcoal,#17130c)] hover:text-white',
                    'link' => 'px-2 underline decoration-[var(--frontend-gold,#c8a24a)] decoration-2 underline-offset-4 hover:opacity-70',
                    default => 'bg-[var(--frontend-gold,#c8a24a)] text-[var(--frontend-black,#090806)] hover:opacity-90',
                };
            @endphp
            <a href="{{ $button['url'] }}" class="inline-flex min-h-11 items-center justify-center rounded-full px-6 py-3 text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-[var(--frontend-gold,#c8a24a)] focus:ring-offset-2 {{ $buttonClass }}">
                {{ $button['text'] }}
            </a>
        @endforeach
    </div>
</section>
@endif
