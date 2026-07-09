@extends('layouts.admin')

@section('content')
<x-admin.form-shell
    title="Edit Category"
    :subtitle="$category->name"
    back-route="admin.categories.index"
    form-action="{{ route('admin.categories.update', $category) }}"
    form-method="PUT"
>
    <x-slot:content>
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="admin-form-label">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $category->name) }}"
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
                        value="{{ old('slug', $category->slug) }}"
                        class="admin-input @error('slug') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                        placeholder="Auto-generated if empty"
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
                >{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-admin.media-image-field
                    name="image"
                    :value="old('image', $category->image ?? '')"
                    label="Category Image"
                    collection="category"
                    hint="Shown on the frontend category landing header. Pick from the Media Library or upload (saved to the Category collection)."
                />
                @error('image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Per-locale translations (Phase 7 — B6) --}}
            @php $__locales = \App\Support\Locales::nonDefaultActive(); @endphp
            @foreach($__locales as $__loc)
                <div class="rounded-xl border border-violet-200 bg-violet-50/40 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="rounded-md bg-violet-600 px-2 py-0.5 text-xs font-bold uppercase text-white">{{ $__loc }}</span>
                        <span class="text-sm font-bold text-admin-secondary">{{ \App\Support\Locales::label($__loc) ?? strtoupper($__loc) }} translation</span>
                        <span class="text-xs text-admin-secondary opacity-70">Leave blank to fall back to the default language.</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="admin-form-label">Name</label>
                            <input type="text" name="translations[{{ $__loc }}][name]"
                                   value="{{ old("translations.$__loc.name", $category->rawTranslation('name', $__loc)) }}"
                                   class="admin-input">
                        </div>
                        <div>
                            <label class="admin-form-label">Description</label>
                            <textarea name="translations[{{ $__loc }}][description]" rows="4" class="admin-textarea">{{ old("translations.$__loc.description", $category->rawTranslation('description', $__loc)) }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot:content>

    <x-slot:sidebar>
        <x-admin.publish-box
            status-field="is_active"
            :status="old('is_active', $category->is_active ? '1' : '0')"
            :status-options="['1' => 'Active', '0' => 'Inactive']"
            :status-colors="['1' => 'success', '0' => 'warning']"
            submit-label="Update Category"
            cancel-route="admin.categories.index"
        />
    </x-slot:sidebar>
</x-admin.form-shell>
@endsection
