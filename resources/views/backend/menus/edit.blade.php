@extends('layouts.admin')

@section('content')

@php
    $rootItems = $menu->rootItems;

    $typeMeta = [
        'page'        => ['label' => 'Page',        'icon' => 'fa-file-lines'],
        'product'     => ['label' => 'Product',     'icon' => 'fa-suitcase-rolling'],
        'category'    => ['label' => 'Category',    'icon' => 'fa-tags'],
        'destination' => ['label' => 'Destination', 'icon' => 'fa-location-dot'],
        'url'         => ['label' => 'Custom URL',  'icon' => 'fa-link'],
        'anchor'      => ['label' => 'Anchor',      'icon' => 'fa-hashtag'],
    ];

    // Option lists for the slide-over target picker.
    $jsOptions = [
        'page'        => $pages->map(fn ($p) => ['id' => $p->id, 'label' => $p->title])->values(),
        'product'     => $products->map(fn ($p) => ['id' => $p->id, 'label' => $p->name])->values(),
        'category'    => $categories->map(fn ($c) => ['id' => $c->id, 'label' => $c->name])->values(),
        'destination' => $destinations->map(fn ($d) => ['id' => $d->id, 'label' => $d->name])->values(),
    ];
    $jsTypes   = collect($typeMeta)->map(fn ($m, $k) => ['value' => $k, 'label' => $m['label'], 'icon' => 'fa-solid ' . $m['icon']])->values();
    $jsParents = $rootItems->map(fn ($i) => ['id' => $i->id, 'label' => $i->label])->values();
    $isHeader  = $menu->location === 'header';
@endphp

<div
    class="admin-page"
    x-data="menuEditor({
        storeUrl: '{{ route('admin.menu-items.store', $menu) }}',
        reorderUrl: '{{ route('admin.menu-items.reorder', $menu) }}',
        csrf: '{{ csrf_token() }}',
        options: {{ Js::from($jsOptions) }},
        types: {{ Js::from($jsTypes) }},
        parents: {{ Js::from($jsParents) }},
    })"
