@extends('layouts.frontend')

@section('content')
<div class="cms-page">
    <section class="mx-auto max-w-5xl px-6 py-16 sm:py-20">

        <header class="mb-10 text-center">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ $type->label_plural }}</h1>
            @if($type->description)
                <p class="mx-auto mt-4 max-w-2xl text-slate-500">{{ $type->description }}</p>
            @endif
        </header>

        @if($entries->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-20 text-center">
                <p class="text-slate-500">No {{ strtolower($type->label_plural) }} published yet. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($entries as $entry)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition-shadow hover:shadow-lg">
                        <div class="flex flex-1 flex-col p-6">
                            <h2 class="text-lg font-semibold text-slate-900">
                                <a href="{{ $entry->publicUrl() }}" class="hover:text-indigo-600">
                                    {{ $entry->title }}
                                </a>
                            </h2>
                            @if($entry->excerpt)
                                <p class="mt-2 flex-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($entry->excerpt, 140) }}</p>
                            @endif
                            <div class="mt-4">
                                <a href="{{ $entry->publicUrl() }}"
                                   class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                                    Read more
                                    <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $entries->links() }}
            </div>
        @endif

    </section>
</div>
@endsection
