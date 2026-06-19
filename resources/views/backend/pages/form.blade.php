<form
    action="{{ isset($page)
        ? route('admin.pages.update', $page)
        : route('admin.pages.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="space-y-8"
>
    @csrf

    @isset($page)
        @method('PUT')
    @endisset

    {{-- Basic --}}
    <div>
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">
            Basic
        </h2>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="title" class="admin-form-label">
                    Title <span class="text-red-500">*</span>
                </label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $page->title ?? '') }}"
                    class="admin-input @error('title') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    required
                    placeholder="e.g. About Us"
                >
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="admin-form-label">Slug</label>
                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ old('slug', $page->slug ?? '') }}"
                    class="admin-input @error('slug') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    placeholder="Auto-generated from title if empty"
                >
                @error('slug')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">
                    Reserved slugs not allowed: admin, products, api, login, register
                </p>
            </div>

            <div>
                <label for="status" class="admin-form-label">
                    Status <span class="text-red-500">*</span>
                </label>
                <select
                    id="status"
                    name="status"
                    class="admin-input @error('status') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                    <option value="draft" @selected(old('status', $page->status ?? 'draft') === 'draft')>
                        Draft
                    </option>
                    <option value="published" @selected(old('status', $page->status ?? '') === 'published')>
                        Published
                    </option>
                </select>
                @error('status')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-2 space-y-1 text-xs text-slate-500">
                    <p><strong class="text-amber-700">Draft:</strong> admin preview only; unavailable on the public URL and hidden from managed menus.</p>
                    <p><strong class="text-emerald-700">Published:</strong> live on the public URL and eligible for managed menus.</p>
                </div>
            </div>

            <div>
                <label for="sort_order" class="admin-form-label">Sort Order</label>
                <input
                    id="sort_order"
                    type="number"
                    name="sort_order"
                    value="{{ old('sort_order', $page->sort_order ?? 0) }}"
                    class="admin-input @error('sort_order') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    min="0"
                >
                @error('sort_order')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">Lower number appears first.</p>
            </div>

            <div>
                <label for="template_id" class="admin-form-label">Template</label>
                <select
                    id="template_id"
                    name="template_id"
                    class="admin-input @error('template_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                    <option value="">Default (Standard)</option>
                    @foreach($templates ?? [] as $template)
                        <option value="{{ $template->id }}" @selected((int) old('template_id', $page->template_id ?? 0) === $template->id)>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
                @error('template_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">Controls the page's frontend layout.</p>
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="border-t border-slate-100 pt-6">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">
            SEO
        </h2>

        <div class="grid grid-cols-1 gap-5">
            <div>
                <label for="meta_title" class="admin-form-label">Meta Title</label>
                <input
                    id="meta_title"
                    type="text"
                    name="meta_title"
                    value="{{ old('meta_title', $page->meta_title ?? '') }}"
                    class="admin-input @error('meta_title') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    placeholder="Defaults to page title if empty"
                    maxlength="255"
                >
                @error('meta_title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="meta_description" class="admin-form-label">Meta Description</label>
                <textarea
                    id="meta_description"
                    name="meta_description"
                    rows="3"
                    class="admin-textarea @error('meta_description') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    placeholder="Defaults to global SEO description if empty"
                    maxlength="500"
                >{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
                @error('meta_description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="og_image" class="admin-form-label">OG Image</label>

                @if(isset($page) && $page->og_image)
                    <div class="mb-3 flex items-center gap-4">
                        <img
                            src="{{ asset('storage/' . $page->og_image) }}"
                            alt="OG image preview"
                            class="h-20 w-36 rounded-lg object-cover shadow"
                        >
                        <p class="text-sm text-slate-500">
                            Current OG image. Upload a new one to replace it.
                        </p>
                    </div>
                @endif

                <input
                    id="og_image"
                    type="file"
                    name="og_image"
                    accept="image/*"
                    class="admin-input @error('og_image') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                @error('og_image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-400">
                    Max 2MB. Recommended: 1200×630px. Defaults to global OG image if empty.
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
        <button type="submit" class="admin-btn-primary w-full sm:w-auto">
            {{ isset($page) ? 'Update Page' : 'Create Page' }}
        </button>

        <a
            href="{{ route('admin.pages.index') }}"
            class="admin-btn-secondary w-full sm:w-auto"
        >
            Cancel
        </a>
    </div>
</form>
