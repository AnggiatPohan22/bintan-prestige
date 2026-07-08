<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ isset($contentType) ? 'Edit: '.$contentType->label_plural : 'New Content Type' }}
            </h1>
            <p class="mt-1 text-sm text-admin-secondary">
                {{ isset($contentType) ? 'Update the content type definition.' : 'Define a new content structure for your site.' }}
            </p>
        </div>
        <a href="{{ route('admin.content-types.index') }}" class="admin-btn-secondary w-full sm:w-auto">
            Cancel
        </a>
    </div>

    <form
        action="{{ isset($contentType)
            ? route('admin.content-types.update', $contentType)
            : route('admin.content-types.store') }}"
        method="POST"
        class="space-y-8"
        x-data="{
            isPublic: {{ old('is_public', $contentType->is_public ?? true) ? 'true' : 'false' }},
            hasArchive: {{ old('has_archive', $contentType->has_archive ?? true) ? 'true' : 'false' }},
        }"
    >
        @csrf
        @isset($contentType) @method('PUT') @endisset

        {{-- Identity --}}
        <fieldset class="space-y-5">
            <legend class="text-base font-semibold text-admin-secondary border-b pb-2 w-full">Identity</legend>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="label_singular" class="admin-form-label">Singular Label <span class="text-red-500">*</span></label>
                    <input
                        id="label_singular"
                        type="text"
                        name="label_singular"
                        value="{{ old('label_singular', $contentType->label_singular ?? '') }}"
                        placeholder="e.g. Blog Post"
                        class="admin-input @error('label_singular') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                        required
                    >
                    @error('label_singular')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="label_plural" class="admin-form-label">Plural Label <span class="text-red-500">*</span></label>
                    <input
                        id="label_plural"
                        type="text"
                        name="label_plural"
                        value="{{ old('label_plural', $contentType->label_plural ?? '') }}"
                        placeholder="e.g. Blog Posts"
                        class="admin-input @error('label_plural') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                        required
                    >
                    @error('label_plural')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="slug" class="admin-form-label">Slug <span class="text-red-500">*</span></label>
                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $contentType->slug ?? '') }}"
                        placeholder="Auto-generated from singular label"
                        class="admin-input font-mono @error('slug') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                        @isset($contentType) readonly @endisset
                    >
                    <p class="mt-1 text-xs text-admin-secondary">Used in admin URLs. Cannot be changed after creation.</p>
                    @error('slug')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="icon" class="admin-form-label">Icon</label>
                    <input
                        id="icon"
                        type="text"
                        name="icon"
                        value="{{ old('icon', $contentType->icon ?? '') }}"
                        placeholder="e.g. newspaper (Font Awesome name)"
                        class="admin-input @error('icon') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                    >
                    <p class="mt-1 text-xs text-admin-secondary">
                        Any <a href="https://fontawesome.com/icons" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Font Awesome</a> icon name, e.g. <code class="font-mono">newspaper</code>, <code class="font-mono">hotel</code>, <code class="font-mono">star</code>.
                    </p>
                    @error('icon')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="admin-form-label">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="admin-textarea @error('description') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                    placeholder="Optional note about this content type's purpose."
                >{{ old('description', $contentType->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </fieldset>

        {{-- Public routing --}}
        <fieldset class="space-y-5">
            <legend class="text-base font-semibold text-admin-secondary border-b pb-2 w-full">Public Routing</legend>

            <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_public"
                        value="1"
                        class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                        x-model="isPublic"
                        @checked(old('is_public', $contentType->is_public ?? true))
                    >
                    <span class="text-sm font-medium text-admin-secondary">Public (generates frontend routes)</span>
                </label>

                <label class="inline-flex items-center gap-3 cursor-pointer" x-show="isPublic">
                    <input
                        type="checkbox"
                        name="has_archive"
                        value="1"
                        class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                        x-model="hasArchive"
                        @checked(old('has_archive', $contentType->has_archive ?? true))
                    >
                    <span class="text-sm font-medium text-admin-secondary">Has archive listing page</span>
                </label>
            </div>

            <div x-show="isPublic">
                <label for="route_base" class="admin-form-label">
                    Route Base
                    <span class="ml-1 text-xs font-normal text-admin-secondary" x-show="hasArchive">(required for archive)</span>
                </label>
                <div class="flex items-center gap-1">
                    <span class="flex-shrink-0 rounded-l-lg border border-r-0 border-admin bg-slate-50 px-3 py-2 text-sm text-admin-secondary">/</span>
                    <input
                        id="route_base"
                        type="text"
                        name="route_base"
                        value="{{ old('route_base', $contentType->route_base ?? '') }}"
                        placeholder="e.g. blog"
                        class="admin-input rounded-l-none @error('route_base') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                    >
                </div>
                <p class="mt-1 text-xs text-admin-secondary">
                    The URL prefix for this type. Archive: <code class="font-mono">/{route_base}</code> · Single: <code class="font-mono">/{route_base}/{slug}</code>
                </p>
                @error('route_base')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </fieldset>

        {{-- Supports --}}
        <fieldset class="space-y-4">
            <legend class="text-base font-semibold text-admin-secondary border-b pb-2 w-full">Supported Features</legend>
            <p class="text-sm text-admin-secondary">Choose which built-in features are available for entries of this type.</p>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($supports as $flag)
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="supports[]"
                            value="{{ $flag }}"
                            class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                            @checked(in_array($flag, old('supports', $contentType->supports ?? ['title', 'slug', 'seo', 'revisions'])))
                        >
                        <span class="text-sm text-admin-secondary capitalize">{{ str_replace('_', ' ', $flag) }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        {{-- Admin settings --}}
        <fieldset class="space-y-5">
            <legend class="text-base font-semibold text-admin-secondary border-b pb-2 w-full">Admin Settings</legend>

            <div class="w-48">
                <label for="menu_position" class="admin-form-label">Menu Position</label>
                <input
                    id="menu_position"
                    type="number"
                    name="menu_position"
                    value="{{ old('menu_position', $contentType->menu_position ?? 0) }}"
                    min="0"
                    class="admin-input @error('menu_position') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                >
                <p class="mt-1 text-xs text-admin-secondary">Lower numbers appear first in the sidebar.</p>
                @error('menu_position')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="inline-flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                    @checked(old('is_active', $contentType->is_active ?? true))
                >
                <span class="text-sm font-medium text-admin-secondary">Active (shown in sidebar and usable)</span>
            </label>
        </fieldset>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ isset($contentType) ? 'Update Content Type' : 'Create Content Type' }}
            </button>
            <a href="{{ route('admin.content-types.index') }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
