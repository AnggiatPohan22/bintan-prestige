{{--
    Reusable media card grid. Expects $media (paginator).
    Each card calls the Alpine `select(payload)` method defined by the host component
    (index → opens detail panel; picker → emits selection).
--}}
@if($media->isEmpty())
    <div class="admin-empty-state py-12">
        <i class="fa-solid fa-photo-film mb-2 text-2xl text-slate-300"></i>
        <p class="font-medium text-slate-600">No media found.</p>
        <p class="mt-1 text-sm text-slate-400">Upload images to start building your library.</p>
    </div>
@else
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($media as $item)
            <button type="button"
                    x-on:click="select({
                        id: {{ $item->id }},
                        url: @js($item->url),
                        name: @js($item->original_name),
                        alt: @js($item->alt),
                        caption: @js($item->caption),
                        size: @js($item->size_for_humans),
                        dimensions: @js($item->width && $item->height ? $item->width . ' × ' . $item->height : '—'),
                        ext: @js(strtoupper($item->extension)),
                        date: @js($item->created_at?->format('d M Y, H:i')),
                        uploader: @js($item->uploader?->name ?? 'Unknown'),
                    })"
                    class="group overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                <div class="aspect-square overflow-hidden bg-slate-100">
                    <img src="{{ $item->url }}" alt="{{ $item->alt ?: $item->original_name }}"
                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                         loading="lazy">
                </div>
                <div class="px-3 py-2">
                    <p class="truncate text-xs font-semibold text-slate-700" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                    <p class="mt-0.5 flex items-center justify-between text-[11px] text-slate-400">
                        <span>{{ $item->size_for_humans }}</span>
                        <span class="rounded bg-slate-100 px-1.5 font-mono uppercase">{{ $item->extension }}</span>
                    </p>
                </div>
            </button>
        @endforeach
    </div>

    <div class="mt-5">
        {{ $media->links() }}
    </div>
@endif
