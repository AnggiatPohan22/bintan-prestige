@extends('layouts.admin')

@section('content')
<div class="admin-page">
    @php $__entryLocales = \App\Support\Locales::nonDefaultActive(); @endphp
    @if(count($__entryLocales) > 0)
        <div class="mb-4 overflow-hidden rounded-xl border border-violet-200 bg-violet-50/40">
            <div class="flex flex-wrap items-center gap-3 px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-admin-primary">🌐 Translations</span>
                    <span class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">this: {{ $entry->locale }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($__entryLocales as $__loc)
                        @php $__sibling = $entry->translationIn($__loc); @endphp
                        @if($__sibling)
                            <a href="{{ route('admin.content-types.entries.edit', [$contentType, $__sibling]) }}"
                               class="rounded-lg border border-violet-300 bg-admin-card px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:opacity-75">
                                Edit {{ strtoupper($__loc) }} <span class="opacity-60">({{ $__sibling->status }})</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('admin.content-types.entries.translate', [$contentType, $entry]) }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $__loc }}">
                                <button type="submit" class="rounded-lg border border-violet-500 bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90">
                                    + Translate to {{ strtoupper($__loc) }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

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
