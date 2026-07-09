{{--
    Reusable media grid. Expects $media (paginator).
    Each item calls the Alpine `select(payload)` method defined by the host
    component (index → opens detail panel; picker → emits selection).
    The host also exposes `viewMode` (small|large|xlarge|list|detail) which
    drives the layout below. Default is 'small' (densest).
--}}
@if($media->isEmpty())
    <div class="admin-empty-state py-12">
        <i class="fa-solid fa-photo-film mb-2 text-2xl text-admin-secondary"></i>
        <p class="font-medium text-admin-secondary">No media found.</p>
        <p class="mt-1 text-sm text-admin-secondary">Upload images to start building your library.</p>
    </div>
@else
    <div
        x-cloak
        :class="{
            'grid gap-3': ['small','large','xlarge'].includes(viewMode),
            'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8': viewMode === 'small',
            'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4': viewMode === 'large',
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3': viewMode === 'xlarge',
            'flex flex-col gap-2': ['list','detail'].includes(viewMode),
        }"
    >
        @foreach($media as $item)
            <button type="button"
                    x-on:click="select({
                        id: {{ $item->id }},
                        path: @js($item->path),
                        url: @js($item->url),
                        name: @js($item->original_name),
                        alt: @js($item->alt),
                        caption: @js($item->caption),
                        size: @js($item->size_for_humans),
                        dimensions: @js($item->width && $item->height ? $item->width . ' × ' . $item->height : '—'),
                        ext: @js(strtoupper($item->extension)),
                        date: @js($item->created_at?->format('d M Y, H:i')),
                        uploader: @js($item->uploader?->name ?? 'Unknown'),
                        collection: @js($item->collectionLabel()),
                        usageCount: {{ (int) ($item->usage_count ?? 0) }},
                        usageReferences: @js($item->usage_references ?? []),
                        missingFile: @js((bool) ($item->missing_file ?? false)),
                    })"
                    class="group flex overflow-hidden rounded-xl border border-admin bg-admin-card text-left transition hover:border-violet-500/50"
                    :class="{
                        'flex-col hover:-translate-y-0.5': ['small','large','xlarge'].includes(viewMode),
                        'w-full items-center gap-4 p-2': ['list','detail'].includes(viewMode),
                    }">
                <div class="shrink-0 overflow-hidden bg-admin-card"
                     :class="{
                        'aspect-square w-full': ['small','large','xlarge'].includes(viewMode),
                        'h-12 w-12 rounded-lg': viewMode === 'list',
                        'h-20 w-28 rounded-lg': viewMode === 'detail',
                     }">
                    <img src="{{ $item->url }}" alt="{{ $item->alt ?: $item->original_name }}"
                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                         loading="lazy">
                </div>

                {{-- Text/meta block: hidden entirely in 'small' for max density --}}
                <div x-show="viewMode !== 'small'"
                     :class="{
                        'px-3 py-2': ['large','xlarge'].includes(viewMode),
                        'min-w-0 flex-1 py-1 pr-2': ['list','detail'].includes(viewMode),
                     }">
                    <p class="truncate text-xs font-semibold text-admin-secondary" title="{{ $item->original_name }}">{{ $item->original_name }}</p>

                    <p class="mt-0.5 flex items-center gap-2 text-[11px] text-admin-secondary">
                        <span>{{ $item->size_for_humans }}</span>
                        <span class="rounded bg-admin-card px-1.5 font-mono uppercase">{{ $item->extension }}</span>
                    </p>
                    <p class="mt-0.5 truncate text-[10px] font-medium uppercase tracking-wide text-admin-secondary opacity-70">{{ $item->collectionLabel() }}</p>

                    {{-- Extra metadata only in 'detail' --}}
                    <div x-show="viewMode === 'detail'" class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-admin-secondary">
                        <span>{{ $item->width && $item->height ? $item->width . ' × ' . $item->height : '—' }}</span>
                        <span>{{ $item->uploader?->name ?? 'Unknown' }}</span>
                        <span>{{ $item->created_at?->format('d M Y, H:i') }}</span>
                    </div>

                    @if($item->missing_file ?? false)
                        <span class="mt-1 inline-flex rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-600">Missing file</span>
                    @elseif(($item->usage_count ?? 0) > 0)
                        <span class="mt-1 inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Used {{ $item->usage_count }}×</span>
                    @endif
                </div>
            </button>
        @endforeach
    </div>

    <div class="mt-5">
        {{ $media->links() }}
    </div>
@endif
