@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.forms.index') }}" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h1 class="admin-page-title">Submissions: {{ $form->name }}</h1>
                    <p class="admin-page-subtitle">{{ $submissions->total() }} total · {{ $form->unreadCount() }} unread</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.forms.submissions.index', $form) }}" class="admin-btn-secondary text-sm {{ request('filter') === 'unread' ? '' : 'admin-btn-primary' }}">All</a>
                <a href="{{ route('admin.forms.submissions.index', $form) }}?filter=unread" class="admin-btn-secondary text-sm {{ request('filter') === 'unread' ? 'admin-btn-primary' : '' }}">Unread</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="space-y-4">
        @forelse($submissions as $submission)
            <div class="admin-card {{ $submission->is_read ? 'opacity-75' : '' }}">
                <div class="admin-card-body">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                @if(! $submission->is_read)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">New</span>
                                @endif
                                <span class="text-xs text-slate-400">
                                    {{ $submission->created_at->format('d M Y, H:i') }}
                                    @if($submission->ip_address)
                                        · {{ $submission->ip_address }}
                                    @endif
                                </span>
                            </div>

                            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                                @foreach($form->fields as $field)
                                    @php $value = $submission->data[$field['name']] ?? null; @endphp
                                    @if($value !== null)
                                        <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                                            <dt class="font-medium text-slate-500">{{ $field['label'] }}</dt>
                                            <dd class="mt-0.5 text-slate-800 {{ $field['type'] === 'textarea' ? 'whitespace-pre-wrap' : '' }}">{{ $value }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <form method="POST" action="{{ route('admin.form-submissions.read', $submission) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="admin-btn-soft px-3 py-1.5 text-xs" title="{{ $submission->is_read ? 'Mark unread' : 'Mark read' }}">
                                    <i class="fa-solid {{ $submission->is_read ? 'fa-envelope' : 'fa-envelope-open' }}"></i>
                                    {{ $submission->is_read ? 'Mark unread' : 'Mark read' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.form-submissions.destroy', $submission) }}">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this submission?')" class="admin-btn-danger px-3 py-1.5 text-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-card">
                <div class="admin-card-body">
                    <div class="admin-empty-state py-8">
                        <p class="font-medium text-slate-600">No submissions yet.</p>
                        <p class="mt-1 text-sm text-slate-400">Once visitors submit this form, their responses will appear here.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($submissions->hasPages())
        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
</div>
@endsection
