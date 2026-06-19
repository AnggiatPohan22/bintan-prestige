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
    <div class="sticky top-0 z-20 -mx-4 mb-6 border-b border-slate-200 bg-white px-4 py-4 shadow-sm sm:-mx-6 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('admin.pages.index') }}"
                        class="text-slate-400 hover:text-slate-600"
                        title="Back to Pages"
                    >
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>

                    <h1 class="truncate text-lg font-extrabold text-slate-900">
                        {{ $page->title }}
                    </h1>

                    <span class="{{ $page->status === 'published' ? 'admin-badge-success' : 'admin-badge-warning' }}">
                        {{ ucfirst($page->status) }}
                    </span>
                </div>

                <p class="mt-0.5 truncate text-xs text-slate-400">
                    /pages/{{ $page->slug }}
                    &nbsp;·&nbsp;
                    Created {{ $page->created_at->format('d M Y') }}
                    &nbsp;·&nbsp;
                    Updated {{ $page->updated_at->format('d M Y, H:i') }}
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
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
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'basic' ? null : 'basic'"
            >
                <div>
                    <span class="font-extrabold text-slate-900">Basic Information</span>
                    <span class="ml-2 text-sm text-slate-400">Title, slug, status, sort order</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-200"
                    :class="active === 'basic' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'basic'" x-cloak class="border-t border-slate-100">
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
                                <p class="mt-1 text-xs text-slate-400">Reserved: admin, products, api, login, register</p>
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
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                                    value="{{ old('sort_order', $page->sort_order) }}"
                                    class="admin-input"
                                    min="0"
                                >
                                <p class="mt-1 text-xs text-slate-400">Lower number appears first.</p>
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
                                <p class="mt-1 text-xs text-slate-400">Controls the page's frontend layout.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row">
                            <button type="submit" class="admin-btn-primary">Save Basic Info</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 2. SEO Settings --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'seo' ? null : 'seo'"
            >
                <div>
                    <span class="font-extrabold text-slate-900">SEO Settings</span>
                    <span class="ml-2 text-sm text-slate-400">Meta title, description, OG image</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-200"
                    :class="active === 'seo' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'seo'" x-cloak class="border-t border-slate-100">
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
                                    <p class="text-sm text-slate-500">Current OG image. Upload a new one to replace it.</p>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="og_image"
                                accept="image/*"
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-slate-400">Max 2MB. Recommended: 1200×630px.</p>
                        </div>

                        <div class="flex gap-3 border-t border-slate-100 pt-4">
                            <button type="submit" class="admin-btn-primary">Save SEO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. Content Blocks --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'blocks' ? null : 'blocks'"
            >
                <div>
                    <span class="font-extrabold text-slate-900">Content Blocks</span>
                    <span class="ml-2 text-sm text-slate-400">{{ $page->blocks->count() }} block(s) — hero, text, image, gallery, and more</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-200"
                    :class="active === 'blocks' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'blocks'" x-cloak class="border-t border-slate-100">
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

        {{-- 4. Danger Zone --}}
        <div class="overflow-hidden rounded-xl border border-red-100 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 text-left"
                x-on:click="active = active === 'danger' ? null : 'danger'"
            >
                <div>
                    <span class="font-semibold text-red-600">Danger Zone</span>
                    <span class="ml-2 text-sm text-slate-400">Permanently delete this page</span>
                </div>
                <i
                    class="fa-solid fa-chevron-down text-slate-400 transition-transform duration-200"
                    :class="active === 'danger' ? 'rotate-180' : ''"
                ></i>
            </button>

            <div x-show="active === 'danger'" x-cloak class="border-t border-red-50">
                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Delete this page</p>
                        <p class="text-sm text-slate-500">This action is permanent and cannot be undone. All blocks will be deleted.</p>
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
