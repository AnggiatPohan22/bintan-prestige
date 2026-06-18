<div class="space-y-4" x-data="faqBlock('{{ $block->data['source'] ?? 'inline' }}', {{ json_encode($block->data['items'] ?? []) }})">

    <div>
        <label class="admin-form-label">FAQ Source</label>
        <select name="data[source]" class="admin-input" x-model="source">
            <option value="inline">Inline (write Q&amp;A here)</option>
            <option value="ids">From FAQ library (select by ID)</option>
        </select>
    </div>

    {{-- Inline mode --}}
    <div x-show="source === 'inline'" class="space-y-3">
        <div class="flex items-center justify-between">
            <span class="admin-form-label mb-0">Questions &amp; Answers</span>
            <button type="button" x-on:click="addItem()" class="admin-btn-soft px-3 py-1.5 text-xs">+ Add Q&amp;A</button>
        </div>

        <template x-for="(item, i) in items" :key="i">
            <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                <input
                    type="text"
                    :name="`data[items][${i}][question]`"
                    x-model="item.question"
                    class="admin-input"
                    placeholder="Question"
                >
                <textarea
                    :name="`data[items][${i}][answer]`"
                    x-model="item.answer"
                    rows="2"
                    class="admin-textarea"
                    placeholder="Answer"
                ></textarea>
                <button type="button" x-on:click="removeItem(i)" class="text-xs text-red-500 hover:underline">Remove</button>
            </div>
        </template>

        <p x-show="items.length === 0" class="text-sm text-slate-400">No questions yet.</p>
    </div>

    {{-- IDs mode --}}
    <div x-show="source === 'ids'">
        <label class="admin-form-label">FAQ IDs (comma-separated)</label>
        <input
            type="text"
            name="data[faq_ids]"
            value="{{ old('data.faq_ids', implode(',', $block->data['faq_ids'] ?? [])) }}"
            class="admin-input"
            placeholder="e.g. 1,3,5"
        >
        <p class="mt-1 text-xs text-slate-400">Enter IDs from your FAQ library separated by commas.</p>
    </div>

</div>

<script>
function faqBlock(initialSource, initialItems) {
    return {
        source: initialSource,
        items: initialItems.length ? initialItems : [],
        addItem() { this.items.push({ question: '', answer: '' }); },
        removeItem(i) { this.items.splice(i, 1); },
    };
}
</script>
