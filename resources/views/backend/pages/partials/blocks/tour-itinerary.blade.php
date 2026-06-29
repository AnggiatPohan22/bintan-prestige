<div class="space-y-4" x-data="tourItineraryBlock(@js($block->data['items'] ?? []))">
    <div>
        <label class="admin-form-label">Section Heading</label>
        <input type="text" name="data[heading]" value="{{ old('data.heading', $block->data['heading'] ?? '') }}" class="admin-input" maxlength="255" placeholder="Your itinerary">
    </div>
    <div>
        <label class="admin-form-label">Introduction</label>
        <textarea name="data[intro]" rows="2" class="admin-textarea" maxlength="1000" placeholder="Optional overview">{{ old('data.intro', $block->data['intro'] ?? '') }}</textarea>
    </div>

    <div class="flex items-center justify-between gap-3">
        <span class="admin-form-label mb-0">Timeline Items</span>
        <button type="button" x-on:click="addItem()" x-bind:disabled="items.length >= 30" class="admin-btn-soft px-3 py-1.5 text-xs">+ Add Step</button>
    </div>

    <template x-for="(item, index) in items" :key="index">
        <div class="grid grid-cols-1 gap-3 rounded-lg border border-admin p-3 md:grid-cols-12">
            <div class="md:col-span-2">
                <label class="admin-form-label">Marker</label>
                <input type="text" :name="`data[items][${index}][marker]`" x-model="item.marker" class="admin-input" maxlength="50" placeholder="Day 1">
            </div>
            <div class="md:col-span-9">
                <label class="admin-form-label">Title</label>
                <input type="text" :name="`data[items][${index}][title]`" x-model="item.title" class="admin-input" maxlength="255" placeholder="Arrival and resort check-in">
            </div>
            <div class="flex items-end md:col-span-1">
                <button type="button" x-on:click="removeItem(index)" class="text-xs text-red-500 hover:underline">Remove</button>
            </div>
            <div class="md:col-span-12">
                <label class="admin-form-label">Description</label>
                <textarea :name="`data[items][${index}][description]`" x-model="item.description" rows="3" class="admin-textarea" maxlength="3000" placeholder="Describe this part of the journey"></textarea>
            </div>
        </div>
    </template>

    <p x-show="items.length === 0" class="text-sm text-admin-secondary">No itinerary steps yet.</p>
</div>

<script>
function tourItineraryBlock(initialItems) {
    return {
        items: Array.isArray(initialItems) ? initialItems : [],
        addItem() {
            if (this.items.length < 30) this.items.push({ marker: '', title: '', description: '' });
        },
        removeItem(index) { this.items.splice(index, 1); },
    };
}
</script>
