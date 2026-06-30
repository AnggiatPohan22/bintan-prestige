@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Content Types</h1>
                <p class="admin-page-subtitle">Define custom content structures for your site.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72" method="GET">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search content types..."
                        class="admin-input"
                    >
                </form>

                <a href="{{ route('admin.content-types.create') }}" class="admin-btn-primary w-full sm:w-auto">
                    New Content Type
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    {{-- Active content types --}}
    <x-admin.data-table title="Content Types" :count="$active->total()" count-badge="success">
        <x-slot:thead>
            <th class="px-4 py-4">Type</th>
            <th class="px-4 py-4">Route</th>
            <th class="px-4 py-4">Supports</th>
            <th class="px-4 py-4">Status</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($active as $type)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-{{ $type->icon ?: 'file-lines' }} text-sm" aria-hidden="true"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-admin-secondary">{{ $type->label_plural }}</div>
                                <div class="mt-0.5 text-xs text-admin-secondary">slug: {{ $type->slug }}</div>
                            </div>
                        </div>
                        @if($type->description)
                            <p class="mt-2 max-w-md text-sm text-admin-secondary">
                                {{ \Illuminate\Support\Str::limit($type->description, 100) }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        @if($type->is_public && $type->route_base)
                            <div class="font-mono text-xs">
                                /{{ $type->route_base }}
                                @if($type->has_archive)
                                    <span class="ml-1 text-admin-secondary">(archive)</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-admin-secondary">Private — no public routes</span>
                        @endif
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($type->supports ?? [] as $flag)
                                <span class="admin-badge-info">{{ $flag }}</span>
                            @endforeach
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <span class="{{ $type->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.content-types.entries.index', $type) }}"
                               class="admin-btn-primary px-4 py-2">
                                Entries
                            </a>
                            <a href="{{ route('admin.content-types.field-groups.index', $type) }}"
                               class="admin-btn-soft px-4 py-2">
                                Fields
                            </a>
                            <a href="{{ route('admin.content-types.edit', $type) }}" class="admin-btn-soft px-4 py-2">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.content-types.destroy', $type) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Archive &quot;{{ $type->label_plural }}&quot;?"
                                    class="admin-btn-danger px-4 py-2"
                                >
                                    Archive
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10">
                        <div class="admin-empty-state">
                            No content types yet.
                            <a href="{{ route('admin.content-types.create') }}" class="ml-1 text-indigo-600 hover:underline">Create one now.</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $active->links() }}
        </x-slot:pagination>
    </x-admin.data-table>

    {{-- Archive --}}
    @if($archived->total() > 0)
    <x-admin.data-table title="Archive" :count="$archived->total()" count-badge="info">
        <x-slot:thead>
            <th class="px-4 py-4">Type</th>
            <th class="px-4 py-4">Archived At</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @foreach($archived as $type)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-admin-secondary">{{ $type->label_plural }}</div>
                        <div class="mt-0.5 text-xs text-admin-secondary">{{ $type->slug }}</div>
                    </td>

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        {{ $type->deleted_at?->format('d M Y H:i') }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <form method="POST" action="{{ route('admin.content-types.restore', $type->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="admin-btn-success px-4 py-2">Restore</button>
                            </form>

                            <form method="POST" action="{{ route('admin.content-types.force-delete', $type->id) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Permanently delete &quot;{{ $type->label_plural }}&quot;? All entries of this type will also be removed."
                                    class="admin-btn-danger px-4 py-2"
                                >
                                    Delete Permanent
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $archived->links() }}
        </x-slot:pagination>
    </x-admin.data-table>
    @endif
</div>

@endsection
