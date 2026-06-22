{{-- ══════════════════════════════════════════════════════
     TOP BAR — fixed height (flex-none); never scrolls.
     Controls: back, page info/status, panel toggles, device toggles, preview, save.
══════════════════════════════════════════════════════ --}}
<header class="flex min-h-14 shrink-0 flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-slate-800 bg-slate-900 px-3 py-2 sm:px-4">

    {{-- Left: back + page info --}}
    <div class="flex min-w-0 items-center gap-3">
        <a
            href="{{ route('admin.pages.edit', $page) }}"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-700 text-slate-400 transition-colors hover:border-slate-500 hover:text-white"
            title="Back to Edit"
        >
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ $page->title }}</p>
            <p class="truncate text-xs text-slate-500">/pages/{{ $page->slug }}</p>
        </div>

        @if($page->isPublished())
            <span class="shrink-0 rounded-full bg-emerald-900/60 px-2 py-0.5 text-xs font-medium text-emerald-400">Published</span>
        @elseif($page->isScheduled())
            <span class="shrink-0 rounded-full bg-amber-900/60 px-2 py-0.5 text-xs font-medium text-amber-400">Scheduled</span>
        @else
            <span class="shrink-0 rounded-full bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-300">Draft</span>
        @endif
    </div>

    {{-- Right: action buttons --}}
    <div class="flex shrink-0 items-center gap-2">

        {{-- Save error --}}
        <span
            x-show="saveError"
            x-text="saveError"
            class="text-xs text-red-400"
            x-cloak
        ></span>

        {{-- Unsaved indicator --}}
        <span
            x-show="isDirty && !isSaving"
            class="text-xs text-amber-400"
            x-cloak
        >Unsaved changes</span>

        {{-- Panel minimize toggles (optional — widen the preview) --}}
        <div class="flex items-center gap-0.5 rounded-lg border border-slate-700 p-0.5">
            <button
                type="button"
                x-on:click="leftCollapsed = !leftCollapsed"
                :class="leftCollapsed ? 'text-slate-500 hover:text-slate-300' : 'bg-slate-700 text-white'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Toggle blocks panel"
            ><i class="fa-solid fa-table-columns"></i></button>
            <button
                type="button"
                x-on:click="rightCollapsed = !rightCollapsed"
                :class="rightCollapsed ? 'text-slate-500 hover:text-slate-300' : 'bg-slate-700 text-white'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Toggle settings panel"
            ><i class="fa-solid fa-sliders"></i></button>
        </div>

        {{-- Device preview toggles — change ONLY the iframe render width, not the grid --}}
        <div class="flex items-center gap-0.5 rounded-lg border border-slate-700 p-0.5">
            <button
                type="button"
                x-on:click="previewMode = 'desktop'"
                :class="previewMode === 'desktop' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Desktop (fluid)"
            ><i class="fa-solid fa-desktop"></i></button>
            <button
                type="button"
                x-on:click="previewMode = 'tablet'"
                :class="previewMode === 'tablet' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Tablet (768px)"
            ><i class="fa-solid fa-tablet-screen-button"></i></button>
            <button
                type="button"
                x-on:click="previewMode = 'mobile'"
                :class="previewMode === 'mobile' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Mobile (375px)"
            ><i class="fa-solid fa-mobile-screen-button"></i></button>
        </div>

        {{-- Preview in new tab --}}
        <a
            href="{{ route('admin.pages.preview', $page) }}"
            target="_blank"
            class="flex h-8 items-center gap-1.5 rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-300 transition-colors hover:border-slate-500 hover:text-white"
        >
            <i class="fa-solid fa-eye text-xs"></i>
            Preview
        </a>

        {{-- Save Draft --}}
        <button
            type="button"
            x-on:click="saveTree()"
            :disabled="isSaving"
            class="flex h-8 items-center gap-1.5 rounded-lg border border-amber-600 bg-amber-600/20 px-3 text-xs font-semibold text-amber-300 transition-colors hover:bg-amber-600/40 disabled:opacity-50"
        >
            <i class="fa-solid fa-floppy-disk text-xs" x-show="!isSaving"></i>
            <i class="fa-solid fa-spinner fa-spin text-xs" x-show="isSaving" x-cloak></i>
            <span x-text="isSaving ? 'Saving…' : 'Save'"></span>
        </button>

    </div>
</header>
