{{-- ── CENTER PANEL: Live Preview (device width, internal scroll) ───
     Fills the space between the panels (flex-1 → 60% when both panels open).
     The iframe is the only scroll container: it holds the full page at the
     selected device WIDTH (desktop fluid / tablet 768 / mobile 375) and fills
     the visible canvas HEIGHT via CSS (h-full). Device mode applies a plain
     max-width on the wrapper — NO transform/scale and NO JS measurement — so
     the rendered site is identical regardless of panel width (requirement B).
──────────────────────────────────────────────────────── --}}
<div class="relative flex min-w-0 flex-1 flex-col overflow-hidden">

    {{-- Canvas viewport: clips and centers the device frame. It does not scroll;
         the iframe inside scrolls its own content. --}}
    <div
        x-ref="canvas"
        class="flex min-h-0 flex-1 justify-center overflow-hidden bg-slate-800 p-4"
    >
        {{-- Device wrapper: width-capped + centered. h-full fills the visible area. --}}
        <div
            class="mx-auto h-full w-full"
            :style="'max-width:' + deviceMaxWidth()"
        >
            {{-- The iframe renders at its own CSS width and is its own scroll
                 container, so the page scrolls naturally to the footer. --}}
            <iframe
                id="builder-preview"
                title="Page preview"
                sandbox="allow-same-origin allow-scripts allow-forms"
                class="block h-full w-full rounded border-0 bg-white shadow-2xl"
            ></iframe>
        </div>
    </div>

    {{-- B7: Canvas status bar — shows active device mode and viewport width. --}}
    <div class="flex h-7 shrink-0 items-center justify-center gap-2 border-t border-slate-700/50 bg-slate-900/80 text-xs text-slate-500">
        <i class="fa-solid fa-desktop"              x-show="previewMode === 'desktop'"></i>
        <i class="fa-solid fa-tablet-screen-button" x-show="previewMode === 'tablet'"  x-cloak></i>
        <i class="fa-solid fa-mobile-screen-button" x-show="previewMode === 'mobile'"  x-cloak></i>
        <span x-text="previewModeLabel()"></span>
    </div>

    {{-- Preview loading overlay — pinned to the visible canvas area --}}
    <div
        x-show="isRefreshing"
        x-transition.opacity
        class="absolute inset-0 z-10 flex items-center justify-center bg-slate-950/60"
        x-cloak
    >
        <div class="flex items-center gap-2 rounded-full bg-slate-800 px-4 py-2 text-xs text-slate-300 shadow-xl">
            <i class="fa-solid fa-spinner fa-spin"></i>
            Refreshing preview…
        </div>
    </div>

    {{-- Empty state --}}
    <div
        x-show="tree.length === 0 && !isRefreshing"
        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 text-slate-600"
        x-cloak
    >
        <i class="fa-solid fa-layer-group text-5xl"></i>
        <p class="text-sm">Add a block from the left panel to get started.</p>
    </div>

    {{-- Preview fetch error bar --}}
    <div
        x-show="previewError"
        class="absolute inset-x-0 bottom-0 z-20 flex items-center gap-3 bg-red-950/90 px-4 py-3 text-xs text-red-300 shadow-xl"
        x-cloak
    >
        <i class="fa-solid fa-triangle-exclamation shrink-0 text-red-400"></i>
        <span x-text="previewError" class="min-w-0 flex-1 truncate"></span>
        <button
            type="button"
            x-on:click="previewError = null"
            class="shrink-0 text-red-500 hover:text-red-300"
            title="Dismiss"
        ><i class="fa-solid fa-xmark"></i></button>
    </div>

    {{-- B4 rich-text toolbar. Coordinates are calculated from the iframe Selection API. --}}
    <div
        x-show="inlineToolbar.visible"
        x-cloak
        class="fixed z-50 flex -translate-x-1/2 items-center gap-1 rounded-lg border border-slate-600 bg-slate-900 p-1 text-slate-200 shadow-2xl"
        :style="`left:${inlineToolbar.left}px;top:${inlineToolbar.top}px`"
        x-on:mousedown.prevent
    >
        <button type="button" class="rounded px-2 py-1 text-xs font-bold hover:bg-slate-700" title="Bold" x-on:click="formatInline('bold')">B</button>
        <button type="button" class="rounded px-2 py-1 text-xs italic hover:bg-slate-700" title="Italic" x-on:click="formatInline('italic')">I</button>
        <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-700" title="Link" x-on:click="formatInline('link')"><i class="fa-solid fa-link"></i></button>
        <button type="button" class="rounded px-2 py-1 text-xs hover:bg-slate-700" title="Clear formatting" x-on:click="formatInline('clear')"><i class="fa-solid fa-eraser"></i></button>
    </div>

</div>{{-- /center panel --}}
