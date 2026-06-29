@php
    $failedBlockId = old('_block_id');
    $hasBlockErrors = old('_editor_context') === 'blocks' && $errors->any();
@endphp

<div class="space-y-4" id="page-block-editor">

    @if($hasBlockErrors)
        <div
            role="alert"
            aria-live="assertive"
            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
            <p class="font-bold">This block could not be saved.</p>
            <p class="mt-1">Review the highlighted submission details and try again.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add Block --}}
    <form
        method="POST"
        action="{{ route('admin.page-blocks.store', $page) }}"
        class="flex flex-col gap-3 sm:flex-row sm:items-end"
        x-data="{ submitting: false }"
        x-on:submit="submitting = true"
    >
        @csrf
        <input type="hidden" name="_editor_context" value="blocks">

        <div class="flex-1">
            <label for="block_type" class="admin-form-label">Add a new block</label>
            <select
                id="block_type"
                name="block_type"
                class="admin-input @error('block_type') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                @error('block_type') aria-invalid="true" aria-describedby="block-type-error" @enderror
            >
                @foreach($blockTypes as $type)
                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            @error('block_type')
                <p id="block-type-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="admin-btn-primary whitespace-nowrap" x-bind:disabled="submitting">
            <span x-show="! submitting">+ Add Block</span>
            <span x-show="submitting" x-cloak>Adding&hellip;</span>
        </button>
    </form>

    {{-- Block List --}}
    @forelse($blocks as $index => $block)
        @php
            $blockData = $block->data ?? [];
            $previewPath = match ($block->block_type) {
                'hero' => $blockData['image'] ?? null,
                'image' => $blockData['src'] ?? null,
                'gallery' => $blockData['images'][0]['src'] ?? null,
                'testimonials' => $blockData['items'][0]['avatar'] ?? null,
                default => $blockData['background']['image'] ?? null,
            };
            $previewUrl = null;

            if (filled($previewPath)) {
                $previewUrl = str_starts_with($previewPath, 'http://') || str_starts_with($previewPath, 'https://')
                    ? $previewPath
                    : (str_starts_with($previewPath, '/storage/')
                        ? $previewPath
                        : (str_starts_with($previewPath, 'storage/')
                            ? '/'.$previewPath
                            : asset('storage/'.ltrim($previewPath, '/'))));
            }

            $failedBlock = $hasBlockErrors && (int) $failedBlockId === $block->id;
            $siblings = $blocks->where('parent_block_id', $block->parent_block_id)->values();
            $siblingIndex = $siblings->search(fn ($sibling) => $sibling->id === $block->id);
        @endphp
        <div
            x-data="{ open: @js($failedBlock), submitting: false }"
            x-init="if (open) $nextTick(() => $refs.editorPanel.querySelector('input:not([type=hidden]), textarea, select')?.focus())"
            class="scroll-mt-24 rounded-xl border {{ $block->is_visible ? 'border-admin' : 'border-admin opacity-60' }} bg-admin-card shadow-sm"
        >
            {{-- Block Header Row --}}
            <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">

                <div class="flex items-center gap-1" aria-label="Reorder {{ $block->label }}">
                    {{-- Reorder: Up --}}
                    @if($siblingIndex !== false && $siblingIndex > 0)
                        <form method="POST" action="{{ route('admin.page-blocks.reorder', $page) }}">
                            @csrf
                            <input type="hidden" name="_editor_context" value="blocks">
                            @php
                                $swapped = $siblings->pluck('id')->toArray();
                                [$swapped[$siblingIndex], $swapped[$siblingIndex - 1]] = [$swapped[$siblingIndex - 1], $swapped[$siblingIndex]];
                            @endphp
                            @foreach($swapped as $bid)
                                <input type="hidden" name="ids[]" value="{{ $bid }}">
                            @endforeach
                            <button type="submit" class="rounded p-2 text-admin-secondary hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Move {{ $block->label }} up" title="Move up">
                                <i class="fa-solid fa-chevron-up text-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="rounded p-2 text-admin-secondary" aria-label="{{ $block->label }} is already first">
                            <i class="fa-solid fa-chevron-up text-xs" aria-hidden="true"></i>
                        </button>
                    @endif

                    {{-- Reorder: Down --}}
                    @if($siblingIndex !== false && $siblingIndex < $siblings->count() - 1)
                        <form method="POST" action="{{ route('admin.page-blocks.reorder', $page) }}">
                            @csrf
                            <input type="hidden" name="_editor_context" value="blocks">
                            @php
                                $swapped = $siblings->pluck('id')->toArray();
                                [$swapped[$siblingIndex], $swapped[$siblingIndex + 1]] = [$swapped[$siblingIndex + 1], $swapped[$siblingIndex]];
                            @endphp
                            @foreach($swapped as $bid)
                                <input type="hidden" name="ids[]" value="{{ $bid }}">
                            @endforeach
                            <button type="submit" class="rounded p-2 text-admin-secondary hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Move {{ $block->label }} down" title="Move down">
                                <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="rounded p-2 text-admin-secondary" aria-label="{{ $block->label }} is already last">
                            <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>

                @if($previewUrl)
                    <img
                        src="{{ $previewUrl }}"
                        alt=""
                        class="h-12 w-16 shrink-0 rounded-lg border border-admin object-cover"
                        loading="lazy"
                    >
                @endif

                <div class="min-w-0 flex-1">
                    <span class="admin-badge-info shrink-0 font-mono text-xs">{{ $block->block_type }}</span>
                    <p class="mt-1 truncate text-sm font-semibold text-admin-secondary">{{ $block->label }}</p>
                    <p class="mt-0.5 text-xs {{ $block->is_visible ? 'text-emerald-600' : 'text-admin-secondary' }}">
                        {{ $block->is_visible ? 'Visible on page' : 'Hidden from page' }}
                    </p>
                </div>

                {{-- Actions --}}
                <div class="grid grid-cols-3 gap-2 sm:flex sm:shrink-0 sm:items-center">
                    <button
                        id="block-toggle-{{ $block->id }}"
                        type="button"
                        x-on:click="open = !open"
                        x-bind:aria-expanded="open.toString()"
                        aria-controls="block-editor-panel-{{ $block->id }}"
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
                            aria-label="{{ $block->is_visible ? 'Hide' : 'Show' }} {{ $block->label }}"
                        >
                            <i class="fa-solid {{ $block->is_visible ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i>
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
                            data-confirm="Permanently delete the &quot;{{ $block->label }}&quot; block? This cannot be undone."
                            class="admin-btn-danger px-3 py-1.5 text-xs"
                            aria-label="Delete {{ $block->label }}"
                        >
                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Block Edit Form (collapsed by default) --}}
            <div
                id="block-editor-panel-{{ $block->id }}"
                x-ref="editorPanel"
                x-show="open"
                x-cloak
                class="border-t border-admin px-4 pb-4 pt-4"
                x-on:keydown.escape="open = false; document.getElementById('block-toggle-{{ $block->id }}')?.focus()"
            >
                <form
                    method="POST"
                    action="{{ route('admin.page-blocks.update', [$page, $block]) }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                    x-on:submit="submitting = true"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_editor_context" value="blocks">
                    <input type="hidden" name="_block_id" value="{{ $block->id }}">

                    @if($failedBlock)
                        <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                            Your changes were not saved. Correct the validation errors above and submit this block again.
                        </p>
                    @endif

                    <div>
                        <label for="block-label-{{ $block->id }}" class="admin-form-label">Block Label</label>
                        <input
                            id="block-label-{{ $block->id }}"
                            type="text"
                            name="label"
                            value="{{ old('label', $block->label) }}"
                            class="admin-input"
                            placeholder="Label shown in this editor"
                        >
                    </div>

                    <div>
                        <label for="block-parent-{{ $block->id }}" class="admin-form-label">Parent Container</label>
                        <select id="block-parent-{{ $block->id }}" name="parent_block_id" class="admin-input">
                            <option value="">Top level</option>
                            @foreach($blocks->filter(fn ($candidate) => $candidate->id !== $block->id && $candidate->isContainer() && ($candidate->block_type !== 'columns' || $block->block_type === 'group')) as $candidate)
                                <option value="{{ $candidate->id }}" @selected((int) $block->parent_block_id === $candidate->id)>
                                    {{ $candidate->label }} ({{ $candidate->block_type }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-admin-secondary">Columns accept Group blocks as column slots. Other blocks can be nested inside Groups.</p>
                    </div>

                    @include(
                        'backend.pages.partials.blocks.' . str_replace('_', '-', $block->block_type),
                        ['block' => $block, 'categories' => $categories, 'destinations' => $destinations, 'formDefinitions' => $formDefinitions ?? collect()]
                    )

                    @include('backend.pages.partials.blocks.partials.background', ['block' => $block])

                    <div class="flex flex-col gap-3 border-t border-admin pt-3 sm:flex-row">
                        <button type="submit" class="admin-btn-primary w-full sm:w-auto" x-bind:disabled="submitting">
                            <span x-show="! submitting">Save Block</span>
                            <span x-show="submitting" x-cloak>Saving&hellip;</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="open = false"
                            class="admin-btn-secondary w-full sm:w-auto"
                            x-bind:disabled="submitting"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-empty-state py-10" role="status">
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-indigo-50 text-indigo-500" aria-hidden="true">
                <i class="fa-solid fa-layer-group"></i>
            </span>
            <p class="mt-3 font-semibold text-admin-secondary">Build this page one block at a time.</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-admin-secondary">Choose a block type above. You can edit, preview media, show or hide, and reorder it later with the keyboard-friendly Up and Down controls.</p>
            <a href="#block_type" class="admin-btn-primary mt-4 inline-flex">Choose the first block</a>
        </div>
    @endforelse

    @include('backend.media.partials.picker-modal')

</div>
