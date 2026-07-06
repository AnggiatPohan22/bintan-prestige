@php
    $data  = $block->data ?? [];
    $field = $block->resolvedField ?? null;

    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($field !== null)
    <div class="mx-auto max-w-3xl px-6 py-4" style="{{ $bgStyle }}">
        <div class="content-field">
            @if($field['show_label'])
                <span class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $field['label'] }}</span>
            @endif

            @if($field['type'] === 'richtext')
                <div class="prose max-w-none text-slate-700">{!! $field['html'] !!}</div>
            @elseif($field['href'] !== '')
                <a href="{{ $field['href'] }}" @if($field['type'] === 'url') target="_blank" rel="noopener" @endif
                   class="text-indigo-600 hover:underline">{{ $field['text'] }}</a>
            @else
                <span class="text-slate-800">{{ $field['text'] }}</span>
            @endif
        </div>
    </div>
@endif
