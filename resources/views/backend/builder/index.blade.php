@extends('layouts.builder')

@section('builder-title', 'Builder: ' . $page->title)

@push('head')
<style>
.sortable-ghost  { opacity: .25; background: rgb(71 85 105/.4); border-radius: .5rem; }
.sortable-chosen { opacity: .85; box-shadow: 0 8px 32px rgb(0 0 0/.6); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pageBuilder', (cfg) => ({

        /* ── State ─────────────────────────────────────────── */
        tree:         [],
        isDirty:      false,
        isSaving:     false,
        saveError:    null,
        isRefreshing: false,
        previewError: null,
        activeTab:    'insert',   // 'insert' | 'tree'
        selectedCid:  null,       // _cid of the block selected in tree list
        previewMode:  'desktop',  // 'desktop' | 'tablet' | 'mobile'
        _cid:         0,
        _refreshTimer: null,

        /* ── Init ──────────────────────────────────────────── */
        init() {
            this.tree = this.tagCids(JSON.parse(JSON.stringify(cfg.tree)));
            this.$nextTick(() => this.refreshPreview());
        },

        tagCids(nodes) {
            return nodes.map(n => ({
                ...n,
                _cid:     ++this._cid,
                children: this.tagCids(n.children || []),
            }));
        },

        /* ── Preview ───────────────────────────────────────── */
        scheduleRefresh() {
            this.isDirty = true;
            clearTimeout(this._refreshTimer);
            this._refreshTimer = setTimeout(() => this.refreshPreview(), 800);
        },

        async refreshPreview() {
            this.isRefreshing = true;
            this.previewError = null;
            try {
                const res = await fetch(cfg.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': '*/*',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ blocks: this.serialize(this.tree) }),
                });
                if (!res.ok || res.redirected) {
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        try {
                            const json = await res.json();
                            this.previewError = `Preview failed: ${json.message || 'Validation error'}`;
                        } catch {
                            this.previewError = `Preview failed: HTTP ${res.status} ${res.statusText}`;
                        }
                    } else {
                        this.previewError = `Preview failed: HTTP ${res.status} ${res.statusText}`;
                    }
                } else {
                    const html = await res.text();
                    const frame = document.getElementById('builder-preview');
                    if (frame) {
                        let scrollY = 0;
                        try { scrollY = frame.contentWindow?.scrollY ?? 0; } catch {}
                        frame.srcdoc = html;
                        if (scrollY > 0) {
                            frame.addEventListener('load', () => {
                                try { frame.contentWindow?.scrollTo(0, scrollY); } catch {}
                            }, { once: true });
                        }
                    }
                }
            } catch (e) {
                this.previewError = `Preview error: ${e.message || 'Network error'}`;
            }
            this.isRefreshing = false;
        },

        serialize(nodes) {
            return nodes.map((n, i) => ({
                block_type: n.type,
                label:      n.label,
                data:       n.data || {},
                sort_order: i,
                is_visible: n.is_visible !== false,
                children:   this.serialize(n.children || []),
            }));
        },

        /* ── Save ──────────────────────────────────────────── */
        async saveTree() {
            this.isSaving  = true;
            this.saveError = null;
            try {
                const res = await fetch(cfg.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ blocks: this.serialize(this.tree) }),
                });
                const json = await res.json();
                if (json.success) {
                    this.tree    = this.tagCids(json.tree);
                    this.isDirty = false;
                } else {
                    this.saveError = json.message || 'Save failed.';
                }
            } catch {
                this.saveError = 'Network error — please try again.';
            }
            this.isSaving = false;
        },

        /* ── Block operations ──────────────────────────────── */
        addBlock(type, label) {
            const node = {
                _cid:       ++this._cid,
                id:         null,
                type,
                label,
                data:       {},
                is_visible: true,
                sort_order: this.tree.length,
                children:   [],
            };
            if (this.selectedCid !== null) {
                const idx = this.tree.findIndex(n => n._cid === this.selectedCid);
                if (idx !== -1) {
                    this.tree.splice(idx + 1, 0, node);
                } else {
                    this.tree.push(node);
                }
            } else {
                this.tree.push(node);
            }
            this.selectedCid = node._cid;
            this.scheduleRefresh();
        },

        selectBlock(cid) {
            this.selectedCid = this.selectedCid === cid ? null : cid;
        },

        onSort(cidStr, newPos) {
            const cid    = +cidStr;
            const oldPos = this.tree.findIndex(n => n._cid === cid);
            if (oldPos === -1 || oldPos === newPos) return;
            const [item] = this.tree.splice(oldPos, 1);
            this.tree.splice(newPos, 0, item);
            this.scheduleRefresh();
        },

        toggleVisible(node) {
            node.is_visible = !node.is_visible;
            this.scheduleRefresh();
        },

        moveUp(index) {
            if (index === 0) return;
            [this.tree[index - 1], this.tree[index]] = [this.tree[index], this.tree[index - 1]];
            this.tree = [...this.tree];
            this.scheduleRefresh();
        },

        moveDown(index) {
            if (index >= this.tree.length - 1) return;
            [this.tree[index], this.tree[index + 1]] = [this.tree[index + 1], this.tree[index]];
            this.tree = [...this.tree];
            this.scheduleRefresh();
        },

        removeBlock(index) {
            this.tree.splice(index, 1);
            this.tree = [...this.tree];
            if (!this.tree.some(n => n._cid === this.selectedCid)) this.selectedCid = null;
            this.scheduleRefresh();
        },

        /* ── Category label helper ─────────────────────────── */
        catLabel(cat) {
            return {
                layout:     'Layout',
                content:    'Content',
                media:      'Media',
                conversion: 'Conversion',
                travel:     'Travel',
            }[cat] || cat;
        },

        catIcon(cat) {
            return {
                layout:     'fa-table-columns',
                content:    'fa-file-lines',
                media:      'fa-image',
                conversion: 'fa-arrow-pointer',
                travel:     'fa-plane',
            }[cat] || 'fa-cube';
        },

        /* ── Preview mode ──────────────────────────────────── */
        iframeStyle() {
            // Height is NOT set here — the iframe stretches to fill the flex wrapper
            // via default align-items:stretch. Width controls the viewport simulation.
            if (this.previewMode === 'tablet') return 'width:768px;flex-shrink:0';
            if (this.previewMode === 'mobile') return 'width:375px;flex-shrink:0';
            // Desktop: fill available canvas width; guarantee ≥ 1280px so the page
            // always renders a proper desktop viewport. Canvas scrolls horizontally
            // when the panel pair is narrower than 1280px.
            return 'width:100%;min-width:1280px';
        },

        blockIcon(type) {
            const icons = {
                group: 'fa-layer-group', columns: 'fa-table-columns',
                hero: 'fa-image', heading: 'fa-heading', text: 'fa-align-left',
                image: 'fa-image', gallery: 'fa-images', video_embed: 'fa-video',
                button_group: 'fa-hand-pointer', stats: 'fa-chart-bar',
                tour_itinerary: 'fa-route', pricing_table: 'fa-tags',
                cta: 'fa-bullhorn', products_grid: 'fa-grid-2',
                faq: 'fa-circle-question', testimonials: 'fa-comment',
                map: 'fa-location-dot', divider: 'fa-minus',
                contact_form: 'fa-envelope',
            };
            return icons[type] || 'fa-cube';
        },
    }));
});
</script>
@endpush

