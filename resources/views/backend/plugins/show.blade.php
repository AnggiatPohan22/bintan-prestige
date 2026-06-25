@extends('layouts.admin')

@section('content')
<div class="admin-page">

    {{-- Header --}}
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                    <a href="{{ route('admin.plugins.index') }}" class="hover:text-admin-secondary">Plugins</a>
                    <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                    <span class="text-admin-secondary">{{ $plugin->name }}</span>
                </div>
                <h1 class="admin-page-title">{{ $plugin->name }}</h1>
                @if($plugin->description)
                    <p class="admin-page-subtitle">{{ $plugin->description }}</p>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if($plugin->is_active)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">
                        <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>
                        Active
                    </span>

                    <div x-data="{ open: false }">
                        <button
                            type="button"
                            class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition-colors"
                            x-on:click="open = true"
                        >
                            <i class="fa-solid fa-toggle-on mr-1.5" aria-hidden="true"></i>
                            Deactivate
                        </button>

                        <div
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                            x-cloak x-show="open" x-transition.opacity
                        >
                            <div class="w-full max-w-sm rounded-2xl bg-admin-card p-6 shadow-xl" x-on:click.outside="open = false">
                                <h3 class="mb-2 text-base font-bold text-admin-secondary">Deactivate Plugin</h3>
                                <p class="mb-5 text-sm text-admin-secondary">
                                    Deactivate <strong>{{ $plugin->name }}</strong>? Its functionality will be disabled immediately.
                                </p>
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="admin-btn-secondary py-1.5 text-sm" x-on:click="open = false">Cancel</button>
                                    <form method="POST" action="{{ route('admin.plugins.deactivate', $plugin) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg bg-amber-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 transition-colors">
                                            Deactivate
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-admin-card px-3 py-1 text-sm font-semibold text-admin-secondary">
                        <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>
                        Inactive
                    </span>

                    <form method="POST" action="{{ route('admin.plugins.activate', $plugin) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors">
                            <i class="fa-solid fa-toggle-off mr-1.5" aria-hidden="true"></i>
                            Activate
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.plugins.index') }}" class="admin-btn-secondary py-2 text-sm">
                    <i class="fa-solid fa-arrow-left mr-1.5" aria-hidden="true"></i>
                    Back
                </a>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="admin-alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Plugin info --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="font-bold text-admin-secondary">Plugin Information</h2>
            </div>
            <div class="admin-card-body divide-y divide-slate-100">
                <div class="flex justify-between py-2.5 text-sm">
                    <span class="text-admin-secondary">Slug</span>
                    <code class="rounded bg-admin-card px-1.5 py-0.5 text-xs text-admin-secondary">{{ $plugin->slug }}</code>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <span class="text-admin-secondary">Version</span>
                    <span class="font-medium text-admin-secondary">{{ $plugin->version }}</span>
                </div>
                @if($plugin->author)
                    <div class="flex justify-between py-2.5 text-sm">
                        <span class="text-admin-secondary">Author</span>
                        <span class="font-medium text-admin-secondary">{{ $plugin->author }}</span>
                    </div>
                @endif
                <div class="flex justify-between py-2.5 text-sm">
                    <span class="text-admin-secondary">Installed</span>
                    <span class="text-admin-secondary">{{ $plugin->installed_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <span class="text-admin-secondary">Last Activated</span>
                    <span class="text-admin-secondary">{{ $plugin->activated_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Manifest details --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="font-bold text-admin-secondary">Manifest</h2>
            </div>
            <div class="admin-card-body">
                @if($manifest)
                    @if(!empty($manifest['requires']))
                        <div class="mb-3">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-admin-secondary">Requires</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($manifest['requires'] as $dep)
                                    <span class="rounded bg-admin-card px-2 py-0.5 text-xs text-admin-secondary">{{ $dep }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($manifest['min_cms_version']))
                        <div class="mb-3">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-admin-secondary">Min CMS Version</p>
                            <span class="text-sm text-admin-secondary">{{ $manifest['min_cms_version'] }}</span>
                        </div>
                    @endif

                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-admin-secondary">Service Provider</p>
                        <code class="block rounded bg-admin-card border border-admin px-3 py-2 text-xs text-admin-secondary break-all">
                            {{ $manifest['service_provider'] ?? '—' }}
                        </code>
                    </div>
                @else
                    <p class="text-sm text-admin-secondary">
                        <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-400" aria-hidden="true"></i>
                        No <code class="rounded bg-admin-card px-1 text-xs">plugin.json</code> found on disk for this plugin.
                        The plugin is registered in the database but its files may have been removed.
                    </p>
                @endif
            </div>
        </div>

        {{-- Stored config --}}
        @if($plugin->config)
            <div class="admin-card lg:col-span-2">
                <div class="admin-card-header">
                    <h2 class="font-bold text-admin-secondary">Stored Configuration</h2>
                </div>
                <div class="admin-card-body">
                    <pre class="overflow-x-auto rounded-lg bg-admin-card border border-admin p-4 text-xs text-admin-secondary">{{ json_encode($plugin->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
