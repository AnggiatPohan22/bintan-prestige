@php
    $data = $block->data ?? [];
    $heading = trim((string) ($data['heading'] ?? ''));
    $items = collect($data['items'] ?? [])->filter(fn ($item) => ! empty($item['value']) || ! empty($item['label']));
    $alignment = ($data['alignment'] ?? 'center') === 'left' ? 'text-left' : 'text-center';
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($heading !== '' || $items->isNotEmpty())
<section class="px-6 py-12 sm:py-16" style="{{ $bgStyle }}" @if($heading !== '') aria-labelledby="stats-heading-{{ $block->id }}" @else aria-label="Key statistics" @endif>
    <div class="mx-auto max-w-7xl">
        @if($heading !== '')
            <h2 id="stats-heading-{{ $block->id }}" class="text-3xl font-bold tracking-tight {{ $alignment }}">{{ $heading }}</h2>
        @endif
        @if($items->isNotEmpty())
            <dl class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($items as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $alignment }}">
                        @if(! empty($item['value']))<dd class="text-4xl font-bold text-[var(--frontend-gold,#c8a24a)]">{{ $item['value'] }}</dd>@endif
                        @if(! empty($item['label']))<dt class="mt-2 font-semibold">{{ $item['label'] }}</dt>@endif
                        @if(! empty($item['description']))<dd class="mt-2 text-sm text-slate-600">{{ $item['description'] }}</dd>@endif
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</section>
@endif
