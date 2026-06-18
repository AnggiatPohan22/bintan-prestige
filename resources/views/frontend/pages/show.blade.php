@extends('layouts.frontend')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: '')
@if($page->og_image)
    @section('og_image', asset('storage/' . $page->og_image))
@endif

@section('content')

@if($preview ?? false)
    {{-- Admin-only draft/preview ribbon --}}
    <div class="sticky top-0 z-[200] flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950">
        <i class="fa-solid fa-eye"></i>
        <span>
            Preview — this page is
            <strong class="uppercase">{{ $page->status }}</strong>.
            This is not the live URL.
        </span>
    </div>
@endif

@include($templateView ?? 'frontend.templates.default', ['page' => $page])

@endsection
