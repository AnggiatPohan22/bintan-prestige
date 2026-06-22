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
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Block Settings</p>
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
        class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center text-slate-600"
    >
        <i class="fa-solid fa-arrow-pointer text-3xl"></i>
        <p class="text-sm">Select a block in the <strong class="text-slate-500">Block List</strong> to edit its settings.</p>
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

        {{-- No editable fields for this block type --}}
        <p
            x-show="fieldsFor(selectedNode().type).length === 0"
            class="rounded-lg border border-dashed border-slate-700 px-3 py-4 text-center text-xs text-slate-500"
            x-cloak
        >
            This block has no inline settings yet.
        </p>

        {{-- Field schema loop --}}
        <template x-for="field in fieldsFor(selectedNode().type)" :key="field.key">
            <div>
                {{-- Toggle renders its own inline label; everything else uses a top label --}}
                <label
                    x-show="field.type !== 'toggle'"
                    class="mb-1 block text-xs font-medium text-slate-400"
                    x-text="field.label"
                ></label>

                {{-- text / url --}}
                <template x-if="field.type === 'text' || field.type === 'url'">
                    <input
                        :type="field.type === 'url' ? 'url' : 'text'"
                        x-model="selectedNode().data[field.key]"
                        x-on:input="scheduleRefresh()"
                        :maxlength="field.maxlength || null"
                        :placeholder="field.placeholder || ''"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none"
                    >
                </template>

                {{-- number --}}
                <template x-if="field.type === 'number'">
                    <input
                        type="number"
                        x-model.number="selectedNode().data[field.key]"
                        x-on:input="scheduleRefresh()"
                        :min="field.min ?? null"
                        :max="field.max ?? null"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none"
                    >
                </template>

                {{-- textarea --}}
                <template x-if="field.type === 'textarea'">
                    <textarea
                        x-model="selectedNode().data[field.key]"
                        x-on:input="scheduleRefresh()"
                        :rows="field.rows || 3"
                        :placeholder="field.placeholder || ''"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none"
                    ></textarea>
                </template>

                {{-- richtext (HTML; sanitized server-side on save) --}}
                <template x-if="field.type === 'richtext'">
                    <textarea
                        x-model="selectedNode().data[field.key]"
                        x-on:input="scheduleRefresh()"
                        :rows="field.rows || 6"
                        :placeholder="field.placeholder || ''"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-xs text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none"
                    ></textarea>
                </template>

                {{-- select --}}
                <template x-if="field.type === 'select'">
                    <select
                        x-model="selectedNode().data[field.key]"
                        x-on:change="scheduleRefresh()"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 focus:border-amber-500 focus:outline-none"
                    >
                        <template x-for="(label, value) in field.options" :key="value">
                            <option :value="value" x-text="label"></option>
                        </template>
                    </select>
                </template>

                {{-- toggle --}}
                <template x-if="field.type === 'toggle'">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                        <input
                            type="checkbox"
                            x-model="selectedNode().data[field.key]"
                            x-on:change="scheduleRefresh()"
                            class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-amber-500 focus:ring-amber-500"
                        >
                        <span x-text="field.label"></span>
                    </label>
                </template>

                {{-- help text --}}
                <p
                    x-show="field.help"
                    x-text="field.help"
                    class="mt-1 text-[11px] leading-snug text-slate-500"
                ></p>
            </div>
        </template>
    </div>
    </template>

</aside>
