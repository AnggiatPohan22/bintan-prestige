{{-- B6: scalable template library. Saved template bodies are fetched lazily. --}}
<div
    x-show="templateModalOpen"
    x-cloak
    x-on:keydown.escape.window="templateModalOpen = false"
    class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4"
>
    <section
        x-on:click.outside="templateModalOpen = false"
        class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-900 shadow-2xl"
        role="dialog"
        aria-modal="true"
        aria-label="Template Library"
    >
        <header class="flex shrink-0 items-center justify-between border-b border-slate-800 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-white">Template Library</h2>
                <p class="mt-0.5 text-xs text-slate-400">Layout shells and reusable full-page designs</p>
            </div>
            <button type="button" x-on:click="templateModalOpen = false" class="text-slate-400 hover:text-white" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <div class="builder-pane-scroll flex-1 overflow-y-auto p-5">
            <div x-show="templateError" class="mb-4 rounded-lg border border-red-900/50 bg-red-950/30 p-3 text-xs text-red-300" x-text="templateError"></div>

            <section>
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400">Layout Shells</h3>
                        <p class="mt-1 text-[11px] text-slate-400">Changes the frontend layout without replacing blocks.</p>
                    </div>
                    <span class="rounded bg-slate-800 px-2 py-1 text-[10px] text-slate-400" x-text="currentLayoutName()"></span>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <template x-for="layout in layoutTemplates" :key="layout.id">
                        <article class="rounded-lg border p-3" :class="Number(currentTemplateId) === Number(layout.id) ? 'border-amber-500 bg-amber-950/20' : 'border-slate-700 bg-slate-800/50'">
                            <p class="text-sm font-semibold text-slate-200" x-text="layout.name"></p>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-snug text-slate-400" x-text="layout.description || layout.blade_file"></p>
                            <button type="button" x-on:click="applyLayoutTemplate(layout)"
                                    class="mt-3 w-full rounded-md border border-slate-600 py-1.5 text-xs text-slate-300 hover:border-amber-500 hover:text-amber-300">
                                Use Layout
                            </button>
                        </article>
                    </template>
                </div>
            </section>

            <section class="mt-7 border-t border-slate-800 pt-5">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400">Saved Page Templates</h3>
                        <p class="mt-1 text-[11px] text-slate-400">Versioned full-page block trees; applying replaces the current canvas after confirmation.</p>
                    </div>
                    <button type="button" x-on:click="openTemplateSaveForm()"
                            class="rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-slate-100 hover:bg-amber-400">
                        <i class="fa-solid fa-plus mr-1"></i>Save Current Page
                    </button>
                </div>

                <div class="mb-4 flex gap-2">
                    <input type="search" x-model="templateSearch" x-on:keydown.enter.prevent="loadBuilderTemplates(1)"
                           class="builder-input" placeholder="Search templates">
                    <button type="button" x-on:click="loadBuilderTemplates(1)" class="rounded-md border border-slate-600 px-3 text-xs text-slate-300 hover:text-white">Search</button>
                </div>

                <div x-show="templatesLoading" class="p-6 text-center text-xs text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>Loading templatesâ€¦
                </div>
                <div x-show="!templatesLoading && builderTemplates.length === 0" class="rounded-lg border border-dashed border-slate-700 p-6 text-center text-xs text-slate-400">
                    No saved page templates yet.
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="template in builderTemplates" :key="template.id">
                        <article class="rounded-lg border border-slate-700 bg-slate-800/50 p-3">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-table-cells-large mt-0.5 text-amber-500"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-200" x-text="template.name"></p>
                                    <p class="mt-0.5 text-[10px] uppercase tracking-wide text-slate-400" x-text="(template.category || 'Page') + ' Â· v' + template.schema_version"></p>
                                </div>
                                <button type="button" x-on:click="if(confirm('Delete this saved template? Existing pages remain unchanged.')) deleteBuilderTemplate(template)"
                                        class="text-slate-400 hover:text-red-400" title="Delete template">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                            <p x-show="template.description" x-text="template.description" class="mt-2 line-clamp-2 text-[11px] text-slate-400"></p>
                            <p class="mt-2 text-[10px] text-slate-400" x-text="'Layout: ' + template.base_template_name"></p>
                            <button type="button" x-on:click="applyBuilderTemplate(template)"
                                    class="mt-3 w-full rounded-md border border-amber-600/60 py-1.5 text-xs font-semibold text-amber-300 hover:bg-amber-950/30">
                                Apply Template
                            </button>
                        </article>
                    </template>
                </div>

                <div x-show="templateMeta.last_page > 1" class="mt-4 flex items-center justify-center gap-3 text-xs text-slate-400">
                    <button type="button" :disabled="templateMeta.current_page <= 1" x-on:click="loadBuilderTemplates(templateMeta.current_page - 1)" class="disabled:opacity-30">Previous</button>
                    <span x-text="templateMeta.current_page + ' / ' + templateMeta.last_page"></span>
                    <button type="button" :disabled="templateMeta.current_page >= templateMeta.last_page" x-on:click="loadBuilderTemplates(templateMeta.current_page + 1)" class="disabled:opacity-30">Next</button>
                </div>
            </section>
        </div>

        <div x-show="templateFormOpen" class="absolute inset-0 z-10 flex items-center justify-center bg-black/70 p-4" x-cloak>
            <form x-on:submit.prevent="saveBuilderTemplate()" class="w-full max-w-md space-y-3 rounded-xl border border-slate-700 bg-slate-900 p-5 shadow-2xl">
                <h3 class="text-sm font-semibold text-white">Save Current Page as Template</h3>
                <div>
                    <label class="builder-label">Template Name</label>
                    <input type="text" x-model="templateDraft.name" maxlength="150" class="builder-input" required>
                </div>
                <div>
                    <label class="builder-label">Category (optional)</label>
                    <input type="text" x-model="templateDraft.category" maxlength="100" class="builder-input" placeholder="Landing Page">
                </div>
                <div>
                    <label class="builder-label">Description (optional)</label>
                    <textarea x-model="templateDraft.description" maxlength="2000" rows="3" class="builder-input"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="isSavingTemplate || !templateDraft.name.trim()" class="flex-1 rounded-md bg-amber-500 py-2 text-xs font-semibold text-slate-100 disabled:opacity-40">
                        <span x-text="isSavingTemplate ? 'Savingâ€¦' : 'Save Template'"></span>
                    </button>
                    <button type="button" x-on:click="templateFormOpen = false" class="rounded-md border border-slate-600 px-3 text-xs text-slate-400">Cancel</button>
                </div>
            </form>
        </div>
    </section>
</div>
