@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Destinations</h1>
                <p class="admin-page-subtitle">Manage destination data used by products.</p>
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

                <a href="{{ route('admin.destinations.create') }}" class="admin-btn-primary w-full sm:w-auto">
                    Create Destination
                </a>
            </div>
        </div>
    </div>

    {{-- Active --}}
    <x-admin.data-table title="Active Destinations" :count="$destinations->total()" count-badge="success">
        <x-slot:thead>
            <th class="px-4 py-4">Destination</th>
            <th class="px-4 py-4">Products</th>
            <th class="px-4 py-4">Status</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
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
                                <div class="flex h-16 w-20 items-center justify-center rounded-lg bg-admin-card text-xs text-admin-secondary">
                                    No Image
                                </div>
                            @endif

                            <div>
                                <div class="font-semibold text-admin-secondary">{{ $destination->name }}</div>
                                <div class="mt-1 text-xs text-admin-secondary">/{{ $destination->slug }}</div>
                                @if($destination->description)
                                    <p class="mt-2 max-w-xl text-sm text-admin-secondary">
                                        {{ \Illuminate\Support\Str::limit($destination->description, 120) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-4 text-sm font-medium text-admin-secondary">
                        {{ $destination->products_count }}
                    </td>

                    <td class="px-4 py-4">
                        <span class="{{ $destination->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $destination->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.destinations.edit', $destination) }}" class="admin-btn-soft px-4 py-2">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.destinations.destroy', $destination) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Move this destination to archive?"
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
                    <td colspan="4" class="px-4 py-10">
                        <div class="admin-empty-state">No active destinations found.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $destinations->links() }}
        </x-slot:pagination>
    </x-admin.data-table>

    {{-- Archive --}}
    <x-admin.data-table title="Archive" :count="$archivedDestinations->total()" count-badge="info">
        <x-slot:thead>
            <th class="px-4 py-4">Destination</th>
            <th class="px-4 py-4">Archived At</th>
            <th class="px-4 py-4">Products</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($archivedDestinations as $destination)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-admin-secondary">{{ $destination->name }}</div>
                        <div class="mt-1 text-xs text-admin-secondary">/{{ $destination->slug }}</div>
                    </td>

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        {{ $destination->deleted_at?->format('d M Y H:i') }}
                    </td>

                    <td class="px-4 py-4 text-sm font-medium text-admin-secondary">
                        {{ $destination->products_count }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <form method="POST" action="{{ route('admin.destinations.restore', $destination->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="admin-btn-success px-4 py-2">Restore</button>
                            </form>

                            <form method="POST" action="{{ route('admin.destinations.force-delete', $destination->id) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Permanently delete this destination?"
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
                        <div class="admin-empty-state">Archive is empty.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $archivedDestinations->links() }}
        </x-slot:pagination>
    </x-admin.data-table>
</div>

@endsection
