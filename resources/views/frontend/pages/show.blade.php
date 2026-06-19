@extends('layouts.frontend')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: '')
@if($page->og_image)
    @section('og_image', asset('storage/' . $page->og_image))
@endif

@section('content')

@if($preview ?? false)
    {{-- Admin-only draft/preview ribbon --}}
    <div class="sticky top-0 z-[200] flex flex-wrap items-center justify-center gap-x-3 gap-y-2 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950">
        <i class="fa-solid fa-eye" aria-hidden="true"></i>
        <span>
            Preview — this page is
            <strong class="uppercase">{{ $page->status }}</strong>.
            This is not the live URL.
        </span>
        <a
            href="{{ route('admin.pages.edit', $page) }}"
            class="rounded-full border border-amber-900/30 bg-white/70 px-3 py-1 text-xs font-bold text-amber-950 transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-amber-950"
        >
            Back to editor
        </a>
    </div>
@endif

@include($templateView ?? 'frontend.templates.default', ['page' => $page])

@endsection
