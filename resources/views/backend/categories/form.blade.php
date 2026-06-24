<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">
                {{ isset($category) ? 'Edit Category' : 'Create Category' }}
            </h1>

            <p class="mt-1 text-sm text-slate-400">
                {{ isset($category) ? 'Update category information.' : 'Create a new product category.' }}
            </p>
        </div>

        <a
            href="{{ route('admin.categories.index') }}"
            class="admin-btn-secondary w-full sm:w-auto"
        >
            Cancel
        </a>
    </div>

    <form
        action="{{ isset($category)
            ? route('admin.categories.update', $category)
            : route('admin.categories.store') }}"
        method="POST"
        class="space-y-6"
    >
        @csrf

        @isset($category)
            @method('PUT')
        @endisset

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="admin-form-label">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $category->name ?? '') }}"
                    class="admin-input @error('name') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                    required
                >
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="admin-form-label">Slug</label>
                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ old('slug', $category->slug ?? '') }}"
                    class="admin-input @error('slug') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                    placeholder="Auto generated if empty"
                >
                @error('slug')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="description" class="admin-form-label">Description</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                class="admin-textarea @error('description') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
            >{{ old('description', $category->description ?? '') }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="inline-flex items-center gap-3">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                @checked(old('is_active', $category->is_active ?? true))
            >
            <span class="text-sm font-medium text-slate-300">
                Active category
            </span>
        </label>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ isset($category) ? 'Update Category' : 'Create Category' }}
            </button>

            <a href="{{ route('admin.categories.index') }}" class="admin-btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
