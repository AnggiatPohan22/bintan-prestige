<div
    class="space-y-4"
    x-data="buttonGroupBlock(@js($block->data['buttons'] ?? []))"
>
    <div class="flex items-center justify-between gap-3">
        <span class="admin-form-label mb-0">Buttons</span>
        <button type="button" x-on:click="addButton()" x-bind:disabled="buttons.length >= 6" class="admin-btn-soft px-3 py-1.5 text-xs">
            + Add Button
        </button>
    </div>

    <template x-for="(button, index) in buttons" :key="index">
        <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="admin-form-label">Text</label>
                <input type="text" :name="`data[buttons][${index}][text]`" x-model="button.text" class="admin-input" maxlength="100" placeholder="Explore Tours">
            </div>
            <div class="md:col-span-5">
                <label class="admin-form-label">URL</label>
                <input type="text" :name="`data[buttons][${index}][url]`" x-model="button.url" class="admin-input" placeholder="/products">
            </div>
            <div class="md:col-span-2">
                <label class="admin-form-label">Style</label>
                <select :name="`data[buttons][${index}][style]`" x-model="button.style" class="admin-input">
                    <option value="primary">Primary</option>
                    <option value="secondary">Secondary</option>
                    <option value="link">Text link</option>
                </select>
            </div>
            <div class="flex items-end md:col-span-1">
                <button type="button" x-on:click="removeButton(index)" class="text-xs text-red-500 hover:underline">Remove</button>
            </div>
        </div>
    </template>

    <p x-show="buttons.length === 0" class="text-sm text-slate-400">No buttons yet.</p>

    <div class="max-w-xs">
        <label class="admin-form-label">Alignment</label>
        <select name="data[alignment]" class="admin-input">
            @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['alignment'] ?? 'left') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<script>
function buttonGroupBlock(initialButtons) {
    return {
        buttons: Array.isArray(initialButtons) ? initialButtons : [],
        addButton() {
            if (this.buttons.length < 6) {
                this.buttons.push({ text: '', url: '', style: 'primary' });
            }
        },
        removeButton(index) { this.buttons.splice(index, 1); },
    };
}
</script>
