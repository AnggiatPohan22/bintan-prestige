@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Redirect Manager</h1>
                <p class="admin-page-subtitle">Manage 301/302 URL redirects. Changes apply within 10 minutes.</p>
            </div>
            <a href="{{ route('admin.seo.redirects.create') }}" class="admin-btn-primary w-full sm:w-auto">
                <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Add Redirect
            </a>
        </div>
    </div>

    <x-admin.data-table title="All Redirects" :count="$redirects->total()">
        <x-slot:thead>
            <th class="px-4 py-4">From URL</th>
            <th class="px-4 py-4">To URL</th>
            <th class="px-4 py-4">Code</th>
            <th class="px-4 py-4">Status</th>
            <th class="px-4 py-4">Updated</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($redirects as $redirect)
                <tr class="admin-table-row">
                    <td class="px-4 py-4 font-mono text-sm text-admin-secondary">{{ $redirect->from_url }}</td>
                    <td class="max-w-xs truncate px-4 py-4 font-mono text-sm text-admin-secondary">{{ $redirect->to_url }}</td>
                    <td class="px-4 py-4">
                        <span class="admin-badge-{{ $redirect->status_code === 301 ? 'info' : 'warning' }}">
                            {{ $redirect->status_code }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        @if($redirect->is_active)
                            <span class="admin-badge-success">Active</span>
                        @else
                            <span class="admin-badge-warning">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-xs text-admin-secondary">{{ $redirect->updated_at->format('d M Y') }}</td>
                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.seo.redirects.edit', $redirect) }}" class="admin-btn-soft px-3 py-2 text-xs">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.seo.redirects.destroy', $redirect) }}" onsubmit="return confirm('Delete this redirect?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="admin-btn-danger px-3 py-2 text-xs">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10">
                        <div class="admin-empty-state">No redirects configured yet.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        @if($redirects->hasPages())
            <x-slot:pagination>
                {{ $redirects->links() }}
            </x-slot:pagination>
        @endif
    </x-admin.data-table>
</div>
@endsection
