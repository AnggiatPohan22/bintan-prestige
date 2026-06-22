{{-- ── RIGHT PANEL: Block Settings (B3 scope) ──────────────
     lg+: in-flow grid column at 20% (lg:w-[20%]). Below lg: fixed overlay
     drawer from the right. Width is FIXED on desktop.
──────────────────────────────────────────────────────── --}}
<aside
    x-show="!rightCollapsed"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-x-full lg:translate-x-0 lg:opacity-0"
    x-transition:enter-end="translate-x-0 lg:opacity-100"
    class="absolute inset-y-0 right-0 z-40 flex w-80 max-w-[85vw] shrink-0 flex-col border-l border-slate-800 bg-slate-900 shadow-2xl lg:static lg:z-auto lg:w-[20%] lg:max-w-none lg:shadow-none"
    x-cloak
>

    <div class="shrink-0 border-b border-slate-800 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Block Settings</p>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center text-slate-600">
        <i class="fa-solid fa-sliders text-4xl"></i>
        <p class="text-sm">
            Block settings panel coming in <strong class="text-slate-500">Phase 5 — B3</strong>.
        </p>
        <p class="text-xs text-slate-700">
            Use the form-based editor on the Edit page for detailed block configuration.
        </p>
        <a
            href="{{ route('admin.pages.edit', $page) }}#blocks"
            class="mt-2 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-400 transition-colors hover:border-slate-500 hover:text-white"
        >
            <i class="fa-solid fa-pen mr-1 text-xs"></i>
            Open Form Editor
        </a>
    </div>

</aside>
