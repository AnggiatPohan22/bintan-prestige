<div class="space-y-4" x-data="testimonialsBlock({{ json_encode($block->data['items'] ?? []) }})">

    <div class="flex items-center justify-between">
        <span class="admin-form-label mb-0">Testimonials</span>
        <button type="button" x-on:click="addItem()" class="admin-btn-soft px-3 py-1.5 text-xs">+ Add Testimonial</button>
    </div>

    <template x-for="(item, i) in items" :key="i">
        <div class="space-y-2 rounded-lg border border-slate-200 p-3">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <input
                    type="text"
                    :name="`data[items][${i}][name]`"
                    x-model="item.name"
                    class="admin-input"
                    placeholder="Name"
                >
                <input
                    type="number"
                    :name="`data[items][${i}][rating]`"
                    x-model="item.rating"
                    class="admin-input"
                    placeholder="Rating (1–5)"
                    min="1"
                    max="5"
                >
            </div>
            <textarea
                :name="`data[items][${i}][text]`"
                x-model="item.text"
                rows="2"
                class="admin-textarea"
                placeholder="Testimonial text"
            ></textarea>
            <input
                type="text"
                :name="`data[items][${i}][avatar]`"
                x-model="item.avatar"
                class="admin-input"
                placeholder="Avatar path (optional)"
            >
            <button type="button" x-on:click="removeItem(i)" class="text-xs text-red-500 hover:underline">Remove</button>
        </div>
    </template>

    <p x-show="items.length === 0" class="text-sm text-slate-400">No testimonials yet.</p>
</div>

<script>
function testimonialsBlock(initialItems) {
    return {
        items: initialItems.length ? initialItems : [],
        addItem() { this.items.push({ name: '', text: '', rating: 5, avatar: '' }); },
        removeItem(i) { this.items.splice(i, 1); },
    };
}
</script>
