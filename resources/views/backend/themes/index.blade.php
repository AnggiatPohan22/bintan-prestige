@extends('layouts.admin')

@section('content')

<div class="admin-page">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-lg font-extrabold text-admin-secondary">Themes</h1>
            <p class="mt-0.5 text-sm text-admin-secondary">
                Manage the visual appearance of your website.
                The active theme controls layouts, header, footer, widget areas, and design tokens.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2" x-data="{ importOpen: false }">
            <button
                type="button"
                class="admin-btn-secondary"
                x-on:click="importOpen = !importOpen"
                title="Import a theme from a ZIP file"
            >
                <i class="fa-solid fa-upload mr-1.5" aria-hidden="true"></i>
                Import Theme
            </button>

            <form method="POST" action="{{ route('admin.themes.scan') }}">
                @csrf
                <button
                    type="submit"
                    class="admin-btn-secondary"
                    title="Scan the themes/ directory for new or updated theme.json manifests"
                >
                    <i class="fa-solid fa-rotate mr-1.5" aria-hidden="true"></i>
                    Scan for Themes
                </button>
            </form>

            {{-- Import panel --}}
            <div
                class="w-full"
                x-cloak
                x-show="importOpen"
                x-transition
            >
                <div class="mt-3 rounded-2xl border border-admin bg-admin-card p-5">
                    <h2 class="mb-1 text-sm font-bold text-admin-secondary">Import Theme from ZIP</h2>
                    <p class="mb-4 text-xs text-admin-secondary">
                        Upload a <code class="rounded bg-admin-card px-1 border border-admin">.zip</code>
                        file exported from this CMS. Max 50 MB. PHP files are not allowed.
                    </p>

                    @if(session('error'))
                        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                            <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('admin.themes.import') }}"
                        enctype="multipart/form-data"
                        class="flex flex-wrap items-end gap-3"
                    >
                        @csrf
                        <div class="flex-1 min-w-48">
                            <label class="mb-1 block text-xs font-semibold text-admin-secondary uppercase tracking-wide">
                                Theme ZIP File
                            </label>
                            <input
                                type="file"
                                name="theme_zip"
                                accept=".zip,application/zip"
                                class="admin-input w-full text-sm"
                                required
                            >
                            @error('theme_zip')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="admin-btn-primary">
                            <i class="fa-solid fa-upload mr-1.5" aria-hidden="true"></i>
                            Upload & Install
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <i class="fa-solid fa-circle-info mr-1.5" aria-hidden="true"></i>
            {{ session('info') }}
        </div>
    @endif

    @if($themes->isEmpty())

        {{-- Empty state --}}
        <div class="admin-empty-state py-12">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-violet-900/20 text-2xl text-violet-400">
                <i class="fa-solid fa-palette" aria-hidden="true"></i>
            </div>
            <p class="font-semibold text-admin-secondary">No themes registered yet</p>
            <p class="mt-1 max-w-xs text-center text-sm text-admin-secondary">
                Place a <code class="rounded bg-admin-card px-1 py-0.5 text-xs">theme.json</code> file
                inside each subdirectory of <code class="rounded bg-admin-card px-1 py-0.5 text-xs">themes/</code>,
                then click <strong>Scan for Themes</strong>.
            </p>
        </div>

        {{-- Getting started guide --}}
        <div class="mt-8 rounded-2xl border border-admin bg-admin-card p-6">
            <h2 class="mb-3 text-sm font-bold text-admin-secondary">Getting Started</h2>
            <ol class="space-y-2 text-sm text-admin-secondary">
                <li class="flex gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-900/30 text-xs font-bold text-violet-400">1</span>
                    Create a subdirectory under <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">themes/your-theme-slug/</code>
                </li>
                <li class="flex gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-900/30 text-xs font-bold text-violet-400">2</span>
                    Add a <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">theme.json</code> manifest with <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">name</code>, <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">slug</code>, and optionally <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">widget_areas</code> and <code class="rounded bg-admin-card px-1 py-0.5 text-xs border border-admin">customization_schema</code>
                </li>
                <li class="flex gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-900/30 text-xs font-bold text-violet-400">3</span>
                    Click <strong>Scan for Themes</strong> above — the theme will appear in this list
                </li>
                <li class="flex gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-900/30 text-xs font-bold text-violet-400">4</span>
                    Click <strong>Activate</strong> to make it the live theme, then add widgets and customize design tokens
                </li>
            </ol>
        </div>

    @else

        {{-- Theme cards --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($themes as $theme)
                @php
                    $widgetAreaCount  = count($theme->widgetAreas());
                    $tokenCount       = count($theme->customization ?? []);
                    $totalWidgets     = $theme->widgets()->count();
                @endphp

                <div class="flex flex-col rounded-2xl border {{ $theme->is_active ? 'border-violet-500 ring-1 ring-violet-500/30' : 'border-admin' }} bg-admin-card">

                    {{-- Screenshot / placeholder --}}
                    <div class="relative flex h-36 items-center justify-center rounded-t-2xl overflow-hidden {{ $theme->is_active ? 'bg-gradient-to-br from-indigo-50 to-violet-50' : 'bg-admin-card' }}">
                        @if($theme->hasScreenshot())
                            <img
                                src="{{ asset('themes/' . $theme->slug . '/' . $theme->screenshot) }}"
                                alt="{{ $theme->name }} screenshot"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <span class="select-none text-5xl font-black tracking-tighter {{ $theme->is_active ? 'text-indigo-200' : 'text-admin-secondary' }}">
                                {{ strtoupper(mb_substr($theme->name, 0, 2)) }}
                            </span>
                        @endif

                        @if($theme->is_active)
                            <span class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white shadow">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                Active
                            </span>
                        @endif
                    </div>

                    {{-- Card body --}}
                    <div class="flex flex-1 flex-col gap-3 p-5">

                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-admin-secondary">{{ $theme->name }}</h2>
                                @if($theme->version || $theme->author)
                                    <p class="mt-0.5 text-xs text-admin-secondary">
                                        @if($theme->version) v{{ $theme->version }} @endif
                                        @if($theme->version && $theme->author) &middot; @endif
                                        @if($theme->author) {{ $theme->author }} @endif
                                    </p>
                                @endif
                            </div>
                            @if(! $theme->is_active)
                                <span class="admin-badge-neutral shrink-0">Inactive</span>
                            @endif
                        </div>

                        @if($theme->description)
                            <p class="line-clamp-2 text-sm text-admin-secondary">{{ $theme->description }}</p>
                        @endif

                        {{-- Stats row --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-admin/50 pt-3 text-xs text-admin-secondary">
                            <span title="Widget areas declared in theme.json">
                                <i class="fa-solid fa-puzzle-piece mr-1" aria-hidden="true"></i>
                                {{ $widgetAreaCount }} {{ Str::plural('area', $widgetAreaCount) }}
                            </span>
                            <span title="Active widgets assigned to this theme">
                                <i class="fa-solid fa-cubes mr-1" aria-hidden="true"></i>
                                {{ $totalWidgets }} {{ Str::plural('widget', $totalWidgets) }}
                            </span>
                            @if($tokenCount > 0)
                                <span class="text-amber-500" title="Design tokens customized from defaults">
                                    <i class="fa-solid fa-brush mr-1" aria-hidden="true"></i>
                                    {{ $tokenCount }} {{ Str::plural('token', $tokenCount) }} customized
                                </span>
                            @else
                                <span title="No design token overrides — using schema defaults">
                                    <i class="fa-solid fa-paintbrush mr-1" aria-hidden="true"></i>
                                    Using defaults
                                </span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-1">
                            @if($theme->is_active)
                                <span class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    Currently Active
                                </span>
                            @else
                                <form
                                    method="POST"
                                    action="{{ route('admin.themes.activate', $theme) }}"
                                    class="flex-1"
                                    onsubmit="return confirm('Activate \'{{ addslashes($theme->name) }}\'? This will change the live site appearance.')"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="admin-btn-secondary w-full">
                                        <i class="fa-solid fa-bolt mr-1.5" aria-hidden="true"></i>
                                        Activate
                                    </button>
                                </form>
                            @endif

                            <a
                                href="{{ route('admin.themes.widgets.index', $theme) }}"
                                class="admin-btn-secondary"
                                title="Manage widgets for {{ $theme->name }}"
                            >
                                <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
                            </a>

                            <a
                                href="{{ route('admin.themes.customize', $theme) }}"
                                class="admin-btn-secondary"
                                title="Customize design tokens for {{ $theme->name }}"
                            >
                                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                            </a>

                            <a
                                href="{{ route('admin.themes.export', $theme) }}"
                                class="admin-btn-secondary"
                                title="Export {{ $theme->name }} as ZIP"
                            >
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>

            @endforeach
        </div>

        {{-- How it works --}}
        <details class="mt-8 rounded-2xl border border-admin bg-admin-card">
            <summary class="cursor-pointer select-none px-5 py-4 text-sm font-semibold text-admin-secondary hover:text-admin-secondary">
                <i class="fa-solid fa-circle-info mr-2 text-admin-secondary" aria-hidden="true"></i>
                How the Theme System Works
            </summary>
            <div class="border-t border-admin px-5 py-4 text-sm text-admin-secondary space-y-2">
                <p><strong class="text-admin-secondary">Active theme</strong> — only one theme is active at a time. Activating a theme changes the live site immediately.</p>
                <p><strong class="text-admin-secondary">Widget areas</strong> — declared in <code class="rounded bg-admin-card px-1 border border-admin text-xs">theme.json</code> as named zones (e.g. <code class="rounded bg-admin-card px-1 border border-admin text-xs">footer-col-1</code>). Add widgets to populate those zones on the frontend.</p>
                <p><strong class="text-admin-secondary">Design tokens</strong> — CSS custom properties (e.g. <code class="rounded bg-admin-card px-1 border border-admin text-xs">--frontend-gold</code>) declared in the theme schema. Override values via the Customize page. Saved overrides are injected into every page's <code class="rounded bg-admin-card px-1 border border-admin text-xs">&lt;head&gt;</code> as a <code class="rounded bg-admin-card px-1 border border-admin text-xs">:root { }</code> block.</p>
                <p><strong class="text-admin-secondary">Template hierarchy</strong> — if a theme provides <code class="rounded bg-admin-card px-1 border border-admin text-xs">partials/header.blade.php</code> or <code class="rounded bg-admin-card px-1 border border-admin text-xs">partials/footer.blade.php</code>, those files replace the Phase 2 defaults. Otherwise the defaults are used automatically.</p>
            </div>
        </details>

    @endif

</div>

@endsection
