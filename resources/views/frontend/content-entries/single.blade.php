@extends('layouts.frontend')

@section('content')
@php
    // Structured data (JSON-LD) resolved from the entry's template schema type.
    $publicUrl = $entry->publicUrl();
    $structured = array_filter([
        '@context'      => 'https://schema.org',
        '@type'         => $schemaType,
        'headline'      => $entry->title,
        'name'          => $entry->title,
        'description'   => $seoDescription,
        'url'           => $publicUrl,
        'datePublished' => $entry->published_at?->toIso8601String(),
        'dateModified'  => $entry->updated_at?->toIso8601String(),
        'author'        => $entry->author?->name ? ['@type' => 'Person', 'name' => $entry->author->name] : null,
        'mainEntityOfPage' => $publicUrl ? ['@type' => 'WebPage', '@id' => $publicUrl] : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<script type="application/ld+json">{!! json_encode($structured, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<div class="cms-page">

    {{-- Entry header --}}
    <header class="{{ $templateContainer }} pt-16 text-center sm:pt-20">
        @if($type->has_archive && $type->route_base)
            <a href="{{ url($type->route_base) }}"
               class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                {{ $type->label_plural }}
            </a>
        @endif
        <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ $entry->title }}</h1>
        @if($entry->published_at)
            <p class="mt-3 text-sm text-slate-400">
                <time datetime="{{ $entry->published_at->toDateString() }}">{{ $entry->published_at->format('d M Y') }}</time>
            </p>
        @endif
        @if($entry->excerpt)
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-500">{{ $entry->excerpt }}</p>
        @endif
    </header>

    {{-- Builder block body (content types with `editor` support) --}}
    @if($blocks->isNotEmpty())
        <div class="{{ $templateKey === 'full-width' ? 'mt-12' : $templateContainer.' mt-12' }}">
            @foreach($blocks as $block)
                @includeIf('frontend.blocks.' . str_replace('_', '-', $block->block_type), [
                    'block' => $block,
                    'data'  => $block->data ?? [],
                ])
            @endforeach
        </div>
    @endif

</div>
@endsection
