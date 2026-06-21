@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">
                    Destinations
                </h1>

                <p class="admin-page-subtitle">
                    Manage destination data used by products.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search destinations..."
                        class="admin-input"
                    >
                </form>

                <a
                    href="{{ route('admin.destinations.create') }}"
                    class="admin-btn-primary w-full sm:w-auto"
                >
                    Create Destination
                </a>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">
                    Active Destinations
                </h2>

                <span class="admin-badge-success">
                    {{ $destinations->total() }} item(s)
                </span>
            </div>
        </div>

        <div class="admin-card-body">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr class="admin-table-header">
                        <th class="px-4 py-4">Destination</th>
                        <th class="px-4 py-4">Products</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($destinations as $destination)
                        <tr class="admin-table-row">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @if($destination->image)
                                        <img
                                            src="{{ asset('storage/'.$destination->image) }}"
                                            class="h-16 w-20 rounded-lg border object-cover"
                                            alt="{{ $destination->name }}"
                                        >
                                    @else
                                        <div class="flex h-16 w-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">
                                            No Image
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            {{ $destination->name }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            /{{ $destination->slug }}
                                        </div>
                                        @if($destination->description)
                                            <p class="mt-2 max-w-xl text-sm text-slate-500">
                                                {{ \Illuminate\Support\Str::limit($destination->description, 120) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $destination->products_count }}
                            </td>

                            <td class="px-4 py-4">
                                <span class="{{ $destination->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                    {{ $destination->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <a
                                        href="{{ route('admin.destinations.edit', $destination) }}"
                                        class="admin-btn-soft px-4 py-2"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.destinations.destroy', $destination) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Move this destination to archive?')"
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
                            <td colspan="4" class="px-4 py-6">
                                <div class="admin-empty-state">
                                    No active destinations found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $destinations->links() }}
        </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">
                    Archive
                </h2>

                <span class="admin-badge-info">
                    {{ $archivedDestinations->total() }} item(s)
                </span>
            </div>
        </div>

        <div class="admin-card-body">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr class="admin-table-header">
                        <th class="px-4 py-4">Destination</th>
                        <th class="px-4 py-4">Archived At</th>
                        <th class="px-4 py-4">Products</th>
                        <th class="px-4 py-4 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($archivedDestinations as $destination)
                        <tr class="admin-table-row">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $destination->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    /{{ $destination->slug }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-500">
                                {{ $destination->deleted_at?->format('d M Y H:i') }}
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $destination->products_count }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.destinations.restore', $destination->id) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="admin-btn-success px-4 py-2"
                                        >
                                            Restore
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.destinations.force-delete', $destination->id) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Permanently delete this destination?')"
                                            class="admin-btn-danger px-4 py-2"
                                        >
                                            Delete Permanent
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6">
                                <div class="admin-empty-state">
                                    Archive is empty.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $archivedDestinations->links() }}
        </div>
        </div>
    </div>
</div>

@endsection
