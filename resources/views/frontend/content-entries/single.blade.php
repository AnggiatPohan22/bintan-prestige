@extends('layouts.frontend')

@section('content')
<div class="cms-page">

    {{-- Entry header --}}
    <header class="mx-auto max-w-3xl px-6 pt-16 text-center sm:pt-20">
        @if($type->has_archive && $type->route_base)
            <a href="{{ url($type->route_base) }}"
               class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                {{ $type->label_plural }}
            </a>
        @endif
        <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ $entry->title }}</h1>
        @if($entry->excerpt)
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-500">{{ $entry->excerpt }}</p>
        @endif
    </header>

    {{-- Builder block body (content types with `editor` support) --}}
    @if($blocks->isNotEmpty())
        <div class="mt-12">
            @foreach($blocks as $block)
                @includeIf('frontend.blocks.' . str_replace('_', '-', $block->block_type), [
                    'block' => $block,
                    'data'  => $block->data ?? [],
                ])
            @endforeach
        </div>
    @elseif(! $type->supports('editor'))
        {{-- Non-editor types: nothing to render beyond the header for now.
             Structured field display is surfaced via builder content_field blocks (B14). --}}
    @endif

</div>
@endsection
