@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                    <a href="{{ route('admin.content-types.index') }}" class="hover:text-indigo-600">Content Types</a>
                    <span>/</span>
                    <a href="{{ route('admin.content-types.edit', $contentType) }}" class="hover:text-indigo-600">{{ $contentType->label_plural }}</a>
                    <span>/</span>
                    <span>Field Groups</span>
                </div>
                <h1 class="admin-page-title">{{ $contentType->label_plural }} — Field Groups</h1>
                <p class="admin-page-subtitle">Organise fields for this content type into logical groups.</p>
            </div>

            <a href="{{ route('admin.content-types.field-groups.create', $contentType) }}" class="admin-btn-primary w-full sm:w-auto">
                Add Field Group
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    <x-admin.data-table title="Field Groups" :count="$groups->count()" count-badge="info">
        <x-slot:thead>
            <th class="px-4 py-4">Group</th>
            <th class="px-4 py-4">Fields</th>
            <th class="px-4 py-4">Sort</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($groups as $group)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-admin-secondary">{{ $group->label }}</div>
                        <div class="mt-0.5 font-mono text-xs text-admin-secondary">{{ $group->key }}</div>
                        @if($group->description)
                            <p class="mt-1 max-w-md text-sm text-admin-secondary">{{ \Illuminate\Support\Str::limit($group->description, 80) }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        {{ $group->fields->count() }} field{{ $group->fields->count() !== 1 ? 's' : '' }}
                        @if($group->fields->isNotEmpty())
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($group->fields->take(5) as $field)
                                    <span class="admin-badge-info font-mono text-[10px]">{{ $field->key }}</span>
                                @endforeach
                                @if($group->fields->count() > 5)
                                    <span class="text-xs text-admin-secondary">+{{ $group->fields->count() - 5 }} more</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        {{ $group->sort_order }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.content-types.field-groups.edit', [$contentType, $group]) }}"
                               class="admin-btn-soft px-4 py-2">
                                Edit / Fields
                            </a>

                            <form method="POST" action="{{ route('admin.content-types.field-groups.destroy', [$contentType, $group]) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Delete field group &quot;{{ $group->label }}&quot; and all its fields?"
                                    class="admin-btn-danger px-4 py-2"
                                >
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
                            No field groups yet.
                            <a href="{{ route('admin.content-types.field-groups.create', $contentType) }}"
                               class="ml-1 text-indigo-600 hover:underline">Add one now.</a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>
    </x-admin.data-table>
</div>
@endsection
