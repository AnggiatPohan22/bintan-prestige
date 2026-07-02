{{-- ══════════════════════════════════════════════════════
     TOP BAR (entry builder) — back, entry info/status, panel + device toggles, save.
     No template library (entries use the string `template` column) and no external
     preview link (entry frontend route arrives at B11–B12); the live iframe preview
     still works via the previewUrl in the builder config.
══════════════════════════════════════════════════════ --}}
<header class="flex min-h-14 shrink-0 flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-admin bg-admin-card px-3 py-2 sm:px-4">

    {{-- Left: back + entry info --}}
    <div class="flex min-w-0 items-center gap-3">
        <a
            href="{{ route('admin.content-types.entries.edit', [$contentType, $entry]) }}"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-admin text-admin-secondary transition-colors hover:border-admin hover:text-white"
            title="Back to Edit"
        >
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ $entry->title ?? 'Untitled' }}</p>
            <p class="truncate text-xs text-admin-secondary">{{ $contentType->label_singular }}</p>
        </div>

        @if($entry->isPublished())
            <span class="shrink-0 rounded-full bg-emerald-900/60 px-2 py-0.5 text-xs font-medium text-emerald-400">Published</span>
        @elseif($entry->isScheduled())
            <span class="shrink-0 rounded-full bg-amber-900/60 px-2 py-0.5 text-xs font-medium text-amber-400">Scheduled</span>
        @else
            <span class="shrink-0 rounded-full bg-admin-card px-2 py-0.5 text-xs font-medium text-admin-secondary">Draft</span>
        @endif
    </div>

    {{-- Right: action buttons --}}
    <div class="flex shrink-0 items-center gap-2">

        <span x-show="saveError" x-text="saveError" class="text-xs text-red-400" x-cloak></span>
        <span x-show="isDirty && !isSaving" class="text-xs text-amber-400" x-cloak>Unsaved changes</span>

        {{-- Panel minimize toggles --}}
        <div class="flex items-center gap-0.5 rounded-lg border border-admin p-0.5">
            <button
                type="button"
                x-on:click="leftCollapsed = !leftCollapsed"
                :class="leftCollapsed ? 'text-admin-secondary hover:text-admin-secondary' : 'bg-admin-surface text-white'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Toggle blocks panel"
            ><i class="fa-solid fa-table-columns"></i></button>
            <button
                type="button"
                x-on:click="rightCollapsed = !rightCollapsed"
                :class="rightCollapsed ? 'text-admin-secondary hover:text-admin-secondary' : 'bg-admin-surface text-white'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Toggle settings panel"
            ><i class="fa-solid fa-sliders"></i></button>
        </div>

        {{-- Device preview toggles --}}
        <div class="flex items-center gap-0.5 rounded-lg border border-admin p-0.5">
            <button
                type="button"
                x-on:click="previewMode = 'desktop'"
                :class="previewMode === 'desktop' ? 'bg-admin-surface text-white' : 'text-admin-secondary hover:text-admin-secondary'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Desktop (fluid)"
            ><i class="fa-solid fa-desktop"></i></button>
            <button
                type="button"
                x-on:click="previewMode = 'tablet'"
                :class="previewMode === 'tablet' ? 'bg-admin-surface text-white' : 'text-admin-secondary hover:text-admin-secondary'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Tablet (768px)"
            ><i class="fa-solid fa-tablet-screen-button"></i></button>
            <button
                type="button"
                x-on:click="previewMode = 'mobile'"
                :class="previewMode === 'mobile' ? 'bg-admin-surface text-white' : 'text-admin-secondary hover:text-admin-secondary'"
                class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                title="Mobile (375px)"
            ><i class="fa-solid fa-mobile-screen-button"></i></button>
        </div>

        {{-- Save --}}
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
