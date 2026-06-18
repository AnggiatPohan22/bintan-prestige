{{-- Shared block loop for all page templates. Expects $page (with loaded blocks). --}}
@forelse($page->blocks as $block)
    {{-- Each block type renders its own partial (underscore→hyphen view name). --}}
    @includeIf('frontend.blocks.' . str_replace('_', '-', $block->block_type), [
        'block' => $block,
        'data'  => $block->data ?? [],
    ])
@empty
    <div class="flex min-h-[40vh] items-center justify-center px-6 py-24 text-center">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">{{ $page->title }}</h1>
            <p class="mt-4 text-slate-500">This page has no content yet.</p>
        </div>
    </div>
@endforelse
