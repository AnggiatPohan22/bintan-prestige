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
                class="block h-full w-full rounded border-0 bg-white shadow-2xl"
            ></iframe>
        </div>
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

</div>{{-- /center panel --}}
