@php
    /** @var App\Models\Taxonomy $taxonomy */
    /** @var App\Models\Term|null $term */
    /** @var \Illuminate\Database\Eloquent\Collection<int, App\Models\Term> $parents */
    $isEdit     = isset($term);
    $formAction = $isEdit
        ? route('admin.taxonomies.terms.update', [$taxonomy, $term])
        : route('admin.taxonomies.terms.store', $taxonomy);
@endphp

<div class="admin-form-card">

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                <a href="{{ route('admin.taxonomies.index') }}" class="hover:text-indigo-600">Taxonomies</a>
                <span>/</span>
                <a href="{{ route('admin.taxonomies.terms.index', $taxonomy) }}" class="hover:text-indigo-600">
                    {{ $taxonomy->label_plural }}
                </a>
                <span>/</span>
                <span>{{ $isEdit ? 'Edit' : 'New '.$taxonomy->label_singular }}</span>
            </div>
            <h1 class="text-2xl font-bold text-admin-secondary">
                {{ $isEdit ? 'Edit '.$term->name : 'New '.$taxonomy->label_singular }}
            </h1>
        </div>
        <a href="{{ route('admin.taxonomies.terms.index', $taxonomy) }}" class="admin-btn-secondary w-full sm:w-auto">
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    <form action="{{ $formAction }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Name --}}
        <div>
            <label for="name" class="admin-form-label">Name <span class="text-red-500">*</span></label>
            <input id="name" type="text" name="name"
                   value="{{ old('name', $term->name ?? '') }}"
                   placeholder="e.g. North Bintan"
                   class="admin-input @error('name') border-red-400 @enderror">
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Slug --}}
        <div>
            <label for="slug" class="admin-form-label">Slug <span class="text-red-500">*</span></label>
            <input id="slug" type="text" name="slug"
                   value="{{ old('slug', $term->slug ?? '') }}"
                   placeholder="auto-generated-from-name"
                   class="admin-input font-mono @error('slug') border-red-400 @enderror">
            <p class="mt-1 text-xs text-admin-secondary">Must be unique within this taxonomy.</p>
            @error('slug') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="admin-form-label">Description</label>
            <textarea id="description" name="description" rows="2"
                      placeholder="Optional description"
                      class="admin-textarea @error('description') border-red-400 @enderror"
            >{{ old('description', $term->description ?? '') }}</textarea>
            @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Parent (hierarchical only) --}}
        @if($taxonomy->is_hierarchical && $parents->isNotEmpty())
            <div>
                <label for="parent_id" class="admin-form-label">Parent {{ $taxonomy->label_singular }}</label>
                <select id="parent_id" name="parent_id"
                        class="admin-input @error('parent_id') border-red-400 @enderror">
                    <option value="">— None (root level) —</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}"
                                {{ old('parent_id', $term->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Sort Order --}}
        <div class="w-32">
            <label for="sort_order" class="admin-form-label">Sort Order</label>
            <input id="sort_order" type="number" name="sort_order" min="0"
                   value="{{ old('sort_order', $term->sort_order ?? 0) }}"
                   class="admin-input">
        </div>

        {{-- Per-locale translations (Phase 7 — B7) --}}
        @php $__termLocales = \App\Support\Locales::nonDefaultActive(); @endphp
        @if(count($__termLocales) > 0 && $isEdit)
            <div class="rounded-xl border border-violet-200 bg-violet-50/40 p-4">
                <p class="mb-2 text-sm font-bold text-admin-secondary">🌐 Translations</p>
                <div class="space-y-4">
                    @foreach($__termLocales as $__loc)
                        <div class="rounded-lg border border-violet-200 bg-admin-card p-3">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="rounded bg-violet-600 px-2 py-0.5 text-xs font-bold uppercase text-white">{{ $__loc }}</span>
                                <span class="text-xs text-admin-secondary opacity-70">Leave blank to fall back to the default language.</span>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <label class="admin-form-label">Name</label>
                                    <input type="text" name="translations[{{ $__loc }}][name]"
                                           value="{{ old("translations.$__loc.name", $term->rawTranslation('name', $__loc)) }}"
                                           class="admin-input">
                                </div>
                                <div>
                                    <label class="admin-form-label">Description</label>
                                    <textarea name="translations[{{ $__loc }}][description]" rows="2" class="admin-input">{{ old("translations.$__loc.description", $term->rawTranslation('description', $__loc)) }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Submit --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="admin-btn-primary">
                {{ $isEdit ? 'Update '.$taxonomy->label_singular : 'Create '.$taxonomy->label_singular }}
            </button>
            <a href="{{ route('admin.taxonomies.terms.index', $taxonomy) }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
