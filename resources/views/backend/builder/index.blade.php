@extends('layouts.builder')

@section('builder-title', 'Builder: ' . $page->title)

@push('head')
<style>
.sortable-ghost  { opacity: .25; background: rgb(71 85 105/.4); border-radius: .5rem; }
.sortable-chosen { opacity: .85; box-shadow: 0 8px 32px rgb(0 0 0/.6); }
/* Reserve the scrollbar lane on every left-panel tab so the inner width never
   shifts when a scrollbar appears (Windows scrollbars take ~17px of layout). */
.builder-pane-scroll { scrollbar-gutter: stable; }
/* Shared settings-panel control (dark builder theme). Keeps field markup terse. */
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
    // View-prep only (grouping a passed array — no DB query).
    $categorized = collect($registry)->groupBy('category', true);
    $catOrder    = ['layout', 'content', 'media', 'conversion', 'travel'];
@endphp

{{--
    Visual Builder layout — see docs/visual-builder-structure.md
    Grid (lg+):  LEFT 20%  :  CANVAS 60% (flex-1)  :  RIGHT 20%
    Full-viewport flex column; the 3-panel row uses min-h-0 so each column
    honours its own overflow. Nothing here is heavy logic — partials only.
--}}
<div
    x-data="pageBuilder({
        tree:       {{ Js::from($tree) }},
        csrf:       {{ Js::from(csrf_token()) }},
        previewUrl: {{ Js::from(route('admin.pages.preview-payload', $page)) }},
        saveUrl:    {{ Js::from(route('admin.page-blocks.save-tree', $page)) }},
        registry:   {{ Js::from($registry) }},
        options:    {{ Js::from($fieldOptions) }},
        uploadUrl:  {{ Js::from(route('admin.media.upload-quick')) }},
    })"
    x-on:media-picker-selected.window="onMediaPicked($event.detail)"
    class="flex h-screen flex-col bg-slate-950"
>

    @include('backend.builder.partials.topbar')

    {{-- THREE-PANEL ROW — min-h-0 lets each column scroll independently. --}}
    <div class="relative flex min-h-0 flex-1 overflow-hidden">

        {{-- Mobile drawer backdrop — closes either open panel when tapped.
             Only present below lg, where the panels float over the canvas. --}}
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

    </div>{{-- /three-panel --}}

    {{-- Media Library picker modal — reused by image fields via the
         open-media-picker / media-picker-selected event protocol. --}}
    @include('backend.media.partials.picker-modal')

</div>{{-- /x-data pageBuilder --}}

@endsection
