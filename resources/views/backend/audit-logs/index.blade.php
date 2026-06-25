@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Audit Log</h1>
            <p class="admin-page-subtitle">
                Track all admin actions — creates, updates, and deletes across the CMS.
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="admin-card mb-4">
        <div class="admin-card-body">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-admin-secondary uppercase tracking-wide">Admin User</label>
                    <select name="user_id" class="admin-input w-44" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($adminUsers as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-admin-secondary uppercase tracking-wide">Action</label>
                    <select name="action" class="admin-input w-36" onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-admin-secondary uppercase tracking-wide">Type</label>
                    <select name="type" class="admin-input w-36" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-admin-secondary uppercase tracking-wide">From</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="admin-input w-36"
                        onchange="this.form.submit()"
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-admin-secondary uppercase tracking-wide">To</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="admin-input w-36"
                        onchange="this.form.submit()"
                    >
                </div>

                @if(request()->hasAny(['user_id', 'action', 'type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.audit-logs.index') }}" class="admin-btn-soft px-4 py-2">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-admin-secondary">Activity</h2>
                <span class="admin-badge-info">{{ $logs->total() }} entries</span>
            </div>
        </div>

        <div class="admin-card-body">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-header">
                            <th class="px-4 py-4">When</th>
                            <th class="px-4 py-4">User</th>
                            <th class="px-4 py-4">Action</th>
                            <th class="px-4 py-4">Subject</th>
                            <th class="px-4 py-4">Changes</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($logs as $log)
                            <tr class="admin-table-row" x-data="{ open: false }">
                                <td class="px-4 py-3 text-sm text-admin-secondary whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y') }}
                                    <div class="text-xs text-admin-secondary">{{ $log->created_at->format('H:i') }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-admin-secondary">
                                        {{ $log->user?->name ?? '—' }}
                                    </div>
                                    <div class="text-xs text-admin-secondary">{{ $log->ip_address }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    @php
                                        $badge = match($log->action) {
                                            'created'  => 'admin-badge-success',
                                            'updated'  => 'admin-badge-info',
                                            'deleted'  => 'admin-badge-danger',
                                            default    => 'admin-badge-warning',
                                        };
                                    @endphp
                                    <span class="{{ $badge }}">{{ ucfirst($log->action) }}</span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-admin-secondary">
                                        {{ $log->auditable_type }}
                                        @if($log->auditable_id)
                                            <span class="text-admin-secondary">#{{ $log->auditable_id }}</span>
                                        @endif
                                    </div>
                                    @if($log->auditable_label)
                                        <div class="text-xs text-admin-secondary truncate max-w-44">
                                            {{ $log->auditable_label }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if($log->old_values || $log->new_values)
                                        <button
                                            type="button"
                                            class="text-xs text-indigo-600 hover:underline"
                                            x-on:click="open = !open"
                                        >
                                            <span x-text="open ? 'Hide' : 'Show'">Show</span> diff
                                        </button>

                                        <div x-cloak x-show="open" class="mt-2 space-y-2">
                                            @if($log->old_values)
                                                <div>
                                                    <p class="text-xs font-semibold text-red-500 mb-1">Before</p>
                                                    <pre class="text-xs bg-red-50 text-red-700 rounded p-2 overflow-x-auto max-w-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                            @if($log->new_values)
                                                <div>
                                                    <p class="text-xs font-semibold text-green-600 mb-1">After</p>
                                                    <pre class="text-xs bg-green-50 text-green-700 rounded p-2 overflow-x-auto max-w-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-admin-secondary text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10">
                                    <div class="admin-empty-state">
                                        <p class="font-medium text-admin-secondary">No audit log entries found.</p>
                                        <p class="mt-1 text-sm text-admin-secondary">
                                            Activity will appear here as admins make changes.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
