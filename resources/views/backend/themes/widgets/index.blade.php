@extends('layouts.admin')

@section('content')

<div class="admin-page">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.themes.index') }}"
               class="mb-1 inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Back to Themes
            </a>
            <h1 class="text-lg font-extrabold text-slate-900">Widgets — {{ $theme->name }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Assign content widgets to declared areas in this theme.
            </p>
        </div>

        @if($areas)
            <a href="{{ route('admin.themes.widgets.create', $theme) }}"
               class="admin-btn-primary shrink-0">
                <i class="fa-solid fa-plus mr-1.5" aria-hidden="true"></i>
                Add Widget
            </a>
        @endif
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(empty($areas))

        {{-- No widget areas defined --}}
        <div class="admin-empty-state py-12">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
            </div>
            <p class="font-semibold text-slate-700">No widget areas defined</p>
            <p class="mt-1 max-w-xs text-center text-sm text-slate-400">
                Add a <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">widget_areas</code>
                section to this theme's <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">theme.json</code>
                to enable widget management.
            </p>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
            <p class="mb-2 font-semibold text-slate-700">Example <code class="rounded bg-white px-1 border border-slate-200 text-xs">theme.json</code> widget area declaration:</p>
            <pre class="overflow-x-auto rounded-lg bg-white border border-slate-200 p-3 text-xs text-slate-600">{
  "widget_areas": {
    "footer-col-1": { "label": "Footer Column 1", "description": "Left footer column" },
    "before-footer": { "label": "Before Footer",  "description": "Full-width zone above footer" }
  }
}</pre>
        </div>

    @else

        <div class="space-y-6">
            @foreach($areas as $areaKey => $areaConfig)
                @php
                    $areaWidgets = $widgets->get($areaKey, collect());
                    $areaLabel   = $areaConfig['label'] ?? ucwords(str_replace('-', ' ', $areaKey));
                    $areaDesc    = $areaConfig['description'] ?? null;
                @endphp

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    {{-- Area header --}}
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="font-bold text-slate-800">{{ $areaLabel }}</h2>
                            @if($areaDesc)
                                <p class="mt-0.5 text-xs text-slate-400">{{ $areaDesc }}</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.themes.widgets.create', [$theme, 'area' => $areaKey]) }}"
                           class="admin-btn-secondary shrink-0 text-sm">
                            <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>
                            Add Widget
                        </a>
                    </div>

                    @if($areaWidgets->isEmpty())
                        <p class="px-5 py-6 text-center text-sm text-slate-400">
                            No widgets in this area yet.
                        </p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach($areaWidgets as $widget)
                                <li class="flex items-center gap-3 px-5 py-3 {{ $widget->is_visible ? '' : 'opacity-50' }}">

                                    {{-- Type badge --}}
                                    <span class="admin-badge-neutral shrink-0 font-mono text-xs">
                                        {{ $widget->typeLabel() }}
                                    </span>

                                    {{-- Title --}}
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">
                                        {{ $widget->title ?: '(untitled)' }}
                                    </span>

                                    {{-- Sort order --}}
                                    <span class="shrink-0 text-xs text-slate-400">#{{ $widget->sort_order }}</span>

                                    {{-- Visibility toggle --}}
                                    <form method="POST" action="{{ route('admin.themes.widgets.toggle-visible', [$theme, $widget]) }}">
                                        @csrf
                                        <button type="submit"
                                                title="{{ $widget->is_visible ? 'Hide widget' : 'Show widget' }}"
                                                class="text-slate-400 hover:text-slate-700">
                                            <i class="fa-solid {{ $widget->is_visible ? 'fa-eye' : 'fa-eye-slash' }}"
                                               aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.themes.widgets.edit', [$theme, $widget]) }}"
                                       class="shrink-0 text-sm text-indigo-600 hover:text-indigo-800">
                                        Edit
                                    </a>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.themes.widgets.destroy', [$theme, $widget]) }}"
                                          onsubmit="return confirm('Delete this widget?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="shrink-0 text-sm text-red-500 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </div>
            @endforeach
        </div>

        {{-- Widget type reference --}}
        <details class="mt-6 rounded-2xl border border-slate-200 bg-slate-50">
            <summary class="cursor-pointer select-none px-5 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-circle-info mr-2 text-slate-400" aria-hidden="true"></i>
                Available Widget Types
            </summary>
            <div class="border-t border-slate-200 px-5 py-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-800 text-sm">Text</p>
                        <p class="mt-1 text-xs text-slate-400">Optional heading + rich-text content. Safe HTML output.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-800 text-sm">HTML</p>
                        <p class="mt-1 text-xs text-slate-400">Raw HTML block. Use for embed codes, custom markup, or widgets not covered by other types.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-800 text-sm">Image</p>
                        <p class="mt-1 text-xs text-slate-400">Single image with alt text. Rendered as a responsive <code class="text-xs">&lt;img&gt;</code> with lazy loading.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-800 text-sm">Navigation</p>
                        <p class="mt-1 text-xs text-slate-400">List of links with optional external-link flags. Useful for footer nav columns.</p>
                    </div>
                </div>
            </div>
        </details>

    @endif

</div>

@endsection
