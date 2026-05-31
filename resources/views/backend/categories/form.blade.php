<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ isset($category) ? 'Edit Category' : 'Create Category' }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ isset($category) ? 'Update category information.' : 'Create a new product category.' }}
            </p>
        </div>

        <a
            href="{{ route('admin.categories.index') }}"
            class="btn-secondary w-full sm:w-auto"
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
                <label for="name" class="form-label">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $category->name ?? '') }}"
                    class="form-input @error('name') form-error @enderror"
                    required
                >
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="form-label">Slug</label>
                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ old('slug', $category->slug ?? '') }}"
                    class="form-input @error('slug') form-error @enderror"
                    placeholder="Auto generated if empty"
                >
                @error('slug')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="description" class="form-label">Description</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                class="form-textarea @error('description') form-error @enderror"
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
                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                @checked(old('is_active', $category->is_active ?? true))
            >
            <span class="text-sm font-medium text-slate-700">
                Active category
            </span>
        </label>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="btn-primary">
                {{ isset($category) ? 'Update Category' : 'Create Category' }}
            </button>

            <a href="{{ route('admin.categories.index') }}" class="btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
