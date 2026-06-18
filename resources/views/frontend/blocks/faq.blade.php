@php
    use App\Models\Faq;

    $data   = $block->data ?? [];
    $source = $data['source'] ?? 'inline';

    if ($source === 'ids' && ! empty($data['faq_ids'])) {
        $ids   = is_array($data['faq_ids']) ? $data['faq_ids'] : explode(',', $data['faq_ids']);
        $items = Faq::whereIn('id', $ids)->where('is_active', true)->get()
            ->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])
            ->toArray();
    } else {
        $items = array_filter($data['items'] ?? [], fn ($i) => ! empty($i['question']));
    }
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if(count($items))
<section class="py-16" style="{{ $bgStyle }}">
    <div class="mx-auto max-w-3xl px-6">
        <div class="space-y-4" x-data="{ open: null }">
            @foreach($items as $i => $item)
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-6 py-4 text-left font-semibold text-slate-800"
                        x-on:click="open = open === {{ $i }} ? null : {{ $i }}"
                        :aria-expanded="open === {{ $i }}"
                    >
                        <span>{{ $item['question'] }}</span>
                        <i
                            class="fa-solid fa-chevron-down ml-4 shrink-0 text-slate-400 transition-transform"
                            :class="open === {{ $i }} ? 'rotate-180' : ''"
                            aria-hidden="true"
                        ></i>
                    </button>

                    <div
                        x-show="open === {{ $i }}"
                        x-cloak
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
