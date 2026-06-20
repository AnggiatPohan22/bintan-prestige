@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Contact Forms</h1>
                <p class="admin-page-subtitle">Build forms and manage submissions from visitors.</p>
            </div>
            <a href="{{ route('admin.forms.create') }}" class="admin-btn-primary w-full sm:w-auto">
                + Create Form
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-lg font-extrabold text-slate-900">All Forms</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-header">
                            <th class="px-4 py-4">Form</th>
                            <th class="px-4 py-4">Fields</th>
                            <th class="px-4 py-4">Submissions</th>
                            <th class="px-4 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($forms as $form)
                            <tr class="admin-table-row">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-800">{{ $form->name }}</div>
                                    <div class="mt-1 font-mono text-xs text-slate-400">{{ $form->slug }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    {{ count($form->fields) }} field(s)
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.forms.submissions.index', $form) }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:underline">
                                        {{ $form->submissions_count }} total
                                        @if($form->unread_count > 0)
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                                                {{ $form->unread_count }} unread
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.forms.submissions.index', $form) }}" class="admin-btn-soft px-3 py-1.5 text-xs">
                                            Submissions
                                        </a>
                                        <a href="{{ route('admin.forms.edit', $form) }}" class="admin-btn-soft px-3 py-1.5 text-xs">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Delete form &quot;{{ addslashes($form->name) }}&quot; and all its submissions?')" class="admin-btn-danger px-3 py-1.5 text-xs">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10">
                                    <div class="admin-empty-state">
                                        <p class="font-medium text-slate-600">No forms yet.</p>
                                        <p class="mt-1 text-sm text-slate-400">
                                            <a href="{{ route('admin.forms.create') }}" class="text-indigo-600 hover:underline">Create your first form</a>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
