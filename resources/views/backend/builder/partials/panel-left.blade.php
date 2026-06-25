{{-- ── LEFT PANEL: Inserter + Tree ─────────────────────────
     lg+: in-flow grid column at 20% (lg:w-[20%]). Below lg: fixed overlay
     drawer (w-72) so it never squeezes the canvas on small screens.
     Width is FIXED on desktop — switching Add Block <-> Block List never
     changes the panel width.
──────────────────────────────────────────────────────── --}}
<aside
    x-show="!leftCollapsed"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full lg:translate-x-0 lg:opacity-0"
    x-transition:enter-end="translate-x-0 lg:opacity-100"
    class="absolute inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] shrink-0 flex-col border-r border-admin bg-admin-card shadow-2xl lg:static lg:z-auto lg:w-[20%] lg:max-w-none lg:shadow-none"
    x-cloak
>

    {{-- Tab switcher --}}
    <div class="flex shrink-0 border-b border-admin">
        <button
            type="button"
            x-on:click="activeTab = 'insert'"
            :class="activeTab === 'insert' ? 'border-b-2 border-amber-500 text-white' : 'text-admin-secondary hover:text-admin-secondary'"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold transition-colors"
        >
            <i class="fa-solid fa-plus text-xs"></i>
            Add Block
        </button>
        <button
            type="button"
            x-on:click="activeTab = 'patterns'"
            :class="activeTab === 'patterns' ? 'border-b-2 border-amber-500 text-white' : 'text-admin-secondary hover:text-admin-secondary'"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold transition-colors"
        >
            <i class="fa-solid fa-shapes text-xs"></i>
            Patterns
            <span x-show="patterns.length" x-text="patterns.length" class="rounded-full bg-admin-card px-1.5 py-0.5 text-[10px] text-admin-secondary"></span>
        </button>
        <button
            type="button"
            x-on:click="activeTab = 'tree'"
            :class="activeTab === 'tree' ? 'border-b-2 border-amber-500 text-white' : 'text-admin-secondary hover:text-admin-secondary'"
            class="flex flex-1 items-center justify-center gap-1.5 py-3 text-xs font-semibold transition-colors"
        >
            <i class="fa-solid fa-list text-xs"></i>
            Block List
            <span
                x-text="tree.length"
                class="rounded-full bg-admin-card px-1.5 py-0.5 text-xs text-admin-secondary"
            ></span>
        </button>
    </div>

    {{-- Nesting hint (shown briefly when a Group/Columns rule applies) --}}
    <div
        x-show="nestHint"
        x-cloak
        x-transition.opacity
        class="shrink-0 border-b border-amber-900/40 bg-amber-950/40 px-3 py-2 text-[11px] leading-snug text-amber-300"
    >
        <i class="fa-solid fa-circle-info mr-1"></i><span x-text="nestHint"></span>
    </div>

    {{-- INSERT TAB --}}
    <div x-show="activeTab === 'insert'" class="builder-pane-scroll flex-1 overflow-y-auto" x-cloak>

        {{-- Insert position context (container-aware) --}}
        <div class="border-b border-admin px-3 py-2 text-xs text-admin-secondary">
            <span x-show="!selectedNode()">Adding to end of page</span>
            <template x-if="selectedNode()">
                <span>
                    <span x-show="isContainer(selectedNode())">Inserting <span class="font-medium text-amber-400">inside</span> </span>
                    <span x-show="!isContainer(selectedNode())">Inserting after </span>
                    <span class="font-medium text-amber-400" x-text="selectedNode()?.label || selectedNode()?.type"></span>
                    <button
                        type="button"
                        x-on:click="selectedCid = null"
                        class="ml-1 text-admin-secondary hover:text-admin-secondary"
                        title="Reset to add at end"
                    >✕</button>
                </span>
            </template>
        </div>

        <div class="py-2">
        @foreach($catOrder as $cat)
            @if($categorized->has($cat))
                <div class="mb-1">
                    <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-widest text-admin-secondary">
                        <i class="fa-solid {{ match($cat) { 'layout' => 'fa-table-columns', 'content' => 'fa-file-lines', 'media' => 'fa-image', 'conversion' => 'fa-arrow-pointer', 'travel' => 'fa-plane', default => 'fa-cube' } }} mr-1"></i>
                        {{ ucfirst($cat) }}
                    </p>
                    <div class="grid grid-cols-2 gap-1 px-2">
                        @foreach($categorized[$cat] as $type => $block)
                            <button
                                type="button"
                                x-on:click="addBlock('{{ $type }}', '{{ $block['label'] }}')"
                                class="flex flex-col items-center gap-1.5 rounded-lg border border-admin bg-admin-card px-2 py-3 text-center transition-colors hover:border-amber-500/50 hover:opacity-75"
                                title="{{ $block['description'] }}"
                            >
                                <i class="fa-solid {{ match($type) {
                                    'group'          => 'fa-layer-group',
                                    'columns'        => 'fa-table-columns',
                                    'hero'           => 'fa-panorama',
                                    'heading'        => 'fa-heading',
                                    'text'           => 'fa-align-left',
                                    'image'          => 'fa-image',
                                    'gallery'        => 'fa-images',
                                    'video_embed'    => 'fa-video',
                                    'button_group'   => 'fa-hand-pointer',
                                    'stats'          => 'fa-chart-bar',
                                    'tour_itinerary' => 'fa-route',
                                    'pricing_table'  => 'fa-tags',
                                    'cta'            => 'fa-bullhorn',
                                    'products_grid'  => 'fa-th-large',
                                    'faq'            => 'fa-circle-question',
                                    'testimonials'   => 'fa-comments',
                                    'map'            => 'fa-location-dot',
                                    'divider'        => 'fa-minus',
                                    'contact_form'   => 'fa-envelope',
                                    default          => 'fa-cube',
                                } }} text-sm text-admin-secondary"></i>
                                <span class="text-xs leading-tight text-admin-secondary">{{ $block['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
        </div>{{-- /py-2 --}}
    </div>

    {{-- PATTERNS TAB --}}
    <div x-show="activeTab === 'patterns'" class="builder-pane-scroll flex-1 overflow-y-auto" x-cloak>
        <div class="border-b border-admin px-3 py-2 text-xs text-admin-secondary">
            <span x-show="!selectedNode()">Patterns insert at the end of the page.</span>
            <span x-show="selectedNode()">
                Pattern inserts <span x-show="isContainer(selectedNode())">inside</span><span x-show="!isContainer(selectedNode())">after</span>
                <strong class="text-amber-400" x-text="selectedNode()?.label || selectedNode()?.type"></strong>.
            </span>
        </div>

        <div x-show="patternsLoading" class="flex items-center justify-center gap-2 p-6 text-xs text-admin-secondary">
            <i class="fa-solid fa-spinner fa-spin"></i> Loading patternsâ€¦
        </div>

        <div x-show="patternsError" class="m-3 rounded-lg border border-red-900/50 bg-red-950/30 p-3 text-xs text-red-300" x-cloak>
            <span x-text="patternsError"></span>
            <button type="button" class="mt-2 block text-red-400 underline" x-on:click="loadPatterns()">Try again</button>
        </div>

        <div x-show="!patternsLoading && patterns.length === 0" class="p-6 text-center text-xs text-admin-secondary" x-cloak>
            <i class="fa-solid fa-shapes mb-3 block text-3xl text-admin-secondary"></i>
            No saved patterns yet.<br>Select a block and use <strong class="text-admin-secondary">Save as Pattern</strong> in Block Settings.
        </div>

        <div class="space-y-2 p-2">
            <template x-for="pattern in patterns" :key="pattern.id">
                <article class="rounded-lg border border-admin bg-admin-card/60 p-3">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid mt-0.5 text-xs text-amber-500" :class="blockIcon(pattern.block_type)"></i>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-admin-secondary" x-text="pattern.name"></p>
                            <p class="mt-0.5 text-[10px] uppercase tracking-wide text-admin-secondary" x-text="pattern.category || pattern.block_type"></p>
                        </div>
                        <button type="button"
                                class="text-admin-secondary hover:text-red-400"
                                title="Delete pattern"
                                x-on:click="if(confirm('Delete this saved pattern? Existing page blocks will stay unchanged.')) deletePattern(pattern)">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </div>
                    <p x-show="pattern.description" x-text="pattern.description" class="mt-2 line-clamp-2 text-[11px] leading-snug text-admin-secondary"></p>
                    <button type="button"
                            x-on:click="insertPattern(pattern)"
                            class="mt-3 w-full rounded-md bg-amber-500 px-2 py-1.5 text-xs font-semibold text-admin-secondary transition hover:bg-amber-400">
                        <i class="fa-solid fa-plus mr-1"></i>Insert Pattern
                    </button>
                </article>
            </template>
        </div>
    </div>

    {{-- TREE TAB --}}
    <div x-show="activeTab === 'tree'" class="builder-pane-scroll flex-1 overflow-y-auto" x-cloak>

        <div x-show="tree.length === 0" class="p-4 text-center text-xs text-admin-secondary" x-cloak>
            No blocks yet.<br>
            Switch to <strong class="text-admin-secondary">Add Block</strong> to start.
        </div>

        {{-- How-to hint for nesting --}}
        <div x-show="tree.length > 0" class="border-b border-admin px-3 py-2 text-[11px] leading-snug text-admin-secondary" x-cloak>
            <i class="fa-solid fa-arrows-up-down-left-right mr-1"></i>
            Drag a block to reorder. Drop it <strong class="text-amber-400/90">onto</strong> a
            <strong class="text-admin-secondary">Group</strong> or <strong class="text-admin-secondary">Columns</strong>
            to place it inside.
        </div>

        {{-- Hierarchical, drag-and-drop block list.
             Children render indented under their container. Dragging shows a
             blue/amber line for reorder (above/below) or highlights a container
             with an "inside" badge when the drop will nest the block. --}}
        <ul class="py-1">
            <template x-for="row in flatList()" :key="row.node._cid">
                <li
                    draggable="true"
                    x-on:dragstart="onDragStart(row.node._cid, $event)"
                    x-on:dragover.prevent="onDragOver(row.node._cid, $event)"
                    x-on:drop.prevent="onDrop(row.node._cid)"
                    x-on:dragend="clearDrag()"
                    x-on:click="selectBlock(row.node._cid)"
                    :style="'padding-left:' + (8 + row.depth * 16) + 'px'"
                    class="group relative flex cursor-grab items-center gap-1.5 border-l-2 py-2 pr-2 transition-colors active:cursor-grabbing hover:opacity-75/60"
                    :class="{
                        'bg-amber-900/30 border-amber-500': selectedCid === row.node._cid && !(dragOverCid === row.node._cid),
                        'border-transparent': selectedCid !== row.node._cid && !(dragOverCid === row.node._cid),
                        'bg-amber-500/15 ring-2 ring-inset ring-amber-400 border-amber-400': dragOverCid === row.node._cid && dropMode === 'inside',
                        'ring-2 ring-inset ring-red-500/70 border-red-500/70': dragOverCid === row.node._cid && dropMode === 'invalid',
                        'opacity-40': dragCid === row.node._cid,
                        'opacity-50': isHiddenOnDevice(row.node) && dragCid !== row.node._cid,
                    }"
                >
                    {{-- Reorder indicator lines (above / below) --}}
                    <div x-show="dragOverCid === row.node._cid && dropMode === 'before'"
                         class="pointer-events-none absolute inset-x-0 top-0 z-10 h-0.5 bg-amber-400"></div>
                    <div x-show="dragOverCid === row.node._cid && dropMode === 'after'"
                         class="pointer-events-none absolute inset-x-0 bottom-0 z-10 h-0.5 bg-amber-400"></div>

                    {{-- Grip affordance --}}
                    <i class="fa-solid fa-grip-vertical w-2.5 shrink-0 text-center text-[10px] text-admin-secondary group-hover:text-admin-secondary"></i>

                    {{-- Icon — folder for containers, cube for leaves --}}
                    <i
                        class="w-3.5 shrink-0 text-center text-xs"
                        :class="[
                            isContainer(row.node) ? 'fa-solid fa-folder' : 'fa-solid fa-cube',
                            !row.node.is_visible ? 'text-admin-secondary' : (isContainer(row.node) ? 'text-amber-500/70' : 'text-admin-secondary'),
                        ]"
                    ></i>

                    {{-- Label --}}
                    <span
                        class="min-w-0 flex-1 truncate text-xs"
                        :class="!row.node.is_visible
                            ? 'text-admin-secondary line-through'
                            : selectedCid === row.node._cid ? 'text-amber-300' : 'text-admin-secondary'"
                        x-text="row.node.label || row.node.type"
                    ></span>

                    {{-- B7: badge shown when block is hidden on the active preview device --}}
                    <span
                        x-show="isHiddenOnDevice(row.node) && dragCid !== row.node._cid"
                        x-cloak
                        class="pointer-events-none shrink-0 rounded border border-admin px-1 py-0.5 text-[9px] font-medium text-admin-secondary"
                        :title="'Hidden on ' + previewMode"
                    ><i class="fa-solid fa-eye-slash mr-0.5"></i>hidden</span>

                    {{-- 'Drop inside' badge while hovering a container as nest target --}}
                    <span
                        x-show="dragOverCid === row.node._cid && dropMode === 'inside'"
                        class="pointer-events-none shrink-0 rounded bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-admin-secondary"
                    ><i class="fa-solid fa-arrow-turn-down mr-0.5"></i>inside</span>

                    {{-- Child count for containers (hidden while showing the drop badge) --}}
                    <span
                        x-show="isContainer(row.node) && !(dragOverCid === row.node._cid && dropMode === 'inside')"
                        class="shrink-0 rounded bg-admin-card px-1 text-[10px] text-admin-secondary"
                        x-text="childCount(row.node)"
                        title="Blocks inside"
                    ></span>

                    {{-- Insert target indicator (selection) --}}
                    <span
                        x-show="selectedCid === row.node._cid && dragCid === null"
                        class="shrink-0 rounded bg-amber-800/60 px-1 py-0.5 text-[10px] text-amber-400"
                        x-text="isContainer(row.node) ? 'inside' : '↓'"
                        title="Where the next added block goes"
                    ></span>

                    {{-- Action buttons (hover) — hidden while dragging --}}
                    <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100" x-show="dragCid === null">
                        <button type="button" x-on:click.stop="toggleVisible(row.node)"
                                class="flex h-5 w-5 items-center justify-center rounded text-admin-secondary transition-colors hover:text-white"
                                :title="row.node.is_visible ? 'Hide block' : 'Show block'">
                            <i class="fa-solid text-xs" :class="row.node.is_visible ? 'fa-eye' : 'fa-eye-slash'"></i>
                        </button>
                        <button type="button" x-on:click.stop="if(confirm('Remove this block?')) removeBlock(row.node._cid)"
                                class="flex h-5 w-5 items-center justify-center rounded text-admin-secondary transition-colors hover:text-red-400" title="Delete block">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </div>
                </li>
            </template>
        </ul>

    </div>

</aside>