>

    {{-- Header bar --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.menus.index') }}" class="text-slate-400 hover:text-slate-600" title="Back to Menus">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-extrabold text-slate-900">{{ $menu->name }}</h1>
                <p class="text-xs text-slate-400">
                    <span class="font-mono">{{ $menu->location }}</span>
                    · drag <i class="fa-solid fa-grip-vertical"></i> to reorder
                </p>
            </div>
        </div>
        <button type="button" x-on:click="openAdd()" class="admin-btn-primary">
            <i class="fa-solid fa-plus mr-1"></i> Add item
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">

        {{-- ============ EDITOR TREE ============ --}}
        <div>
            @if($rootItems->isEmpty())
                <div class="admin-empty-state py-12">
                    <i class="fa-solid fa-bars-staggered mb-2 text-2xl text-slate-300"></i>
                    <p class="font-medium text-slate-600">This menu is empty.</p>
                    <p class="mt-1 text-sm text-slate-400">Click <span class="font-semibold">Add item</span> to create your first link.</p>
                </div>
            @else
                <ul class="space-y-2" x-sort="persistOrder($el)" x-sort:config="{ handle: '[data-handle]', animation: 150 }">
                    @foreach($rootItems as $item)
                        <li x-sort:item="{{ $item->id }}" data-id="{{ $item->id }}"
                            class="rounded-xl border {{ $item->is_active ? 'border-slate-200 bg-white' : 'border-slate-100 bg-slate-50 opacity-70' }} shadow-sm">

                            @include('backend.menus.partials.item-row', ['item' => $item, 'typeMeta' => $typeMeta, 'isChild' => false])

                            {{-- Children --}}
                            @if($item->children->isNotEmpty())
                                <ul class="space-y-1.5 border-t border-dashed border-slate-200 px-3 py-2 pl-8"
                                    x-sort="persistOrder($el)" x-sort:config="{ handle: '[data-handle]', animation: 150 }">
                                    @foreach($item->children as $child)
                                        <li x-sort:item="{{ $child->id }}" data-id="{{ $child->id }}"
                                            class="rounded-lg border {{ $child->is_active ? 'border-slate-200 bg-white' : 'border-slate-100 bg-slate-50 opacity-70' }}">
                                            @include('backend.menus.partials.item-row', ['item' => $child, 'typeMeta' => $typeMeta, 'isChild' => true])
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- ============ LIVE PREVIEW ============ --}}
        <div class="lg:sticky lg:top-6 lg:self-start">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preview</span>
                    <i class="fa-solid fa-eye text-slate-300"></i>
                </div>

                @php $previewItems = $rootItems->where('is_active', true); @endphp

                @if($isHeader)
                    {{-- Header-style preview --}}
                    <div class="bg-slate-900 px-4 py-3">
                        @if($previewItems->isEmpty())
                            <p class="text-xs text-slate-400">No visible items.</p>
                        @else
                            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                @foreach($previewItems as $item)
                                    @php $kids = $item->children->where('is_active', true); @endphp
                                    @if($kids->isNotEmpty())
                                        <div class="group relative">
                                            <button class="flex items-center gap-1 text-sm font-medium text-white/90">
                                                {{ $item->label }}
                                                <i class="fa-solid fa-chevron-down text-[10px] opacity-70"></i>
                                            </button>
                                            <div class="absolute left-0 z-10 mt-2 hidden min-w-[160px] rounded-lg bg-white p-1 shadow-xl group-hover:block">
                                                @foreach($kids as $child)
                                                    <span class="block rounded px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ $child->label }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm font-medium text-white/90">{{ $item->label }}</span>
                                    @endif
                                @endforeach
                            </nav>
                        @endif
                    </div>
                @else
                    {{-- Footer-style preview --}}
                    <div class="px-4 py-3">
                        @if($previewItems->isEmpty())
                            <p class="text-xs text-slate-400">No visible items.</p>
                        @else
                            <ul class="space-y-1.5">
                                @foreach($previewItems as $item)
                                    <li class="text-sm text-slate-600">{{ $item->label }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
            <p class="mt-2 px-1 text-xs text-slate-400">Reflects saved items. Refreshes after add, edit, or delete.</p>
        </div>
    </div>

    {{-- ============ SLIDE-OVER DRAWER ============ --}}
    <div x-show="drawer" x-cloak class="fixed inset-0 z-[60]" style="display:none">
        <div class="absolute inset-0 bg-slate-900/40" x-on:click="close()"
             x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>

        <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl"
             x-transition:enter="transition-transform duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900" x-text="mode === 'edit' ? 'Edit menu item' : 'Add menu item'"></h2>
                <button type="button" x-on:click="close()" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close panel">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" :action="formAction" class="flex flex-1 flex-col overflow-y-auto">
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="link_type" :value="form.linkType">
                <input type="hidden" name="target" :value="form.newTab ? '_blank' : '_self'">

                <div class="flex-1 space-y-5 px-5 py-5">
                    {{-- Label --}}
                    <div>
                        <label class="admin-form-label">Label <span class="text-red-500">*</span></label>
                        <input type="text" name="label" x-model="form.label" class="admin-input" placeholder="e.g. About Us" required>
                    </div>

                    {{-- Link type picker --}}
                    <div>
                        <label class="admin-form-label">Link type</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="t in types" :key="t.value">
                                <button type="button" x-on:click="setType(t.value)"
                                        class="flex flex-col items-center gap-1 rounded-lg border px-2 py-2.5 text-xs font-medium transition"
                                        :class="form.linkType === t.value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                    <i :class="t.icon"></i>
                                    <span x-text="t.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Linkable target (searchable) --}}
                    <template x-if="isLinkable">
                        <div>
                            <label class="admin-form-label">Target <span class="text-red-500">*</span></label>
                            <input type="text" x-model="search" class="admin-input mb-2" placeholder="Search…">
                            <select name="linkable_id" x-model="form.linkableId" class="admin-input" required>
                                <option value="">— Select —</option>
                                <template x-for="opt in filteredOptions" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.label"></option>
                                </template>
                            </select>
                            <p x-show="filteredOptions.length === 0" class="mt-1 text-xs text-slate-400">No matches.</p>
                        </div>
                    </template>

                    {{-- URL / anchor --}}
                    <template x-if="needsUrl">
                        <div>
                            <label class="admin-form-label" x-text="form.linkType === 'anchor' ? 'Anchor' : 'URL'"></label>
                            <input type="text" name="url" x-model="form.url" class="admin-input"
                                   :placeholder="form.linkType === 'anchor' ? '/#section-id' : 'https://example.com'" required>
                        </div>
                    </template>

                    @if($isHeader)
                    {{-- Header supports one dropdown child level; footer locations remain flat. --}}
                    <div>
                        <label class="admin-form-label">Parent (optional)</label>
                        <select name="parent_id" x-model="form.parentId" class="admin-input">
                            <option value="">— Top level —</option>
                            <template x-for="p in parentChoices" :key="p.id">
                                <option :value="p.id" x-text="p.label"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Choose one top-level parent. Deeper nesting is not allowed.</p>
                    </div>
                    @endif

                    {{-- New tab --}}
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="form.newTab" class="rounded border-slate-300">
                        <span class="text-sm text-slate-700">Open in new tab</span>
                    </label>
                </div>

                <div class="flex gap-3 border-t border-slate-100 px-5 py-4">
                    <button type="submit" class="admin-btn-primary flex-1" x-text="mode === 'edit' ? 'Save changes' : 'Add item'"></button>
                    <button type="button" x-on:click="close()" class="admin-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast (reorder feedback) --}}
    <div x-show="toastMsg" x-cloak x-transition
         class="fixed bottom-5 left-1/2 z-[70] -translate-x-1/2 rounded-full px-4 py-2 text-sm font-medium text-white shadow-lg"
         :class="toastErr ? 'bg-red-600' : 'bg-slate-900'"
         x-text="toastMsg" style="display:none"></div>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('menuEditor', (config) => ({
            drawer: false,
            mode: 'add',
            search: '',
            form: { id: null, label: '', linkType: 'page', linkableId: '', url: '', newTab: false, parentId: '' },
            options: config.options,
            types: config.types,
            parents: config.parents,
            toastMsg: '',
            toastErr: false,
            _t: null,

            openAdd() {
                this.form = { id: null, label: '', linkType: 'page', linkableId: '', url: '', newTab: false, parentId: '' };
                this.mode = 'add';
                this.search = '';
                this.drawer = true;
            },
            openEdit(item) {
                this.form = {
                    id: item.id,
                    label: item.label || '',
                    linkType: item.linkType || 'page',
                    linkableId: item.linkableId != null ? String(item.linkableId) : '',
                    url: item.url || '',
                    newTab: !!item.newTab,
                    parentId: item.parentId != null ? String(item.parentId) : '',
                };
                this.mode = 'edit';
                this.search = '';
                this.drawer = true;
            },
            close() { this.drawer = false; },
            setType(t) { this.form.linkType = t; this.form.linkableId = ''; this.form.url = ''; this.search = ''; },

            get isLinkable() { return ['page', 'product', 'category', 'destination'].includes(this.form.linkType); },
            get needsUrl() { return ['url', 'anchor'].includes(this.form.linkType); },
            get currentOptions() { return this.options[this.form.linkType] || []; },
            get filteredOptions() {
                const q = this.search.trim().toLowerCase();
                return q ? this.currentOptions.filter((o) => o.label.toLowerCase().includes(q)) : this.currentOptions;
            },
            get parentChoices() { return this.parents.filter((p) => String(p.id) !== String(this.form.id)); },
            get formAction() { return this.mode === 'edit' ? `${config.storeUrl}/${this.form.id}` : config.storeUrl; },

            async persistOrder(el) {
                const ids = Array.from(el.querySelectorAll(':scope > [data-id]')).map((n) => n.dataset.id);
                try {
                    const res = await fetch(config.reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ ids }),
                    });
                    if (!res.ok) throw new Error('reorder failed');
                    this.toast('Order saved');
                } catch (e) {
                    this.toast('Could not save order', true);
                }
            },
            toast(msg, err = false) {
                this.toastMsg = msg;
                this.toastErr = err;
                clearTimeout(this._t);
                this._t = setTimeout(() => { this.toastMsg = ''; }, 2000);
            },
        }));
    });
</script>
@endpush

@endsection
