@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Redirect Manager</h1>
            <p class="admin-page-subtitle">Manage 301/302 URL redirects. Changes apply within 10 minutes.</p>
        </div>
        <a href="{{ route('admin.seo.redirects.create') }}" class="admin-btn-primary">
            <i class="fa-solid fa-plus mr-1"></i> Add Redirect
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="admin-card">
        <div class="admin-card-body p-0">
            @if($redirects->isEmpty())
                <p class="p-6 text-sm text-slate-500">No redirects configured yet.</p>
            @else
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>From URL</th>
                            <th>To URL</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($redirects as $redirect)
                        <tr>
                            <td class="font-mono text-sm text-slate-700">{{ $redirect->from_url }}</td>
                            <td class="font-mono text-sm text-slate-500 max-w-xs truncate">{{ $redirect->to_url }}</td>
                            <td>
                                <span class="admin-badge-{{ $redirect->status_code === 301 ? 'info' : 'warning' }}">
                                    {{ $redirect->status_code }}
                                </span>
                            </td>
                            <td>
                                @if($redirect->is_active)
                                    <span class="admin-badge-success">Active</span>
                                @else
                                    <span class="admin-badge-warning">Inactive</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-400">{{ $redirect->updated_at->format('d M Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.seo.redirects.edit', $redirect) }}" class="admin-btn-secondary text-xs">Edit</a>
                                <form method="POST" action="{{ route('admin.seo.redirects.destroy', $redirect) }}" class="inline" onsubmit="return confirm('Delete this redirect?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="admin-btn-danger text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($redirects->hasPages())
                    <div class="p-4">{{ $redirects->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
