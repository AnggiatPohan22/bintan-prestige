<div class="space-y-4" x-data="statsBlock(@js($block->data['items'] ?? []))">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="admin-form-label">Section Heading</label>
            <input type="text" name="data[heading]" value="{{ old('data.heading', $block->data['heading'] ?? '') }}" class="admin-input" maxlength="255" placeholder="Why travel with us">
        </div>
        <div>
            <label class="admin-form-label">Alignment</label>
            <select name="data[alignment]" class="admin-input">
                <option value="left" @selected(($block->data['alignment'] ?? 'center') === 'left')>Left</option>
                <option value="center" @selected(($block->data['alignment'] ?? 'center') === 'center')>Center</option>
            </select>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        <span class="admin-form-label mb-0">Statistics</span>
        <button type="button" x-on:click="addItem()" x-bind:disabled="items.length >= 8" class="admin-btn-soft px-3 py-1.5 text-xs">+ Add Stat</button>
    </div>

    <template x-for="(item, index) in items" :key="index">
        <div class="grid grid-cols-1 gap-3 rounded-lg border border-admin p-3 md:grid-cols-12">
            <div class="md:col-span-2">
                <label class="admin-form-label">Value</label>
                <input type="text" :name="`data[items][${index}][value]`" x-model="item.value" class="admin-input" maxlength="50" placeholder="10+">
            </div>
            <div class="md:col-span-3">
                <label class="admin-form-label">Label</label>
                <input type="text" :name="`data[items][${index}][label]`" x-model="item.label" class="admin-input" maxlength="100" placeholder="Years of experience">
            </div>
            <div class="md:col-span-6">
                <label class="admin-form-label">Description</label>
                <input type="text" :name="`data[items][${index}][description]`" x-model="item.description" class="admin-input" maxlength="500" placeholder="Optional supporting detail">
            </div>
            <div class="flex items-end md:col-span-1">
                <button type="button" x-on:click="removeItem(index)" class="text-xs text-red-500 hover:underline">Remove</button>
            </div>
        </div>
    </template>

    <p x-show="items.length === 0" class="text-sm text-admin-secondary">No statistics yet.</p>
</div>

<script>
function statsBlock(initialItems) {
    return {
        items: Array.isArray(initialItems) ? initialItems : [],
        addItem() {
            if (this.items.length < 8) this.items.push({ value: '', label: '', description: '' });
        },
        removeItem(index) { this.items.splice(index, 1); },
    };
}
</script>
