<div class="space-y-4">

    {{-- Add Block --}}
    <form
        method="POST"
        action="{{ route('admin.page-blocks.store', $page) }}"
        class="flex flex-col gap-3 sm:flex-row sm:items-end"
    >
        @csrf

        <div class="flex-1">
            <label for="block_type" class="admin-form-label">Add a new block</label>
            <select id="block_type" name="block_type" class="admin-input">
                @foreach($blockTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="admin-btn-primary whitespace-nowrap">
            + Add Block
        </button>
    </form>

    {{-- Block List --}}
    @forelse($blocks as $index => $block)
        <div
            x-data="{ open: false }"
            class="rounded-xl border {{ $block->is_visible ? 'border-slate-200' : 'border-slate-100 opacity-60' }} bg-white shadow-sm"
        >
            {{-- Block Header Row --}}
            <div class="flex items-center gap-2 px-4 py-3">

                {{-- Reorder: Up --}}
                @if(! $loop->first)
                    <form method="POST" action="{{ route('admin.page-blocks.reorder', $page) }}">
                        @csrf
                        @php
                            $swapped = $blocks->pluck('id')->toArray();
                            [$swapped[$index], $swapped[$index - 1]] = [$swapped[$index - 1], $swapped[$index]];
                        @endphp
                        @foreach($swapped as $bid)
                            <input type="hidden" name="ids[]" value="{{ $bid }}">
                        @endforeach
                        <button type="submit" class="rounded p-1 text-slate-400 hover:text-slate-700" title="Move up">
                            <i class="fa-solid fa-chevron-up text-xs"></i>
                        </button>
                    </form>
                @else
                    <span class="w-6"></span>
                @endif

                {{-- Reorder: Down --}}
                @if(! $loop->last)
                    <form method="POST" action="{{ route('admin.page-blocks.reorder', $page) }}">
                        @csrf
                        @php
                            $swapped = $blocks->pluck('id')->toArray();
                            [$swapped[$index], $swapped[$index + 1]] = [$swapped[$index + 1], $swapped[$index]];
                        @endphp
                        @foreach($swapped as $bid)
                            <input type="hidden" name="ids[]" value="{{ $bid }}">
                        @endforeach
                        <button type="submit" class="rounded p-1 text-slate-400 hover:text-slate-700" title="Move down">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                    </form>
                @else
                    <span class="w-6"></span>
                @endif

                {{-- Block Type Badge --}}
                <span class="admin-badge-info shrink-0 font-mono text-xs">
                    {{ $block->block_type }}
                </span>

                {{-- Label --}}
                <span class="flex-1 truncate text-sm font-semibold text-slate-700">
                    {{ $block->label }}
                </span>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="admin-btn-soft px-3 py-1.5 text-xs"
                    >
                        <span x-text="open ? 'Close' : 'Edit'"></span>
                    </button>

                    <form method="POST" action="{{ route('admin.page-blocks.toggle-visible', [$page, $block]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="admin-btn-soft px-3 py-1.5 text-xs"
                            title="{{ $block->is_visible ? 'Hide block' : 'Show block' }}"
                        >
                            <i class="fa-solid {{ $block->is_visible ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('admin.page-blocks.destroy', [$page, $block]) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Delete this block? This cannot be undone.')"
                            class="admin-btn-danger px-3 py-1.5 text-xs"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Block Edit Form (collapsed by default) --}}
            <div x-show="open" x-cloak class="border-t border-slate-100 px-4 pb-4 pt-4">
                <form
                    method="POST"
                    action="{{ route('admin.page-blocks.update', [$page, $block]) }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="admin-form-label">Block Label</label>
                        <input
                            type="text"
                            name="label"
                            value="{{ old('label', $block->label) }}"
                            class="admin-input"
                            placeholder="Label shown in this editor"
                        >
                    </div>

                    @include(
                        'backend.pages.partials.blocks.' . str_replace('_', '-', $block->block_type),
                        ['block' => $block, 'categories' => $categories, 'destinations' => $destinations]
                    )

                    @include('backend.pages.partials.blocks.partials.background', ['block' => $block])

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row">
                        <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                            Save Block
                        </button>
                        <button
                            type="button"
                            x-on:click="open = false"
                            class="admin-btn-secondary w-full sm:w-auto"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-empty-state py-8">
            <p class="font-medium text-slate-600">No blocks yet.</p>
            <p class="mt-1 text-sm text-slate-400">Use the "Add Block" form above to add your first content block.</p>
        </div>
    @endforelse

</div>
