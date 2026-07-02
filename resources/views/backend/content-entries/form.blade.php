@php
    /** @var App\Models\ContentType $contentType */
    /** @var App\Models\ContentEntry|null $entry */
    $isEdit = isset($entry);
    $formAction = $isEdit
        ? route('admin.content-types.entries.update', [$contentType, $entry])
        : route('admin.content-types.entries.store', $contentType);
@endphp

<div class="admin-form-card">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                <a href="{{ route('admin.content-types.index') }}" class="hover:text-indigo-600">Content Types</a>
                <span>/</span>
                <a href="{{ route('admin.content-types.entries.index', $contentType) }}" class="hover:text-indigo-600">
                    {{ $contentType->label_plural }}
                </a>
                <span>/</span>
                <span>{{ $isEdit ? 'Edit' : 'New '.$contentType->label_singular }}</span>
            </div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ $isEdit ? ($entry->title ?? 'Edit Entry') : 'New '.$contentType->label_singular }}
            </h1>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @if($isEdit && $contentType->supports('editor'))
                <a href="{{ route('admin.content-types.entries.builder', [$contentType, $entry]) }}"
                   class="admin-btn-primary w-full sm:w-auto">
                    <i class="fa-solid fa-table-cells-large mr-1.5 text-xs"></i>
                    Edit Body in Builder
                </a>
            @endif
            <a href="{{ route('admin.content-types.entries.index', $contentType) }}" class="admin-btn-secondary w-full sm:w-auto">
                Back to Entries
            </a>
        </div>
    </div>

    @if($isEdit && $contentType->supports('editor'))
        <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm text-slate-600">
            <i class="fa-solid fa-circle-info mr-1 text-indigo-500"></i>
            This content type has a visual <strong>body</strong>. Use
            <a href="{{ route('admin.content-types.entries.builder', [$contentType, $entry]) }}" class="text-indigo-600 hover:underline">Edit Body in Builder</a>
            to compose block content. Fields below store structured data.
        </div>
    @endif

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    <form action="{{ $formAction }}" method="POST" class="space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ============================================================ CORE FIELDS --}}
        @if($contentType->supports('title') || $contentType->supports('slug'))
            <div class="space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Core</h2>

                @if($contentType->supports('title'))
                    <div>
                        <label for="title" class="admin-form-label">Title</label>
                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="{{ old('title', $entry->title ?? '') }}"
                            placeholder="{{ $contentType->label_singular }} title"
                            class="admin-input text-lg @error('title') border-red-400 @enderror"
                        >
                        @error('title') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($contentType->supports('slug'))
                    <div>
                        <label for="slug" class="admin-form-label">Slug</label>
                        <div class="flex items-center gap-2">
                            @if($contentType->route_base)
                                <span class="text-sm text-admin-secondary">/{{ $contentType->route_base }}/</span>
                            @endif
                            <input
                                id="slug"
                                type="text"
                                name="slug"
                                value="{{ old('slug', $entry->slug ?? '') }}"
                                placeholder="auto-generated-from-title"
                                class="admin-input font-mono @error('slug') border-red-400 @enderror"
                            >
                        </div>
                        <p class="mt-1 text-xs text-admin-secondary">Leave blank to auto-generate from title.</p>
                        @error('slug') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($contentType->supports('excerpt'))
                    <div>
                        <label for="excerpt" class="admin-form-label">Excerpt</label>
                        <textarea
                            id="excerpt"
                            name="excerpt"
                            rows="2"
                            placeholder="Short summary (used as meta description fallback)"
                            class="admin-textarea @error('excerpt') border-red-400 @enderror"
                        >{{ old('excerpt', $entry->excerpt ?? '') }}</textarea>
                        @error('excerpt') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- ============================================================ CUSTOM FIELD GROUPS --}}
        @if($groups->isNotEmpty())
            @foreach($groups as $group)
                <div class="space-y-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                        {{ $group->label }}
                        @if($group->description)
                            <span class="ml-2 text-xs font-normal normal-case text-admin-secondary">{{ $group->description }}</span>
                        @endif
                    </h2>

                    @forelse($group->fields as $field)
                        <x-admin.field-input
                            :field="$field"
                            :value="old('data.'.$field->key, $isEdit ? $entry->fieldValue($field->key) : null)"
                            name-prefix="data"
                        />
                    @empty
                        <p class="text-sm text-admin-secondary italic">No fields in this group yet.</p>
                    @endforelse
                </div>
            @endforeach
        @else
            <div class="rounded-lg border border-dashed border-slate-300 px-6 py-8 text-center">
                <p class="text-sm text-admin-secondary">
                    No field groups defined for this content type.
                    <a href="{{ route('admin.content-types.field-groups.index', $contentType) }}"
                       class="text-indigo-600 hover:underline">Add field groups</a> to build the entry form.
                </p>
            </div>
        @endif

        {{-- ============================================================ TAXONOMY TERMS --}}
        @include('backend.content-entries.partials.taxonomy-terms', [
            'taxonomies'      => $taxonomies ?? collect(),
            'selectedTermIds' => $selectedTermIds ?? [],
        ])

        {{-- ============================================================ SEO --}}
        @if($contentType->supports('seo'))
            <div class="space-y-4 rounded-lg border border-slate-200 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">SEO</h2>

                <div>
                    <label for="seo_title" class="admin-form-label">SEO Title</label>
                    <input
                        id="seo_title"
                        type="text"
                        name="seo[title]"
                        value="{{ old('seo.title', $entry->seo['title'] ?? '') }}"
                        maxlength="200"
                        placeholder="Defaults to entry title"
                        class="admin-input"
                    >
                </div>

                <div>
                    <label for="seo_description" class="admin-form-label">Meta Description</label>
                    <textarea
                        id="seo_description"
                        name="seo[description]"
                        rows="2"
                        maxlength="500"
                        placeholder="Defaults to excerpt"
                        class="admin-textarea"
                    >{{ old('seo.description', $entry->seo['description'] ?? '') }}</textarea>
                </div>

                <div>
                    <label for="seo_canonical" class="admin-form-label">Canonical URL</label>
                    <input
                        id="seo_canonical"
                        type="url"
                        name="seo[canonical]"
                        value="{{ old('seo.canonical', $entry->seo['canonical'] ?? '') }}"
                        placeholder="https://"
                        class="admin-input font-mono"
                    >
                </div>
            </div>
        @endif

        {{-- ============================================================ PUBLISH SETTINGS --}}
        <div
            x-data="{ status: @js(old('status', $entry->status ?? 'draft')) }"
            class="rounded-lg border border-slate-200 p-5 space-y-4"
        >
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Publish</h2>

            <div>
                <label for="status" class="admin-form-label">Status</label>
                <select
                    id="status"
                    name="status"
                    x-model="status"
                    class="admin-input @error('status') border-red-400 @enderror"
                >
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    @if($contentType->supports('scheduling'))
                        <option value="scheduled">Scheduled</option>
                    @endif
                    <option value="archived">Archived</option>
                </select>
                @error('status') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div x-show="status === 'published' || status === 'scheduled'">
                <label for="published_at" class="admin-form-label">
                    Publish Date
                    <span x-show="status === 'scheduled'" class="text-red-500">*</span>
                </label>
                <input
                    id="published_at"
                    type="datetime-local"
                    name="published_at"
                    value="{{ old('published_at', isset($entry->published_at) ? $entry->published_at->format('Y-m-d\TH:i') : '') }}"
                    class="admin-input @error('published_at') border-red-400 @enderror"
                >
                @error('published_at') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="template" class="admin-form-label">Template Override</label>
                    <input
                        id="template"
                        type="text"
                        name="template"
                        value="{{ old('template', $entry->template ?? '') }}"
                        placeholder="e.g. full-width"
                        class="admin-input font-mono @error('template') border-red-400 @enderror"
                    >
                </div>

                <div>
                    <label for="sort_order" class="admin-form-label">Sort Order</label>
                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        value="{{ old('sort_order', $entry->sort_order ?? 0) }}"
                        min="0"
                        class="admin-input"
                    >
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ $isEdit ? 'Update '.$contentType->label_singular : 'Create '.$contentType->label_singular }}
            </button>
            <a href="{{ route('admin.content-types.entries.index', $contentType) }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
