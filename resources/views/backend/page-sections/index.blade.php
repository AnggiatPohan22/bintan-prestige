@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">
                    Page Sections
                </h1>

                <p class="admin-page-subtitle">
                    Manage editable static section content and images by page.
                </p>
            </div>

            @if($activePageKey)
                <span class="admin-badge-info w-fit">
                    Active page: {{ $activePageKey }}
                </span>
            @endif
        </div>
    </div>

    <div
        class="admin-card"
        x-data="{
            activeGroup: 'All',
            search: '',
            matchesCard($el) {
                const term = this.search.trim().toLowerCase();
                const matchesSearch = !term || $el.dataset.search.includes(term);
                const matchesGroup = this.activeGroup === 'All' || $el.dataset.group === this.activeGroup;

                return matchesSearch && matchesGroup;
            }
        }"
    >
        @if($pageKeys->count())
            @php
                $pageFilterGroups = ['All', 'Home', 'Products', 'Destinations', 'Content', 'System'];

                $resolvePageFilterGroup = function (string $pageKey): string {
                    $normalizedKey = strtolower($pageKey);

                    if ($normalizedKey === 'home' || str_starts_with($normalizedKey, 'home.')) {
                        return 'Home';
                    }

                    if (str_starts_with($normalizedKey, 'products.')) {
                        return 'Products';
                    }

                    if (str_starts_with($normalizedKey, 'destinations.')) {
                        return 'Destinations';
                    }

                    foreach (['faqs', 'faq', 'contact', 'about', 'blog'] as $contentKey) {
                        if ($normalizedKey === $contentKey || str_starts_with($normalizedKey, $contentKey.'.')) {
                            return 'Content';
                        }
                    }

                    foreach (['404', 'popup', 'settings', 'global'] as $systemKey) {
                        if ($normalizedKey === $systemKey || str_starts_with($normalizedKey, $systemKey.'.')) {
                            return 'System';
                        }
                    }

                    return 'Content';
                };

                $groupedPageOptions = collect($pageOptions)
                    ->map(function ($page) use ($resolvePageFilterGroup) {
                        $page['filter_group'] = $resolvePageFilterGroup((string) $page['key']);

                        return $page;
                    })
                    ->groupBy('filter_group');
            @endphp

            <div class="admin-card-header">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900">
                            Page Filter
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Registered frontend pages are synced automatically. Section order follows the frontend mapping when available.
                        </p>
                    </div>

                    <div class="w-full xl:max-w-xs">
                        <label for="page-filter-search" class="sr-only">Search pages</label>
                        <input
                            id="page-filter-search"
                            type="search"
                            placeholder="Search pages..."
                            class="admin-input"
                            x-model.debounce.150ms="search"
                        >
                    </div>
                </div>
            </div>

            <div class="admin-card-body">
                <div class="mb-6 flex gap-2 overflow-x-auto pb-2">
                    @foreach($pageFilterGroups as $group)
                        @php
                            $groupCount = $group === 'All'
                                ? $pageOptions->count()
                                : ($groupedPageOptions->get($group)?->count() ?? 0);
                        @endphp

                        <button
                            type="button"
                            x-on:click="activeGroup = '{{ $group }}'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-bold transition"
                            x-bind:class="activeGroup === '{{ $group }}'
                                ? 'border-indigo-200 bg-indigo-600 text-white shadow-sm shadow-indigo-100'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700'"
                        >
                            {{ $group }}
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px]"
                                x-bind:class="activeGroup === '{{ $group }}'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-slate-100 text-slate-500'"
                            >
                                {{ $groupCount }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="space-y-6">
                    @foreach($pageFilterGroups as $group)
                        @continue($group === 'All' || ! $groupedPageOptions->has($group))

                        <section
                            class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4"
                            x-show="activeGroup === 'All' || activeGroup === '{{ $group }}'"
                        >
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-extrabold uppercase tracking-wide text-slate-700">
                                        {{ $group }}
                                    </h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        {{ $groupedPageOptions->get($group)->count() }} registered page(s)
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($groupedPageOptions->get($group) as $page)
                                    @php
                                        $pageKey = $page['key'];
                                        $isActivePage = $activePageKey === $pageKey;
                                        $searchText = strtolower(implode(' ', [
                                            $page['key'],
                                            $page['label'],
                                            $page['description'],
                                            $page['filter_group'],
                                            $page['group'] ?? '',
                                        ]));
                                    @endphp

                                    <div
                                        class="flex min-h-40 flex-col rounded-2xl border p-4 text-left transition {{ $isActivePage ? 'border-indigo-200 bg-white text-indigo-800 shadow-sm ring-2 ring-indigo-100' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50/60' }}"
                                        data-group="{{ $page['filter_group'] }}"
                                        data-search="{{ $searchText }}"
                                        x-show="matchesCard($el)"
                                    >
                                        <span class="text-[11px] font-bold uppercase tracking-wide {{ $isActivePage ? 'text-indigo-600' : 'text-slate-400' }}">
                                            {{ $page['filter_group'] }}
                                        </span>
                                        <span class="mt-1 text-sm font-extrabold">
                                            {{ $page['label'] }}
                                        </span>
                                        <span class="mt-1 line-clamp-2 text-xs leading-5 {{ $isActivePage ? 'text-indigo-700' : 'text-slate-500' }}">
                                            {{ $page['description'] }}
                                        </span>
                                        <span class="mt-auto pt-4">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $page['exists'] ? 'bg-slate-50 text-slate-600 ring-1 ring-slate-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' }}">
                                                {{ $pageKey }}
                                            </span>
                                        </span>

                                        <a
                                            href="{{ route('admin.page-sections.sections', ['page' => $pageKey]) }}"
                                            class="admin-btn-primary mt-4 w-full px-4 py-2"
                                        >
                                            Manage Sections
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

</div>

@endsection
