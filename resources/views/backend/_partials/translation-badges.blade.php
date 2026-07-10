{{--
    Phase 7 (B8) — Translation status badges for a row-per-locale record
    (Page / ContentEntry). Emits one small pill per active locale:
       ● hijau       = a published sibling exists in that locale
       ● kuning      = sibling exists but is not published (draft/scheduled/archived)
       ○ abu-abu    = no sibling row in that locale
    "$record" and (optionally) "$siblings" (pre-loaded collection) are the inputs.
--}}
@php
    use App\Support\Locales;
    $__locales = Locales::active();
    if (count($__locales) < 2) {
        return; // switcher is hidden when only one locale exists
    }
    $__siblings = ($siblings ?? null) ?: $record->translationSiblings()->get(['id', 'locale', 'status']);
    $__byLocale = $__siblings->keyBy('locale');
@endphp

<div class="flex flex-wrap items-center gap-1" title="Translation status per locale">
    @foreach($__locales as $__code => $__meta)
        @php
            $__sib   = $__byLocale->get($__code);
            $__state = 'missing';
            if ($__sib) {
                $__state = $__sib->status === 'published' ? 'published' : 'draft';
            }
            $__title = strtoupper($__code)
                . ' — ' . ($__state === 'published' ? 'Published' : ($__state === 'draft' ? 'Draft/unpublished' : 'Not translated'));
            $__classes = match ($__state) {
                'published' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
                'draft'     => 'bg-amber-100 text-amber-700 border-amber-300',
                default     => 'bg-slate-100 text-slate-500 border-slate-200',
            };
            $__dot = match ($__state) {
                'published' => 'bg-emerald-500',
                'draft'     => 'bg-amber-500',
                default     => 'bg-slate-300',
            };
        @endphp
        <span class="inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $__classes }}"
              title="{{ $__title }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $__dot }}"></span>
            {{ $__code }}
        </span>
    @endforeach
</div>
