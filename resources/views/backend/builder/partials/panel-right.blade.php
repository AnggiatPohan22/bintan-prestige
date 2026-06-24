{{-- ── RIGHT PANEL: Block Settings (B3) ────────────────────
     lg+: in-flow grid column at 20% (lg:w-[20%]). Below lg: overlay drawer.
     Schema-driven: renders the selected block's `fields` (config/blocks.php)
     and binds each control to the live tree node via x-model. Editing a field
     schedules a debounced preview refresh and is saved by save-tree.
──────────────────────────────────────────────────────── --}}
<aside
    x-show="!rightCollapsed"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-x-full lg:translate-x-0 lg:opacity-0"
    x-transition:enter-end="translate-x-0 lg:opacity-100"
    class="absolute inset-y-0 right-0 z-40 flex w-80 max-w-[85vw] shrink-0 flex-col border-l border-slate-800 bg-slate-900 shadow-2xl lg:static lg:z-auto lg:w-[20%] lg:max-w-none lg:shadow-none"
    x-cloak
>

    <div class="flex shrink-0 items-center justify-between border-b border-slate-800 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Block Settings</p>
        <span
            x-show="selectedNode()"
            x-text="selectedNode()?.type"
            class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-300"
            x-cloak
        ></span>
    </div>

    {{-- EMPTY STATE — no block selected --}}
    <div
        x-show="!selectedNode()"
        class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center text-slate-400"
    >
        <i class="fa-solid fa-arrow-pointer text-3xl"></i>
        <p class="text-sm">Select a block in the <strong class="text-slate-400">Block List</strong> to edit its settings.</p>
    </div>

    {{-- SETTINGS FORM — schema-driven from the selected block's fields.
         x-if (not x-show) so the inner x-model never evaluates against a null node. --}}
    <template x-if="selectedNode()">
    <div class="builder-pane-scroll flex-1 space-y-4 overflow-y-auto p-4">
        {{-- Block label (admin-facing name) --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-400">Block Label</label>
            <input
                type="text"
                x-model="selectedNode().label"
                x-on:input="scheduleRefresh()"
                placeholder="Admin label (optional)"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none"
            >
        </div>

        {{-- Container guidance --}}
        <p
            x-show="isContainer(selectedNode())"
            class="rounded-lg border border-amber-900/40 bg-amber-950/30 px-3 py-2 text-[11px] leading-snug text-amber-300/90"
            x-cloak
        >
            <i class="fa-solid fa-layer-group mr-1"></i>
            This is a container (<span x-text="childCount(selectedNode())"></span> inside). Keep it selected and add blocks to place them inside it. Empty containers are hidden on the page.
        </p>

        {{-- No editable fields for this block type --}}
        <p
            x-show="fieldsFor(selectedNode().type).length === 0"
            class="rounded-lg border border-dashed border-slate-700 px-3 py-4 text-center text-xs text-slate-400"
            x-cloak
        >
            This block has no inline settings yet.
        </p>

        {{-- Layout / Style / Advanced tab bar (only when the block uses >1 tab) --}}
        <div x-show="fieldTabs().length > 1" class="flex gap-1 rounded-lg bg-slate-800 p-0.5" x-cloak>
            <template x-for="t in fieldTabs()" :key="t">
                <button
                    type="button"
                    x-on:click="activeFieldTab = t"
                    :class="activeFieldTab === t ? 'bg-slate-700 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                    class="flex-1 rounded-md py-1.5 text-[11px] font-semibold capitalize transition-colors"
                    x-text="t"
                ></button>
            </template>
        </div>

        {{-- Field schema loop (filtered by active tab) --}}
        <template x-for="field in fieldsFor(selectedNode().type)" :key="field.key">
            <div x-show="showField(field) && fieldTab(field) === activeFieldTab">

                {{-- Top label (toggle carries its own inline label) --}}
                <label
                    x-show="field.type !== 'toggle'"
                    class="builder-label"
                    x-text="field.label"
                ></label>

                {{-- ── REPEATER (array of objects) ─────────────── --}}
                <template x-if="field.type === 'repeater'">
                    <div class="space-y-2">
                        <template x-for="(item, idx) in repeaterArr(field)" :key="idx">
                            <div class="space-y-2 rounded-lg border border-slate-700 bg-slate-800/40 p-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-slate-400"
                                          x-text="(field.itemLabel || 'Item') + ' ' + (idx + 1)"></span>
                                    <button type="button" title="Remove"
                                            class="text-slate-400 transition-colors hover:text-red-400"
                                            x-on:click="repeaterRemove(field, idx)">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                                <template x-for="sub in field.fields" :key="sub.key">
                                    <div>
                                        <label x-show="sub.type !== 'toggle'" class="builder-label" x-text="sub.label"></label>
                                        @include('backend.builder.partials.builder-field', ['f' => 'sub', 'model' => 'item[sub.key]'])
                                    </div>
                                </template>
                            </div>
                        </template>

                        <button type="button"
                                class="w-full rounded-lg border border-dashed border-slate-600 py-2 text-xs font-medium text-slate-400 transition-colors hover:border-amber-500/50 hover:text-amber-300 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="field.max && repeaterArr(field).length >= field.max"
                                x-on:click="repeaterAdd(field)">
                            <i class="fa-solid fa-plus mr-1"></i><span x-text="'Add ' + (field.itemLabel || 'item')"></span>
                        </button>
                    </div>
                </template>

                {{-- ── LIST (array of plain strings) ───────────── --}}
                <template x-if="field.type === 'list'">
                    <div class="space-y-1.5">
                        <template x-for="(val, idx) in repeaterArr(field)" :key="idx">
                            <div class="flex gap-1.5">
                                <input type="text" class="builder-input"
                                       x-model="repeaterArr(field)[idx]" x-on:input="scheduleRefresh()"
                                       :placeholder="field.placeholder || ''">
                                <button type="button" class="px-1 text-slate-400 hover:text-red-400"
                                        x-on:click="listRemove(field, idx)" title="Remove">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </template>
                        <button type="button"
                                class="w-full rounded-lg border border-dashed border-slate-600 py-1.5 text-xs font-medium text-slate-400 transition-colors hover:border-amber-500/50 hover:text-amber-300 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="field.max && repeaterArr(field).length >= field.max"
                                x-on:click="listAdd(field)">
                            <i class="fa-solid fa-plus mr-1"></i><span x-text="'Add ' + (field.itemLabel || 'item')"></span>
                        </button>
                    </div>
                </template>

                {{-- ── SIMPLE CONTROLS (delegated to builder-field) ── --}}
                <template x-if="field.type !== 'repeater' && field.type !== 'list'">
                    <div>
                        @include('backend.builder.partials.builder-field', ['f' => 'field', 'model' => 'selectedNode().data[field.key]'])
                    </div>
                </template>

                {{-- help text --}}
                <p
                    x-show="field.help"
                    x-text="field.help"
                    class="mt-1 text-[11px] leading-snug text-slate-400"
                ></p>
            </div>
        </template>

        {{-- B5: persist the selected block or its complete subtree as a pattern. --}}
        <div class="border-t border-slate-800 pt-4">
            <button
                type="button"
                x-show="!patternFormOpen"
                x-on:click="openPatternForm()"
                class="w-full rounded-lg border border-amber-600/50 bg-amber-950/30 px-3 py-2 text-xs font-semibold text-amber-300 transition hover:border-amber-500 hover:bg-amber-950/50"
            >
                <i class="fa-solid fa-shapes mr-1"></i>Save as Pattern
            </button>

            <div x-show="patternFormOpen" class="space-y-3 rounded-lg border border-slate-700 bg-slate-800/40 p-3" x-cloak>
                <p class="text-xs font-semibold text-slate-300">Save selected subtree</p>
                <div>
                    <label class="builder-label">Pattern Name</label>
                    <input type="text" x-model="patternDraft.name" maxlength="150" class="builder-input" placeholder="e.g. Island Hero">
                </div>
                <div>
                    <label class="builder-label">Category (optional)</label>
                    <input type="text" x-model="patternDraft.category" maxlength="100" class="builder-input" placeholder="e.g. Landing Page">
                </div>
                <div>
                    <label class="builder-label">Description (optional)</label>
                    <textarea x-model="patternDraft.description" maxlength="2000" rows="2" class="builder-input" placeholder="When to use this pattern"></textarea>
                </div>
                <p x-show="patternsError" x-text="patternsError" class="text-[11px] text-red-400"></p>
                <div class="flex gap-2">
                    <button type="button"
                            x-on:click="saveSelectedPattern()"
                            :disabled="isSavingPattern || !patternDraft.name.trim()"
                            class="flex-1 rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="fa-solid fa-spinner fa-spin mr-1" x-show="isSavingPattern"></i>
                        <span x-text="isSavingPattern ? 'Savingâ€¦' : 'Save Pattern'"></span>
                    </button>
                    <button type="button" x-on:click="patternFormOpen = false" class="rounded-md border border-slate-600 px-3 py-2 text-xs text-slate-400 hover:text-white">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    </template>

</aside>
