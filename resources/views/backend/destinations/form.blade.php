<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ isset($destination) ? 'Edit Destination' : 'Create Destination' }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ isset($destination) ? 'Update destination information.' : 'Create a new destination.' }}
            </p>
        </div>

        <a
            href="{{ route('admin.destinations.index') }}"
            class="admin-btn-secondary w-full sm:w-auto"
        >
            Cancel
        </a>
    </div>

    <form
        action="{{ isset($destination)
            ? route('admin.destinations.update', $destination)
            : route('admin.destinations.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
    >
        @csrf

        @isset($destination)
            @method('PUT')
        @endisset

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="admin-form-label">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $destination->name ?? '') }}"
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
                    value="{{ old('slug', $destination->slug ?? '') }}"
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
            >{{ old('description', $destination->description ?? '') }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_180px]">
            <div>
                <label for="image" class="admin-form-label">Image</label>
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/*"
                    class="admin-input @error('image') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                >
                @error('image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                @if(isset($destination) && $destination->image)
                    <img
                        src="{{ asset('storage/'.$destination->image) }}"
                        class="h-28 w-full rounded-lg border object-cover"
                        alt="{{ $destination->name }}"
                    >
                @else
                    <div class="flex h-28 w-full items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-400">
                        No Image
                    </div>
                @endif
            </div>
        </div>

        <label class="inline-flex items-center gap-3">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                @checked(old('is_active', $destination->is_active ?? true))
            >
            <span class="text-sm font-medium text-slate-700">
                Active destination
            </span>
        </label>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ isset($destination) ? 'Update Destination' : 'Create Destination' }}
            </button>

            <a href="{{ route('admin.destinations.index') }}" class="admin-btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
