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

            <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_180px]">
                <div>
                    <label for="image" class="admin-form-label">Image</label>
                    <input
                        id="image"
                        type="file"
                        name="image"
                        accept="image/*"
                        class="admin-input @error('image') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    @error('image')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    @if($destination->image)
                        <img
                            src="{{ asset('storage/'.$destination->image) }}"
                            class="h-28 w-full rounded-xl border border-slate-200 object-cover"
                            alt="{{ $destination->name }}"
                        >
                    @else
                        <div class="flex h-28 w-full items-center justify-center rounded-xl border border-slate-200 bg-slate-800 text-sm text-slate-400">
                            No Image
                        </div>
                    @endif
                </div>
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
