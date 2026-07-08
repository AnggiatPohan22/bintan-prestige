@extends('layouts.admin')

@section('content')
<x-admin.form-shell
    title="Edit Destination"
    :subtitle="$destination->name"
    back-route="admin.destinations.index"
    form-action="{{ route('admin.destinations.update', $destination) }}"
    form-method="PUT"
    enctype="multipart/form-data"
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
                        value="{{ old('name', $destination->name) }}"
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
                        value="{{ old('slug', $destination->slug) }}"
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
                >{{ old('description', $destination->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-admin.media-image-field
                    name="image_path"
                    :value="old('image_path', $destination->image ?? '')"
                    label="Image"
                    collection="destination"
                    hint="Pick from the Media Library or upload (saved to the Destination collection)."
                />
                @error('image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('image_path')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-slot:content>

    <x-slot:sidebar>
        <x-admin.publish-box
            status-field="is_active"
            :status="old('is_active', $destination->is_active ? '1' : '0')"
            :status-options="['1' => 'Active', '0' => 'Inactive']"
            :status-colors="['1' => 'success', '0' => 'warning']"
            submit-label="Update Destination"
            cancel-route="admin.destinations.index"
        />
    </x-slot:sidebar>
</x-admin.form-shell>
@endsection
