@extends('layouts.admin')

@section('content')
<div class="admin-page space-y-8">

    {{-- Group settings form --}}
    @include('backend.field-groups.form')

    {{-- Fields table --}}
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-admin-secondary">
                Fields
                <span class="ml-2 text-sm font-normal text-admin-secondary">({{ $fieldGroup->fields->count() }})</span>
            </h2>
            <a href="{{ route('admin.content-types.field-groups.fields.create', [$contentType, $fieldGroup]) }}"
               class="admin-btn-primary">
                Add Field
            </a>
        </div>

        @if(session('success'))
            <div class="admin-alert-success mb-4">{{ session('success') }}</div>
        @endif

        <x-admin.data-table title="Fields" :count="$fieldGroup->fields->count()" count-badge="info">
            <x-slot:thead>
                <th class="px-4 py-4">Field</th>
                <th class="px-4 py-4">Type</th>
                <th class="px-4 py-4">Options</th>
                <th class="px-4 py-4 text-right">Action</th>
            </x-slot:thead>

            <x-slot:tbody>
                @forelse($fieldGroup->fields as $field)
                    <tr class="admin-table-row">
                        <td class="px-4 py-4">
                            <div class="font-semibold text-admin-secondary">{{ $field->label }}</div>
                            <div class="mt-0.5 font-mono text-xs text-admin-secondary">{{ $field->key }}</div>
                            @if($field->instructions)
                                <p class="mt-1 max-w-xs text-xs text-admin-secondary">{{ \Illuminate\Support\Str::limit($field->instructions, 60) }}</p>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            <span class="admin-badge-info">{{ $field->typeLabel() }}</span>
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($field->is_required)
                                    <span class="admin-badge-warning">Required</span>
                                @endif
                                @if($field->is_filterable)
                                    <span class="admin-badge-success">Filterable</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                <a href="{{ route('admin.content-types.field-groups.fields.edit', [$contentType, $fieldGroup, $field]) }}"
                                   class="admin-btn-soft px-4 py-2">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('admin.content-types.field-groups.fields.destroy', [$contentType, $fieldGroup, $field]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        data-confirm="Delete field &quot;{{ $field->label }}&quot;? This cannot be undone."
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
                        <td colspan="4" class="px-4 py-8">
                            <div class="admin-empty-state">
                                No fields in this group yet.
                                <a href="{{ route('admin.content-types.field-groups.fields.create', [$contentType, $fieldGroup]) }}"
                                   class="ml-1 text-indigo-600 hover:underline">Add the first field.</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-slot:tbody>
        </x-admin.data-table>
    </div>
</div>
@endsection
