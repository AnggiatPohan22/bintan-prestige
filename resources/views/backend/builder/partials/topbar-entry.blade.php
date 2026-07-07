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

        {{-- Interactive publish status — change without leaving the builder. --}}
        <div
            x-data="{
                status: @js($entry->status),
                saving: false,
                saved: false,
                async update() {
                    this.saving = true; this.saved = false;
                    try {
                        const res = await fetch(@js(route('admin.content-types.entries.builder.status', [$contentType, $entry])), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' },
                            body: JSON.stringify({ status: this.status }),
                        });
                        if (res.ok) { this.saved = true; setTimeout(() => this.saved = false, 2000); }
                    } finally { this.saving = false; }
                }
            }"
            class="flex shrink-0 items-center gap-1.5"
        >
            <select
                x-model="status"
                x-on:change="update()"
                :disabled="saving"
                class="rounded-lg border border-admin bg-admin-surface px-2 py-1 text-xs font-medium text-white focus:outline-none"
                :class="{
                    'text-emerald-400': status === 'published',
                    'text-amber-400': status === 'scheduled',
                }"
                title="Publish status"
            >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                @if($contentType->supports('scheduling'))
                    <option value="scheduled">Scheduled</option>
                @endif
                <option value="archived">Archived</option>
            </select>
            <i class="fa-solid fa-spinner fa-spin text-xs text-admin-secondary" x-show="saving" x-cloak></i>
            <i class="fa-solid fa-check text-xs text-emerald-400" x-show="saved" x-cloak></i>
        </div>
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