@section('content')
@php
    $categorized = collect($registry)->groupBy('category', true);
    $catOrder    = ['layout', 'content', 'media', 'conversion', 'travel'];
@endphp

<div
    x-data="pageBuilder({
        tree:       {{ Js::from($tree) }},
        csrf:       {{ Js::from(csrf_token()) }},
        previewUrl: {{ Js::from(route('admin.pages.preview-payload', $page)) }},
        saveUrl:    {{ Js::from(route('admin.page-blocks.save-tree', $page)) }},
        registry:   {{ Js::from($registry) }},
    })"
    class="flex h-screen flex-col bg-slate-950"
>

    {{-- ══════════════════════════════════════════════════════
         TOP BAR
    ══════════════════════════════════════════════════════ --}}
    <header class="flex h-14 shrink-0 items-center justify-between border-b border-slate-800 bg-slate-900 px-4">

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

            {{-- Device preview toggles --}}
            <div class="flex items-center gap-0.5 rounded-lg border border-slate-700 p-0.5">
                <button
                    type="button"
                    x-on:click="previewMode = 'desktop'"
                    :class="previewMode === 'desktop' ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-slate-300'"
                    class="flex h-7 w-7 items-center justify-center rounded text-xs transition-colors"
                    title="Desktop (1280px+)"
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

    {{-- ══════════════════════════════════════════════════════
         THREE-PANEL CANVAS
    ══════════════════════════════════════════════════════ --}}
    <div class="flex min-h-0 flex-1 overflow-hidden">

        {{-- ── LEFT PANEL: Inserter + Tree ───────────────────── --}}
        <aside class="flex w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-900">

            {{-- Tab switcher --}}
            <div class="flex shrink-0 border-b border-slate-800">
                <button
                    type="button"
                    x-on:click="activeTab = 'insert'"
                    :class="activeTab === 'insert' ? 'border-b-2 border-amber-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                    class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold transition-colors"
                >
                    <i class="fa-solid fa-plus text-xs"></i>
                    Add Block
                </button>
                <button
                    type="button"
                    x-on:click="activeTab = 'tree'"
                    :class="activeTab === 'tree' ? 'border-b-2 border-amber-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                    class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold transition-colors"
                >
                    <i class="fa-solid fa-list text-xs"></i>
                    Block List
                    <span
                        x-text="tree.length"
                        class="rounded-full bg-slate-700 px-1.5 py-0.5 text-xs text-slate-300"
                    ></span>
                </button>
            </div>

            {{-- INSERT TAB --}}
            <div x-show="activeTab === 'insert'" class="flex-1 overflow-y-auto" x-cloak>

                {{-- Insert position context --}}
                <div class="border-b border-slate-800 px-3 py-2 text-xs text-slate-500">
                    <span x-show="selectedCid === null">Adding to end of page</span>
                    <span x-show="selectedCid !== null" x-cloak>
                        Inserting after
                        <span
                            class="font-medium text-amber-400"
                            x-text="(tree.find(n => n._cid === selectedCid) || {}).label || '…'"
                        ></span>
                        <button
                            type="button"
                            x-on:click="selectedCid = null"
                            class="ml-1 text-slate-600 hover:text-slate-300"
                            title="Reset to add at end"
                        >✕</button>
                    </span>
                </div>

                <div class="py-2">
                @foreach($catOrder as $cat)
                    @if($categorized->has($cat))
                        <div class="mb-1">
                            <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-widest text-slate-500">
                                {{ $categorized[$cat]->first() ? '' : '' }}
                                <i class="fa-solid {{ match($cat) { 'layout' => 'fa-table-columns', 'content' => 'fa-file-lines', 'media' => 'fa-image', 'conversion' => 'fa-arrow-pointer', 'travel' => 'fa-plane', default => 'fa-cube' } }} mr-1"></i>
                                {{ ucfirst($cat) }}
                            </p>
                            <div class="grid grid-cols-2 gap-1 px-2">
                                @foreach($categorized[$cat] as $type => $block)
                                    <button
                                        type="button"
                                        x-on:click="addBlock('{{ $type }}', '{{ $block['label'] }}')"
                                        class="flex flex-col items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-2 py-3 text-center transition-colors hover:border-amber-500/50 hover:bg-slate-700"
                                        title="{{ $block['description'] }}"
                                    >
                                        <i class="fa-solid {{ match($type) {
                                            'group'          => 'fa-layer-group',
                                            'columns'        => 'fa-table-columns',
                                            'hero'           => 'fa-panorama',
                                            'heading'        => 'fa-heading',
                                            'text'           => 'fa-align-left',
                                            'image'          => 'fa-image',
                                            'gallery'        => 'fa-images',
                                            'video_embed'    => 'fa-video',
                                            'button_group'   => 'fa-hand-pointer',
                                            'stats'          => 'fa-chart-bar',
                                            'tour_itinerary' => 'fa-route',
                                            'pricing_table'  => 'fa-tags',
                                            'cta'            => 'fa-bullhorn',
                                            'products_grid'  => 'fa-th-large',
                                            'faq'            => 'fa-circle-question',
                                            'testimonials'   => 'fa-comments',
                                            'map'            => 'fa-location-dot',
                                            'divider'        => 'fa-minus',
                                            'contact_form'   => 'fa-envelope',
                                            default          => 'fa-cube',
                                        } }} text-sm text-slate-400"></i>
                                        <span class="text-xs leading-tight text-slate-300">{{ $block['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
                </div>{{-- /py-2 --}}
            </div>

            {{-- TREE TAB --}}
            <div x-show="activeTab === 'tree'" class="flex-1 overflow-y-auto" x-cloak>

                <div x-show="tree.length === 0" class="p-4 text-center text-xs text-slate-500" x-cloak>
                    No blocks yet.<br>
                    Switch to <strong class="text-slate-400">Add Block</strong> to start.
                </div>

                {{-- x-sort enables drag-and-drop via @alpinejs/sort (already loaded in app.js) --}}
                <ul x-sort="onSort($item, $position)" class="py-1">
                    <template x-for="(block, index) in tree" :key="block._cid">
                        <li
                            x-sort:item="block._cid"
                            x-on:click="selectBlock(block._cid)"
                            :class="{
                                'bg-amber-900/30 border-l-2 border-amber-500': selectedCid === block._cid,
                                'border-l-2 border-transparent': selectedCid !== block._cid,
                            }"
                            class="group flex cursor-pointer items-center gap-1.5 py-2 pl-2 pr-3 transition-colors hover:bg-slate-800/60"
                        >
                            {{-- Drag handle --}}
                            <span
                                x-sort:handle
                                class="flex h-5 w-4 shrink-0 cursor-grab items-center justify-center text-slate-600 transition-colors hover:text-slate-400 active:cursor-grabbing"
                                title="Drag to reorder"
                            >
                                <i class="fa-solid fa-grip-vertical text-xs"></i>
                            </span>

                            {{-- Block icon --}}
                            <i
                                class="fa-solid fa-cube w-3.5 shrink-0 text-center text-xs"
                                :class="!block.is_visible ? 'text-slate-700' : 'text-slate-500'"
                            ></i>

                            {{-- Label --}}
                            <span
                                class="min-w-0 flex-1 truncate text-xs"
                                :class="!block.is_visible
                                    ? 'text-slate-600 line-through'
                                    : selectedCid === block._cid ? 'text-amber-300' : 'text-slate-300'"
                                x-text="block.label || block.type"
                            ></span>

                            {{-- Insert-after indicator --}}
                            <span
                                x-show="selectedCid === block._cid"
                                class="shrink-0 rounded bg-amber-800/60 px-1 py-0.5 text-xs text-amber-400"
                                title="Next block added after this one"
                            >↓</span>

                            {{-- Action buttons (visible on hover) --}}
                            <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                                <button
                                    type="button"
                                    x-on:click.stop="moveUp(index)"
                                    :disabled="index === 0"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-500 transition-colors hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                                    title="Move up"
                                >
                                    <i class="fa-solid fa-chevron-up text-xs"></i>
                                </button>
                                <button
                                    type="button"
                                    x-on:click.stop="moveDown(index)"
                                    :disabled="index === tree.length - 1"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-500 transition-colors hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                                    title="Move down"
                                >
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </button>
                                <button
                                    type="button"
                                    x-on:click.stop="toggleVisible(block)"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-500 transition-colors hover:text-white"
                                    :title="block.is_visible ? 'Hide block' : 'Show block'"
                                >
                                    <i class="fa-solid text-xs" :class="block.is_visible ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                                <button
                                    type="button"
                                    x-on:click.stop="if(confirm('Remove this block?')) removeBlock(index)"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-500 transition-colors hover:text-red-400"
                                    title="Delete block"
                                >
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>

            </div>

        </aside>

        {{-- ── CENTER PANEL: Live Preview iframe ─────────────── --}}
        {{-- Outer: overflow-hidden so absolute overlays cover only the visible canvas area,
             regardless of how far the inner canvas has scrolled. --}}
        <div class="relative min-w-0 flex-1 overflow-hidden">

            {{-- Inner: scrollable canvas.
                 overflow-x-auto  — canvas scrolls right when iframe exceeds canvas width
                                    (desktop min-width 1280px on narrow screens).
                 overflow-y-hidden — page content scrolls INSIDE the iframe, not the canvas. --}}
            <div class="absolute inset-0 overflow-x-auto overflow-y-hidden bg-slate-800">
                {{-- Wrapper: full canvas height, min-w fills canvas so justify-center
                     has a reference width for centering tablet/mobile iframes.
                     Default align-items:stretch makes the iframe fill canvas height. --}}
                <div class="flex h-full min-w-full justify-center p-4">
                    <iframe
                        id="builder-preview"
                        title="Page preview"
                        class="h-full border-0 shadow-2xl"
                        :style="iframeStyle()"
                    ></iframe>
                </div>
            </div>

            {{-- Preview loading overlay — covers visible canvas area, not the scroll content --}}
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

        {{-- ── RIGHT PANEL: Block Settings (B3 scope) ────────── --}}
        <aside class="flex w-72 shrink-0 flex-col border-l border-slate-800 bg-slate-900">

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

    </div>{{-- /three-panel --}}

</div>{{-- /x-data pageBuilder --}}

@endsection
