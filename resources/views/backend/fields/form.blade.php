<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                <a href="{{ route('admin.content-types.index') }}" class="hover:text-indigo-600">Content Types</a>
                <span>/</span>
                <span>{{ $contentType->label_plural }}</span>
                <span>/</span>
                <a href="{{ route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup]) }}" class="hover:text-indigo-600">{{ $fieldGroup->label }}</a>
                <span>/</span>
                <span>{{ isset($field) ? 'Edit Field' : 'Add Field' }}</span>
            </div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ isset($field) ? 'Edit: '.$field->label : 'Add Field to "'.$fieldGroup->label.'"' }}
            </h1>
        </div>
        <a href="{{ route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup]) }}"
           class="admin-btn-secondary w-full sm:w-auto">
            Back to Group
        </a>
    </div>

    <form
        action="{{ isset($field)
            ? route('admin.content-types.field-groups.fields.update', [$contentType, $fieldGroup, $field])
            : route('admin.content-types.field-groups.fields.store', [$contentType, $fieldGroup]) }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @isset($field) @method('PUT') @endisset

        {{-- Type + Key + Label --}}
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div>
                <label for="type" class="admin-form-label">Field Type <span class="text-red-500">*</span></label>
                <select
                    id="type"
                    name="type"
                    class="admin-input @error('type') border-red-400 @enderror"
                    required
                    @isset($field) disabled @endisset
                >
                    <option value="">— Choose a type —</option>
                    @foreach($fieldTypes as $typeKey => $typeDef)
                        <option
                            value="{{ $typeKey }}"
                            @selected(old('type', $field->type ?? '') === $typeKey)
                            data-category="{{ $typeDef['category'] }}"
                        >
                            {{ $typeDef['label'] }}
                            ({{ $typeDef['category'] }})
                        </option>
                    @endforeach
                </select>
                @isset($field)
                    {{-- type is readonly on edit — pass as hidden --}}
                    <input type="hidden" name="type" value="{{ $field->type }}">
                @endisset
                @error('type') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="label" class="admin-form-label">Label <span class="text-red-500">*</span></label>
                <input
                    id="label"
                    type="text"
                    name="label"
                    value="{{ old('label', $field->label ?? '') }}"
                    placeholder="e.g. Star Rating"
                    class="admin-input @error('label') border-red-400 @enderror"
                    required
                >
                @error('label') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="key" class="admin-form-label">Key <span class="text-red-500">*</span></label>
                <input
                    id="key"
                    type="text"
                    name="key"
                    value="{{ old('key', $field->key ?? '') }}"
                    placeholder="Auto-generated (snake_case)"
                    class="admin-input font-mono @error('key') border-red-400 @enderror"
                    @isset($field) readonly @endisset
                >
                <p class="mt-1 text-xs text-admin-secondary">Unique per group. Cannot change after creation.</p>
                @error('key') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Instructions --}}
        <div>
            <label for="instructions" class="admin-form-label">Instructions</label>
            <textarea
                id="instructions"
                name="instructions"
                rows="2"
                placeholder="Help text shown to editors below this field."
                class="admin-textarea @error('instructions') border-red-400 @enderror"
            >{{ old('instructions', $field->instructions ?? '') }}</textarea>
            @error('instructions') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Options --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:gap-8">
            <label class="inline-flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_required"
                    value="1"
                    class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                    @checked(old('is_required', $field->is_required ?? false))
                >
                <span class="text-sm font-medium text-admin-secondary">Required field</span>
            </label>

            <label class="inline-flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_filterable"
                    value="1"
                    class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                    @checked(old('is_filterable', $field->is_filterable ?? false))
                >
                <span class="text-sm font-medium text-admin-secondary">
                    Filterable
                    <span class="ml-1 text-xs font-normal text-admin-secondary">(projected to search index)</span>
                </span>
            </label>
        </div>

        {{-- Sort order --}}
        <div class="w-32">
            <label for="sort_order" class="admin-form-label">Sort Order</label>
            <input
                id="sort_order"
                type="number"
                name="sort_order"
                value="{{ old('sort_order', $field->sort_order ?? 0) }}"
                min="0"
                class="admin-input"
            >
        </div>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ isset($field) ? 'Update Field' : 'Add Field' }}
            </button>
            <a href="{{ route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup]) }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
