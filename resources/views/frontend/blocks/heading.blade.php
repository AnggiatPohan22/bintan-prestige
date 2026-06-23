@php
    $data = $block->data ?? [];
    $builderInline = (bool) ($builderCanvas ?? false);
    $builderOrder = abs((int) $block->id);
    $text = trim((string) ($data['text'] ?? ''));
    $requestedLevel = $data['level'] ?? 'h2';
    $level = in_array($requestedLevel, ['h2', 'h3', 'h4', 'h5', 'h6'], true) ? $requestedLevel : 'h2';
    $alignment = match ($data['alignment'] ?? 'left') {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    };
    $size = match ($level) {
        'h2' => 'text-3xl sm:text-4xl',
        'h3' => 'text-2xl sm:text-3xl',
        'h4' => 'text-xl sm:text-2xl',
        default => 'text-lg sm:text-xl',
    };
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($text !== '' || $builderInline)
<section class="px-6 py-8 sm:py-10" style="{{ $bgStyle }}" @if($builderInline) data-builder-block-order="{{ $builderOrder }}" @endif>
    <div class="mx-auto max-w-7xl {{ $alignment }}">
        @if($level === 'h2')
            <h2 class="font-bold tracking-tight {{ $size }}" @if($builderInline) contenteditable="true" data-inline-field="text" data-edit-type="plaintext" data-placeholder="Section heading" @endif>{{ $text }}</h2>
        @elseif($level === 'h3')
            <h3 class="font-bold tracking-tight {{ $size }}" @if($builderInline) contenteditable="true" data-inline-field="text" data-edit-type="plaintext" data-placeholder="Section heading" @endif>{{ $text }}</h3>
        @elseif($level === 'h4')
            <h4 class="font-bold tracking-tight {{ $size }}" @if($builderInline) contenteditable="true" data-inline-field="text" data-edit-type="plaintext" data-placeholder="Section heading" @endif>{{ $text }}</h4>
        @elseif($level === 'h5')
            <h5 class="font-bold tracking-tight {{ $size }}" @if($builderInline) contenteditable="true" data-inline-field="text" data-edit-type="plaintext" data-placeholder="Section heading" @endif>{{ $text }}</h5>
        @else
            <h6 class="font-bold tracking-tight {{ $size }}" @if($builderInline) contenteditable="true" data-inline-field="text" data-edit-type="plaintext" data-placeholder="Section heading" @endif>{{ $text }}</h6>
        @endif
    </div>
</section>
@endif
