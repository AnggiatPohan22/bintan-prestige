@php
    $data    = $block->data ?? [];
    $items   = $block->resolvedFaqItems ?? [];
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if(count($items))
<section class="py-12 sm:py-16" style="{{ $bgStyle }}" aria-label="Frequently asked questions">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <div class="space-y-4" x-data="{ open: null }">
            @foreach($items as $i => $item)
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-6 py-4 text-left font-semibold focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--frontend-gold,#c8a24a)]"
                        x-on:click="open = open === {{ $i }} ? null : {{ $i }}"
                        :aria-expanded="(open === {{ $i }}).toString()"
                        aria-controls="faq-answer-{{ $block->id }}-{{ $i }}"
                        id="faq-question-{{ $block->id }}-{{ $i }}"
                    >
                        <span>{{ $item['question'] }}</span>
                        <i
                            class="fa-solid fa-chevron-down ml-4 shrink-0 text-slate-400 transition-transform"
                            :class="open === {{ $i }} ? 'rotate-180' : ''"
                            aria-hidden="true"
                        ></i>
                    </button>

                    <div
                        id="faq-answer-{{ $block->id }}-{{ $i }}"
                        x-show="open === {{ $i }}"
                        x-cloak
                        role="region"
                        aria-labelledby="faq-question-{{ $block->id }}-{{ $i }}"
                        class="border-t border-slate-100 px-6 py-4 text-sm text-slate-600"
                    >
                        {{ $item['answer'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
