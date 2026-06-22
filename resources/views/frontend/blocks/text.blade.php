@php
    $data    = $block->data ?? [];
    $builderInline = (bool) ($builderCanvas ?? false);
    $builderOrder = abs((int) $block->id);
    $heading = $data['heading'] ?? '';
    // Defense in depth for transient builder previews: preview validation may
    // fall back to raw data so rich HTML is sanitized again at render time.
    $body    = \App\Support\InlineContentSanitizer::richtext((string) ($data['body_html'] ?? ''));
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($heading || $body || $builderInline)
<section class="py-16" style="{{ $bgStyle }}" @if($builderInline) data-builder-block-order="{{ $builderOrder }}" @endif>
    <div class="mx-auto max-w-3xl px-6">
        @if($heading || $builderInline)
            <h2 class="mb-6 text-3xl font-bold" @if($builderInline) contenteditable="true" data-inline-field="heading" data-edit-type="plaintext" data-placeholder="Optional section heading" @endif>
                {{ $heading }}
            </h2>
        @endif

        @if($body || $builderInline)
            <div class="prose prose-slate max-w-none" @if($builderInline) contenteditable="true" data-inline-field="body_html" data-edit-type="richtext" data-placeholder="Write text here…" @endif>
                {!! $body !!}
            </div>
        @endif
    </div>
</section>
@endif
