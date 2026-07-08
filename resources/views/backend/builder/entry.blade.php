@extends('layouts.builder')

@section('builder-title', 'Builder: ' . ($entry->title ?? 'Entry'))

@push('head')
<style>
.sortable-ghost  { opacity: .25; background: rgb(71 85 105/.4); border-radius: .5rem; }
.sortable-chosen { opacity: .85; box-shadow: 0 8px 32px rgb(0 0 0/.6); }
.builder-pane-scroll { scrollbar-gutter: stable; }
.builder-input {
    width: 100%; border-radius: .5rem; border: 1px solid rgb(51 65 85);
    background: rgb(30 41 59); padding: .5rem .75rem; font-size: .875rem; color: rgb(226 232 240);
}
.builder-input::placeholder { color: rgb(100 116 139); }
.builder-input:focus { outline: none; border-color: rgb(245 158 11); }
.builder-label { display: block; margin-bottom: .25rem; font-size: .75rem; font-weight: 500; color: rgb(148 163 184); }
</style>
@endpush

@push('scripts')
@include('backend.builder.partials.alpine-component')
@endpush

@section('content')
@php
    $categorized = collect($registry)->groupBy('category', true);
    $catOrder    = ['layout', 'content', 'media', 'conversion', 'travel'];
@endphp

<div
    x-data="pageBuilder({
        tree:       {{ Js::from($tree) }},
        csrf:       {{ Js::from(csrf_token()) }},
        previewUrl: {{ Js::from(route('admin.content-types.entries.builder.preview-payload', [$contentType, $entry])) }},
        saveUrl:    {{ Js::from(route('admin.content-types.entries.builder.save-tree', [$contentType, $entry])) }},
        registry:   {{ Js::from($registry) }},
        options:    {{ Js::from($fieldOptions) }},
        uploadUrl:  {{ Js::from(route('admin.media.upload-quick')) }},
        patternsUrl: {{ Js::from(route('admin.builder-patterns.index')) }},
        layoutTemplates: {{ Js::from([]) }},
        currentTemplateId: null,
        builderTemplatesUrl: null,
        storeBuilderTemplateUrl: null,
        pageTitle: {{ Js::from($entry->title ?? '') }},
    })"
    x-on:media-picker-selected.window="onMediaPicked($event.detail)"
    class="flex h-screen flex-col bg-admin-card"
>

    @include('backend.builder.partials.topbar-entry')

    <div class="relative flex min-h-0 flex-1 overflow-hidden">
        <div
            x-show="!leftCollapsed || !rightCollapsed"
            x-on:click="leftCollapsed = true; rightCollapsed = true"
            x-transition.opacity
            class="absolute inset-0 z-30 bg-black/50 lg:hidden"
            x-cloak
        ></div>

        @include('backend.builder.partials.panel-left')
        @include('backend.builder.partials.canvas')
        @include('backend.builder.partials.panel-right')
    </div>

    @include('backend.media.partials.picker-modal')

</div>

@endsection
