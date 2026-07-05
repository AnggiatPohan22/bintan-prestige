@php
    $data    = $block->data ?? [];
    $entries = $block->resolvedEntries ?? collect();
    $heading = trim((string)($data['heading'] ?? ''));
    $showExcerpt = (bool)($data['show_excerpt'] ?? true);
    $columns = (int)($data['columns'] ?? 3);
    $gridCols = match ($columns) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    };

    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($entries->isNotEmpty())
    <section class="py-16" style="{{ $bgStyle }}">
        <div class="mx-auto max-w-7xl px-6">
            @if($heading !== '')
                <h2 class="mb-8 text-center text-2xl font-bold text-slate-900 sm:text-3xl">{{ $heading }}</h2>
            @endif

            <div class="grid {{ $gridCols }} gap-6">
                @foreach($entries as $entry)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition-shadow hover:shadow-lg">
                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="text-lg font-semibold text-slate-900">
                                @if($entry->publicUrl())
                                    <a href="{{ $entry->publicUrl() }}" class="hover:text-indigo-600">{{ $entry->title }}</a>
                                @else
                                    {{ $entry->title }}
                                @endif
                            </h3>
                            @if($showExcerpt && $entry->excerpt)
                                <p class="mt-2 flex-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($entry->excerpt, 140) }}</p>
                            @endif
                            @if($entry->publicUrl())
                                <div class="mt-4">
                                    <a href="{{ $entry->publicUrl() }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                                        Read more
                                        <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
