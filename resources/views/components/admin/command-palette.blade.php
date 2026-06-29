@php
$commands = [
    ['label' => 'Dashboard',          'url' => route('admin.dashboard'),                  'icon' => 'fa-chart-pie',            'group' => 'Workspace'],
    ['label' => 'Pages',              'url' => route('admin.pages.index'),                'icon' => 'fa-file-lines',           'group' => 'Content'],
    ['label' => 'Create Page',        'url' => route('admin.pages.create'),               'icon' => 'fa-file-circle-plus',     'group' => 'Content'],
    ['label' => 'Products',           'url' => route('admin.products.index'),             'icon' => 'fa-suitcase-rolling',     'group' => 'Content'],
    ['label' => 'Create Product',     'url' => route('admin.products.create'),            'icon' => 'fa-plus-circle',          'group' => 'Content'],
    ['label' => 'Categories',         'url' => route('admin.categories.index'),           'icon' => 'fa-tags',                 'group' => 'Content'],
    ['label' => 'Destinations',       'url' => route('admin.destinations.index'),         'icon' => 'fa-location-dot',         'group' => 'Content'],
    ['label' => 'Media Library',      'url' => route('admin.media.index'),                'icon' => 'fa-photo-film',           'group' => 'Content'],
    ['label' => 'FAQs',               'url' => route('admin.faqs.index'),                 'icon' => 'fa-circle-question',      'group' => 'Content'],
    ['label' => 'Themes',             'url' => route('admin.themes.index'),               'icon' => 'fa-palette',              'group' => 'Design'],
    ['label' => 'Menus',              'url' => route('admin.menus.index'),                'icon' => 'fa-bars-staggered',       'group' => 'Design'],
    ['label' => 'Page Sections',      'url' => route('admin.page-sections.index'),        'icon' => 'fa-layer-group',          'group' => 'Design'],
    ['label' => 'Contact Forms',      'url' => route('admin.forms.index'),                'icon' => 'fa-envelope-open-text',   'group' => 'Forms'],
    ['label' => 'Redirects',          'url' => route('admin.seo.redirects.index'),        'icon' => 'fa-arrow-right-arrow-left','group' => 'SEO'],
    ['label' => 'Robots.txt',         'url' => route('admin.seo.robots.edit'),            'icon' => 'fa-robot',                'group' => 'SEO'],
    ['label' => 'Analytics',          'url' => route('admin.analytics.index'),            'icon' => 'fa-chart-line',           'group' => 'Analytics'],
    ['label' => 'Plugins',            'url' => route('admin.plugins.index'),              'icon' => 'fa-puzzle-piece',         'group' => 'System'],
    ['label' => 'Audit Log',          'url' => route('admin.audit-logs.index'),           'icon' => 'fa-shield-halved',        'group' => 'System'],
    ['label' => 'Global Settings',    'url' => route('admin.settings.global-assets.edit'),'icon' => 'fa-sliders',             'group' => 'Settings'],
];
@endphp

<div
    x-data="adminCommandPalette({{ Js::from($commands) }})"
    x-on:keydown.ctrl.k.window.prevent="open()"
    x-on:keydown.meta.k.window.prevent="open()"
    x-on:keydown.escape.window="close()"
>
    {{-- Backdrop + modal --}}
    <div
        x-cloak
        x-show="isOpen"
        class="fixed inset-0 z-[200] flex items-start justify-center px-4 pt-24"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Overlay --}}
        <div
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            x-on:click="close()"
        ></div>

        {{-- Panel --}}
        <div
            class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl border border-admin bg-admin-card shadow-2xl shadow-slate-900/20"
        >
            {{-- Search input --}}
            <div class="flex items-center gap-3 border-b border-admin px-4 py-3">
                <i class="fa-solid fa-magnifying-glass text-admin-secondary" aria-hidden="true"></i>
                <input
                    type="text"
                    class="flex-1 border-0 bg-transparent p-0 text-sm font-semibold text-admin-primary placeholder:text-admin-secondary focus:ring-0 focus:outline-none"
                    placeholder="Search admin pages..."
                    x-ref="searchInput"
                    x-model="query"
                    x-on:input="filter()"
                    x-on:keydown.arrow-down.prevent="moveDown()"
                    x-on:keydown.arrow-up.prevent="moveUp()"
                    x-on:keydown.enter.prevent="go()"
                >
                <kbd class="rounded-lg border border-admin bg-admin-card px-2 py-1 text-[10px] font-black uppercase text-admin-secondary">Esc</kbd>
            </div>

            {{-- Results --}}
            <div class="max-h-80 overflow-y-auto py-2" x-ref="results">
                <template x-if="results.length === 0">
                    <p class="px-5 py-4 text-sm text-admin-secondary">No results for "<span x-text="query"></span>"</p>
                </template>

                <template x-for="(item, index) in results" :key="item.url">
                    <a
                        :href="item.url"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm transition"
                        :class="index === cursor ? 'bg-admin-surface text-admin-primary' : 'text-admin-primary hover:bg-admin-card'"
                        x-on:mouseenter="cursor = index"
                        x-on:click="close()"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs"
                            :class="index === cursor ? 'bg-admin-surface text-admin-secondary' : 'bg-admin-card text-admin-secondary'"
                        >
                            <i :class="'fa-solid ' + item.icon" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="font-semibold" x-text="item.label"></span>
                            <span class="ml-2 text-xs text-admin-secondary" x-text="item.group"></span>
                        </span>
                    </a>
                </template>
            </div>

            {{-- Footer hint --}}
            <div class="flex items-center gap-4 border-t border-admin px-4 py-2.5 text-[11px] font-semibold text-admin-secondary">
                <span><kbd class="rounded bg-admin-card px-1.5 py-0.5 font-mono">↑↓</kbd> navigate</span>
                <span><kbd class="rounded bg-admin-card px-1.5 py-0.5 font-mono">↵</kbd> go</span>
                <span><kbd class="rounded bg-admin-card px-1.5 py-0.5 font-mono">Esc</kbd> close</span>
                <span class="ml-auto"><kbd class="rounded bg-admin-card px-1.5 py-0.5 font-mono">Ctrl K</kbd> open</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminCommandPalette', (commands) => ({
        isOpen: false,
        query: '',
        results: [],
        cursor: 0,

        init() {
            this.results = commands;
        },

        open() {
            this.isOpen = true;
            this.query = '';
            this.results = commands;
            this.cursor = 0;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        close() {
            this.isOpen = false;
            this.query = '';
        },

        filter() {
            const q = this.query.toLowerCase().trim();
            this.results = q
                ? commands.filter(c =>
                    c.label.toLowerCase().includes(q) ||
                    c.group.toLowerCase().includes(q)
                  )
                : commands;
            this.cursor = 0;
        },

        moveDown() {
            if (this.cursor < this.results.length - 1) this.cursor++;
        },

        moveUp() {
            if (this.cursor > 0) this.cursor--;
        },

        go() {
            const item = this.results[this.cursor];
            if (item) {
                window.location.href = item.url;
                this.close();
            }
        },
    }));
});
</script>
