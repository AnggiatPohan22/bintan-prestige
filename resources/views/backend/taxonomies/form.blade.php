@php
    /** @var App\Models\Taxonomy|null $taxonomy */
    /** @var \Illuminate\Database\Eloquent\Collection<int, App\Models\ContentType> $contentTypes */
    $isEdit      = isset($taxonomy);
    $formAction  = $isEdit
        ? route('admin.taxonomies.update', $taxonomy)
        : route('admin.taxonomies.store');
    $selectedIds = $taxonomy->content_type_ids ?? [];
@endphp

<div class="admin-form-card">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                <a href="{{ route('admin.taxonomies.index') }}" class="hover:text-indigo-600">Taxonomies</a>
                <span>/</span>
                <span>{{ $isEdit ? 'Edit' : 'New Taxonomy' }}</span>
            </div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ $isEdit ? 'Edit '.$taxonomy->label_singular : 'New Taxonomy' }}
            </h1>
        </div>
        <a href="{{ route('admin.taxonomies.index') }}" class="admin-btn-secondary w-full sm:w-auto">
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    <form action="{{ $formAction }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Slug --}}
        <div>
            <label for="slug" class="admin-form-label">Slug <span class="text-red-500">*</span></label>
            <input id="slug" type="text" name="slug"
                   value="{{ old('slug', $taxonomy->slug ?? '') }}"
                   placeholder="e.g. category"
                   class="admin-input font-mono @error('slug') border-red-400 @enderror">
            <p class="mt-1 text-xs text-admin-secondary">Unique identifier. Auto-generated from singular label if left blank.</p>
            @error('slug') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Labels --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="label_singular" class="admin-form-label">Singular Label <span class="text-red-500">*</span></label>
                <input id="label_singular" type="text" name="label_singular"
                       value="{{ old('label_singular', $taxonomy->label_singular ?? '') }}"
                       placeholder="e.g. Category"
                       class="admin-input @error('label_singular') border-red-400 @enderror">
                @error('label_singular') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="label_plural" class="admin-form-label">Plural Label <span class="text-red-500">*</span></label>
                <input id="label_plural" type="text" name="label_plural"
                       value="{{ old('label_plural', $taxonomy->label_plural ?? '') }}"
                       placeholder="e.g. Categories"
                       class="admin-input @error('label_plural') border-red-400 @enderror">
                @error('label_plural') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="admin-form-label">Description</label>
            <textarea id="description" name="description" rows="2"
                      placeholder="Optional description for this taxonomy"
                      class="admin-textarea @error('description') border-red-400 @enderror"
            >{{ old('description', $taxonomy->description ?? '') }}</textarea>
            @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Options --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="admin-form-label">Type</label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="hidden" name="is_hierarchical" value="0">
                    <input type="checkbox" name="is_hierarchical" value="1"
                           {{ old('is_hierarchical', $taxonomy->is_hierarchical ?? false) ? 'checked' : '' }}
                           class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">Hierarchical (supports parent/child terms)</span>
                </label>
            </div>
            <div>
                <label for="sort_order" class="admin-form-label">Sort Order</label>
                <input id="sort_order" type="number" name="sort_order" min="0"
                       value="{{ old('sort_order', $taxonomy->sort_order ?? 0) }}"
                       class="admin-input">
            </div>
        </div>

        {{-- Content Type Restriction --}}
        @if($contentTypes->isNotEmpty())
            <div class="rounded-lg border border-slate-200 p-5 space-y-3">
                <div>
                    <label class="admin-form-label">Restrict to Content Types</label>
                    <p class="mt-0.5 text-xs text-admin-secondary">
                        Leave all unchecked to make this taxonomy available to all content types.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($contentTypes as $ct)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="content_type_ids[]" value="{{ $ct->id }}"
                                   {{ in_array($ct->id, old('content_type_ids', $selectedIds) ?? []) ? 'checked' : '' }}
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-700">{{ $ct->label_singular }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Submit --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ $isEdit ? 'Update Taxonomy' : 'Create Taxonomy' }}
            </button>
            <a href="{{ route('admin.taxonomies.index') }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
