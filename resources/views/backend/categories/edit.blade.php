@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Edit Category</h1>
                <p class="admin-page-subtitle">
                    Update category information.
                </p>
            </div>

            <a
                href="{{ route('admin.categories.index') }}"
                class="admin-btn-secondary mt-5 w-full sm:mt-0 sm:w-auto"
            >
                Back
            </a>
        </div>

        <div class="admin-form-card">
            <form
                action="{{ route('admin.categories.update', $category) }}"
                method="POST"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name" class="admin-form-label">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $category->name ?? '') }}"
                            class="admin-input @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
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
                            class="admin-input @error('slug') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
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
                        class="admin-textarea @error('description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
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
                    <span class="text-sm font-semibold text-slate-700">
                        Active category
                    </span>
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                    <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                        Update Category
                    </button>

                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="admin-btn-secondary w-full sm:w-auto"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
