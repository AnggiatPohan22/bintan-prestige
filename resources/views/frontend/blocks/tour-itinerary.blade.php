@php
    $data = $block->data ?? [];
    $heading = trim((string) ($data['heading'] ?? ''));
    $intro = trim((string) ($data['intro'] ?? ''));
    $items = collect($data['items'] ?? [])->filter(fn ($item) => ! empty($item['title']) || ! empty($item['description']));
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($heading !== '' || $intro !== '' || $items->isNotEmpty())
<section class="px-6 py-12 sm:py-16" style="{{ $bgStyle }}" @if($heading !== '') aria-labelledby="itinerary-heading-{{ $block->id }}" @else aria-label="Tour itinerary" @endif>
    <div class="mx-auto max-w-4xl">
        @if($heading !== '')<h2 id="itinerary-heading-{{ $block->id }}" class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $heading }}</h2>@endif
        @if($intro !== '')<p class="mt-4 max-w-3xl text-lg text-slate-600">{{ $intro }}</p>@endif
        @if($items->isNotEmpty())
            <ol class="relative mt-10 border-l-2 border-[var(--frontend-gold,#c8a24a)] pl-8">
                @foreach($items as $item)
                    <li class="relative pb-10 last:pb-0">
                        <span class="absolute -left-[2.6rem] top-1 h-5 w-5 rounded-full border-4 border-white bg-[var(--frontend-gold,#c8a24a)] shadow" aria-hidden="true"></span>
                        @if(! empty($item['marker']))<p class="text-xs font-bold uppercase tracking-widest text-[var(--frontend-gold-dark,#b08735)]">{{ $item['marker'] }}</p>@endif
                        @if(! empty($item['title']))<h3 class="mt-1 text-xl font-bold">{{ $item['title'] }}</h3>@endif
                        @if(! empty($item['description']))<p class="mt-2 whitespace-pre-line text-slate-600">{{ $item['description'] }}</p>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
@endif
