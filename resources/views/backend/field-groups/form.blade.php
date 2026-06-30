<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                <a href="{{ route('admin.content-types.index') }}" class="hover:text-indigo-600">Content Types</a>
                <span>/</span>
                <span>{{ $contentType->label_plural }}</span>
                <span>/</span>
                <a href="{{ route('admin.content-types.field-groups.index', $contentType) }}" class="hover:text-indigo-600">Field Groups</a>
                <span>/</span>
                <span>{{ isset($fieldGroup) ? 'Edit' : 'New' }}</span>
            </div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ isset($fieldGroup) ? 'Edit: '.$fieldGroup->label : 'New Field Group' }}
            </h1>
        </div>
        <a href="{{ route('admin.content-types.field-groups.index', $contentType) }}" class="admin-btn-secondary w-full sm:w-auto">
            Back to Groups
        </a>
    </div>

    <form
        action="{{ isset($fieldGroup)
            ? route('admin.content-types.field-groups.update', [$contentType, $fieldGroup])
            : route('admin.content-types.field-groups.store', $contentType) }}"
        method="POST"
        class="space-y-5"
    >
        @csrf
        @isset($fieldGroup) @method('PUT') @endisset

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="label" class="admin-form-label">Label <span class="text-red-500">*</span></label>
                <input
                    id="label"
                    type="text"
                    name="label"
                    value="{{ old('label', $fieldGroup->label ?? '') }}"
                    placeholder="e.g. Property Details"
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
                    value="{{ old('key', $fieldGroup->key ?? '') }}"
                    placeholder="Auto-generated (snake_case)"
                    class="admin-input font-mono @error('key') border-red-400 @enderror"
                    @isset($fieldGroup) readonly @endisset
                >
                <p class="mt-1 text-xs text-admin-secondary">Unique per content type. Cannot change after creation.</p>
                @error('key') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="description" class="admin-form-label">Description</label>
            <textarea
                id="description"
                name="description"
                rows="2"
                class="admin-textarea @error('description') border-red-400 @enderror"
                placeholder="Optional note about this group's purpose."
            >{{ old('description', $fieldGroup->description ?? '') }}</textarea>
            @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="w-32">
            <label for="sort_order" class="admin-form-label">Sort Order</label>
            <input
                id="sort_order"
                type="number"
                name="sort_order"
                value="{{ old('sort_order', $fieldGroup->sort_order ?? 0) }}"
                min="0"
                class="admin-input @error('sort_order') border-red-400 @enderror"
            >
            @error('sort_order') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ isset($fieldGroup) ? 'Update Group' : 'Create Group' }}
            </button>
            <a href="{{ route('admin.content-types.field-groups.index', $contentType) }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
