@extends('layouts.admin')

@section('content')
<div class="admin-page">
    @include('backend.content-entries.form')

    {{-- ============================================================ REVISION HISTORY --}}
    @if(isset($revisions) && $revisions->isNotEmpty())
        <div class="admin-form-card mt-6">
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wider text-slate-400">Revision History</h2>
            <p class="mb-4 text-xs text-admin-secondary">
                The most recent {{ $revisions->count() }} version(s). Restoring snapshots the current state first, so it is reversible.
            </p>

            <div class="admin-card overflow-hidden">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-16">#</th>
                            <th>Saved</th>
                            <th>By</th>
                            <th class="w-32">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revisions as $revision)
                            <tr>
                                <td class="font-mono text-sm">{{ $revision->revision_number }}</td>
                                <td class="text-sm text-admin-secondary">{{ $revision->created_at?->format('d M Y H:i') }}</td>
                                <td class="text-sm text-admin-secondary">{{ $revision->author?->name ?? '—' }}</td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('admin.content-types.entries.revisions.restore', [$contentType, $entry, $revision]) }}"
                                          data-confirm-submit="Restore this entry to revision #{{ $revision->revision_number }}? Current state is saved first."
                                          data-confirm-title="Restore Revision"
                                          data-confirm-btn="Restore">
                                        @csrf
                                        <button type="submit" class="admin-btn-soft py-1 text-xs">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
