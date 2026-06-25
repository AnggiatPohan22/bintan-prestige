@extends('layouts.admin')

@section('content')

@php
    $openSection = old('_editor_context') === 'blocks' && $errors->any()
        ? 'blocks'
        : session('open_section', 'basic');
@endphp

<div
    class="admin-page"
    x-data="{ active: '{{ $openSection }}' }"
>

    {{-- ─── Sticky Page Header ─────────────────────────────────────────── --}}
    <div class="sticky top-0 z-20 -mx-4 mb-6 border-b border-admin bg-admin-card px-4 py-4 sm:-mx-6 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('admin.pages.index') }}"
                        class="text-admin-secondary hover:text-admin-secondary"
                        title="Back to Pages"
                    >
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>

                    <h1 class="truncate text-lg font-extrabold text-admin-primary">
                        {{ $page->title }}
                    </h1>

                    @if($page->isPublished())
                        <span class="admin-badge-success">Published</span>
                    @elseif($page->isScheduled())
                        <span class="admin-badge-warning">Scheduled</span>
                    @else
                        <span class="admin-badge-warning">Draft</span>
                    @endif
                </div>

                <p class="mt-0.5 truncate text-xs text-admin-secondary">
                    /pages/{{ $page->slug }}
                    &nbsp;·&nbsp;
                    Created {{ $page->created_at->format('d M Y') }}
                    &nbsp;·&nbsp;
                    Updated {{ $page->updated_at->format('d M Y, H:i') }}
                    @if($page->isScheduled() && $page->publish_at)
                        &nbsp;·&nbsp;
                        <span class="font-medium text-amber-700">Scheduled for: {{ $page->publish_at->format('d M Y, H:i') }}</span>
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <a
                    href="{{ route('admin.pages.builder', $page) }}"
                    class="admin-btn-primary text-sm"
                >
                    <i class="fa-solid fa-wand-magic-sparkles mr-1 text-xs"></i>
                    Visual Builder
                </a>

                <a
                    href="{{ route('admin.pages.preview', $page) }}"
                    target="_blank"
                    class="admin-btn-secondary text-sm"
                >
                    <i class="fa-solid fa-eye mr-1 text-xs"></i>
                    {{ $page->isPublished() ? 'Preview' : 'Preview Draft' }}
                </a>

                @if($page->isPublished())
                    <a
                        href="{{ route('pages.show', $page->slug) }}"
                        target="_blank"
                        class="admin-btn-secondary text-sm"
                    >
                        <i class="fa-solid fa-arrow-up-right-from-square mr-1 text-xs"></i>
                        View Live
                    </a>
                @endif

                <a
                    href="{{ route('admin.pages.index') }}"
                    class="admin-btn-secondary text-sm"
                >
                    Back to Pages
                </a>
            </div>
        </div>
    </div>

    {{-- ─── Accordion Sections ─────────────────────────────────────────── --}}
    <div class="space-y-3">

        {{-- 1. Basic Information --}}
        <div class="overflow-hidden rounded-xl border border-admin bg-admin-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'basic' ? null : 'basic'"
            >
                <div>
                    <span class="font-extrabold text-admin-primary">Basic Information</span>
                    <span class="ml-2 text-sm text-admin-secondary">Title, slug, status, sort order</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-admin-secondary transition-transform duration-200"
                    :class="active === 'basic' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'basic'" x-cloak class="border-t border-admin/50">
                <div class="px-6 py-6">
                    <form
                        action="{{ route('admin.pages.update', $page) }}"
                        method="POST"
                        class="space-y-5"
                    >
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label for="title" class="admin-form-label">
                                    Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    name="title"
                                    value="{{ old('title', $page->title) }}"
                                    class="admin-input @error('title') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    required
                                >
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="slug" class="admin-form-label">Slug</label>
                                <input
                                    id="slug"
                                    type="text"
                                    name="slug"
                                    value="{{ old('slug', $page->slug) }}"
                                    class="admin-input @error('slug') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="Auto-generated from title if empty"
                                >
                                @error('slug')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-admin-secondary">Reserved: admin, products, api, login, register</p>
                            </div>

                            <div>
                                <label for="status" class="admin-form-label">Status <span class="text-red-500">*</span></label>
                                <select
                                    id="status"
                                    name="status"
                                    class="admin-input @error('status') border-red-300 @enderror"
                                >
                                    <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                                    <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                                    <option value="scheduled" @selected(old('status', $page->status) === 'scheduled')>Scheduled</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-2 space-y-1 text-xs text-admin-secondary">
                                    <p><strong class="text-amber-700">Draft:</strong> admin preview only; unavailable on the public URL and hidden from managed menus.</p>
                                    <p><strong class="text-emerald-700">Published:</strong> live on the public URL and eligible for managed menus.</p>
                                </div>
                            </div>

                            <div>
                                <label for="publish_at" class="admin-form-label">Schedule Publish Date</label>
                                <input
                                    id="publish_at"
                                    type="datetime-local"
                                    name="publish_at"
                                    value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}"
                                    class="admin-input @error('publish_at') border-red-300 @enderror"
                                >
                                @error('publish_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-admin-secondary">
                                    Set a future date/time to schedule this page. Leave empty to publish or save as draft manually.
                                    If the date is in the past, the page will be published immediately.
                                </p>
                                @if($page->isScheduled() && $page->publish_at)
                                    <p class="mt-1 text-xs font-medium" style="color:#854d0e;">
                                        <i class="fa-solid fa-clock mr-1"></i>
                                        Scheduled for: {{ $page->publish_at->format('d M Y, H:i') }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label for="sort_order" class="admin-form-label">Sort Order</label>
                                <input
                                    id="sort_order"
                                    type="number"
                                    name="sort_order"
                                    value="{{ old('sort_order', $page->sort_order) }}"
                                    class="admin-input"
                                    min="0"
                                >
                                <p class="mt-1 text-xs text-admin-secondary">Lower number appears first.</p>
                            </div>

                            <div>
                                <label for="template_id" class="admin-form-label">Template</label>
                                <select id="template_id" name="template_id" class="admin-input">
                                    <option value="">Default (Standard)</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" @selected((int) old('template_id', $page->template_id) === $template->id)>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-admin-secondary">Controls the page's frontend layout.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-admin/50 pt-4 sm:flex-row">
                            <button type="submit" class="admin-btn-primary">Save Basic Info</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 2. SEO Settings --}}
        <div class="overflow-hidden rounded-xl border border-admin bg-admin-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'seo' ? null : 'seo'"
            >
                <div>
                    <span class="font-extrabold text-admin-primary">SEO Settings</span>
                    <span class="ml-2 text-sm text-admin-secondary">Meta title, description, OG image</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-admin-secondary transition-transform duration-200"
                    :class="active === 'seo' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'seo'" x-cloak class="border-t border-admin/50">
                <div class="px-6 py-6">
                    <form
                        action="{{ route('admin.pages.update', $page) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >
                        @csrf
                        @method('PUT')

                        {{-- carry forward required fields so validation passes --}}
                        <input type="hidden" name="title" value="{{ $page->title }}">
                        <input type="hidden" name="slug" value="{{ $page->slug }}">
                        <input type="hidden" name="status" value="{{ $page->status }}">
                        <input type="hidden" name="sort_order" value="{{ $page->sort_order }}">
                        <input type="hidden" name="template_id" value="{{ $page->template_id }}">
                        <input type="hidden" name="publish_at" value="{{ $page->publish_at?->format('Y-m-d\TH:i') }}">

                        <div>
                            <label for="meta_title" class="admin-form-label">Meta Title</label>
                            <input
                                id="meta_title"
                                type="text"
                                name="meta_title"
                                value="{{ old('meta_title', $page->meta_title) }}"
                                class="admin-input"
                                placeholder="Defaults to page title if empty"
                                maxlength="255"
                            >
                        </div>

                        <div>
                            <label for="meta_description" class="admin-form-label">Meta Description</label>
                            <textarea
                                id="meta_description"
                                name="meta_description"
                                rows="3"
                                class="admin-textarea"
                                placeholder="Defaults to global SEO description if empty"
                                maxlength="500"
                            >{{ old('meta_description', $page->meta_description) }}</textarea>
                        </div>

                        <div>
                            <label class="admin-form-label">OG Image</label>

                            @if($page->og_image)
                                <div class="mb-3 flex items-center gap-4">
                                    <img
                                        src="{{ asset('storage/' . $page->og_image) }}"
                                        alt="OG image"
                                        class="h-20 w-36 rounded-lg object-cover shadow"
                                    >
                                    <p class="text-sm text-admin-secondary">Current OG image. Upload a new one to replace it.</p>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="og_image"
                                accept="image/*"
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-admin-secondary">Max 2MB. Recommended: 1200×630px.</p>
                        </div>

                        <div>
                            <label class="admin-form-label" for="seo_robots">Robots (Search Engine Indexing)</label>
                            <select id="seo_robots" name="seo_robots" class="admin-input w-full max-w-xs">
                                <option value="" @selected(old('seo_robots', $page->seo_robots) === '')>Default (use global setting)</option>
                                <option value="index, follow" @selected(old('seo_robots', $page->seo_robots) === 'index, follow')>index, follow</option>
                                <option value="noindex, follow" @selected(old('seo_robots', $page->seo_robots) === 'noindex, follow')>noindex, follow</option>
                                <option value="noindex, nofollow" @selected(old('seo_robots', $page->seo_robots) === 'noindex, nofollow')>noindex, nofollow</option>
                            </select>
                            <p class="admin-form-hint">Override robots meta tag for this page only.</p>
                        </div>

                        <div class="flex gap-3 border-t border-admin/50 pt-4">
                            <button type="submit" class="admin-btn-primary">Save SEO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. Content Blocks --}}
        <div class="overflow-hidden rounded-xl border border-admin bg-admin-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'blocks' ? null : 'blocks'"
            >
                <div>
                    <span class="font-extrabold text-admin-primary">Content Blocks</span>
                    <span class="ml-2 text-sm text-admin-secondary">{{ $page->blocks->count() }} block(s) — hero, text, image, gallery, and more</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-admin-secondary transition-transform duration-200"
                    :class="active === 'blocks' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'blocks'" x-cloak class="border-t border-admin/50">
                <div class="p-4">
                    @include('backend.pages.partials.block-editor', [
                        'page'         => $page,
                        'blocks'       => $page->blocks,
                        'blockTypes'   => $blockTypes,
                        'categories'   => $categories,
                        'destinations' => $destinations,
                    ])
                </div>
            </div>
        </div>

        {{-- 4. Revisions --}}
        @if($revisions->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-admin bg-admin-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'revisions' ? null : 'revisions'"
            >
                <div>
                    <span class="font-extrabold text-admin-primary">Revisions</span>
                    <span class="ml-2 text-sm text-admin-secondary">{{ $revisions->count() }} saved snapshot(s) — restore any previous state</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-admin-secondary transition-transform duration-200"
                    :class="active === 'revisions' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'revisions'" x-cloak class="border-t border-admin/50">
                <div class="divide-y divide-slate-700/50">
                    @foreach($revisions as $revision)
                        <div
                            class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                            x-data="{ previewOpen: false, restoreOpen: false }"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-admin-primary">
                                        Revision #{{ $revision->revision_number }}
                                    </span>
                                    <span class="text-xs text-admin-secondary">
                                        {{ $revision->created_at->format('d M Y, H:i') }}
                                    </span>
                                    @if($revision->author)
                                        <span class="text-xs text-admin-secondary">
                                            by {{ $revision->author->name }}
                                        </span>
                                    @endif
                                </div>
                                @if(!empty($revision->meta_snapshot['title']))
                                    <p class="mt-0.5 truncate text-xs text-admin-secondary">
                                        &ldquo;{{ $revision->meta_snapshot['title'] }}&rdquo;
                                        &nbsp;&middot;&nbsp;
                                        {{ count($revision->content_snapshot) }} block(s)
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 gap-2">
                                {{-- Preview --}}
                                <button
                                    type="button"
                                    class="admin-btn-secondary py-1.5 text-xs"
                                    x-on:click="previewOpen = true"
                                >
                                    <i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>
                                    Preview
                                </button>

                                {{-- Restore --}}
                                <button
                                    type="button"
                                    class="rounded-lg border border-violet-500/30 bg-violet-900/20 px-3 py-1.5 text-xs font-semibold text-violet-400 hover:bg-violet-900/30 transition-colors"
                                    x-on:click="restoreOpen = true"
                                >
                                    <i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>
                                    Restore
                                </button>
                            </div>

                            {{-- Preview modal --}}
                            <div
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                                x-cloak x-show="previewOpen" x-transition.opacity
                            >
                                <div
                                    class="flex max-h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-admin-card shadow-xl"
                                    x-on:click.outside="previewOpen = false"
                                >
                                    <div class="flex items-center justify-between border-b border-admin/50 px-6 py-4">
                                        <h3 class="font-bold text-admin-primary">
                                            Revision #{{ $revision->revision_number }} Preview
                                        </h3>
                                        <button type="button" x-on:click="previewOpen = false" class="text-admin-secondary hover:text-admin-secondary">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="overflow-y-auto px-6 py-4 space-y-4 text-sm">
                                        @if($revision->meta_snapshot)
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-admin-secondary">Meta</p>
                                                <dl class="space-y-1">
                                                    @foreach(['title' => 'Title', 'slug' => 'Slug', 'status' => 'Status', 'meta_title' => 'Meta Title'] as $key => $label)
                                                        @if(!empty($revision->meta_snapshot[$key]))
                                                            <div class="flex gap-2">
                                                                <dt class="w-24 shrink-0 text-admin-secondary">{{ $label }}</dt>
                                                                <dd class="min-w-0 truncate font-medium text-slate-300">{{ $revision->meta_snapshot[$key] }}</dd>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </dl>
                                            </div>
                                        @endif

                                        @if(count($revision->content_snapshot) > 0)
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-admin-secondary">
                                                    Blocks ({{ count($revision->content_snapshot) }})
                                                </p>
                                                <ol class="space-y-1">
                                                    @foreach($revision->content_snapshot as $i => $block)
                                                        <li class="flex items-center gap-2 rounded-lg bg-admin-card px-3 py-2">
                                                            <span class="w-5 shrink-0 text-center text-xs text-admin-secondary">{{ $i + 1 }}</span>
                                                            <span class="rounded bg-admin-card px-1.5 py-0.5 text-xs font-mono text-admin-secondary">{{ $block['block_type'] }}</span>
                                                            @if(!empty($block['label']))
                                                                <span class="min-w-0 truncate text-xs text-admin-secondary">{{ $block['label'] }}</span>
                                                            @endif
                                                            @if(!($block['is_visible'] ?? true))
                                                                <span class="ml-auto rounded bg-amber-100 px-1 py-0.5 text-xs text-amber-600">hidden</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @else
                                            <p class="text-xs text-admin-secondary">No blocks in this revision.</p>
                                        @endif
                                    </div>
                                    <div class="border-t border-admin/50 px-6 py-3">
                                        <button type="button" class="admin-btn-secondary py-1.5 text-sm w-full" x-on:click="previewOpen = false">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Restore confirm modal --}}
                            <div
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                                x-cloak x-show="restoreOpen" x-transition.opacity
                            >
                                <div
                                    class="w-full max-w-sm rounded-2xl bg-admin-card p-6 shadow-xl"
                                    x-on:click.outside="restoreOpen = false"
                                >
                                    <h3 class="mb-2 text-base font-bold text-admin-primary">Restore Revision</h3>
                                    <p class="mb-5 text-sm text-admin-secondary">
                                        Restore this page to
                                        <strong>Revision #{{ $revision->revision_number }}</strong>
                                        ({{ $revision->created_at->format('d M Y, H:i') }})?
                                        <br><span class="mt-1 block text-xs text-admin-secondary">The current state will be auto-saved as a new revision before restoring.</span>
                                    </p>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="admin-btn-secondary py-1.5 text-sm" x-on:click="restoreOpen = false">Cancel</button>
                                        <form method="POST" action="{{ route('admin.pages.revisions.restore', [$page, $revision]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- 5. Danger Zone --}}
        <div class="overflow-hidden rounded-xl border border-red-100 bg-admin-card">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'danger' ? null : 'danger'"
            >
                <div>
                    <span class="font-semibold text-red-600">Danger Zone</span>
                    <span class="ml-2 text-sm text-admin-secondary">Permanently delete this page</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-admin-secondary transition-transform duration-200"
                    :class="active === 'danger' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'danger'" x-cloak class="border-t border-red-50">
                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-300">Delete this page</p>
                        <p class="text-sm text-admin-secondary">This action is permanent and cannot be undone. All blocks will be deleted.</p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.pages.destroy', $page) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Permanently delete this page? This cannot be undone.')"
                            class="admin-btn-danger whitespace-nowrap"
                        >
                            Delete Page
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- /accordion --}}

</div>{{-- /admin-page --}}

@endsection
