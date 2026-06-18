<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="admin-form-label">Google Maps Embed URL</label>
        <input
            type="url"
            name="data[embed_url]"
            value="{{ old('data.embed_url', $block->data['embed_url'] ?? '') }}"
            class="admin-input"
            placeholder="https://www.google.com/maps/embed?..."
        >
        <p class="mt-1 text-xs text-slate-400">
            In Google Maps: Share → Embed a map → Copy the src URL from the iframe code.
        </p>
    </div>

    <div class="md:col-span-2">
        <label class="admin-form-label">Address (text)</label>
        <input
            type="text"
            name="data[address]"
            value="{{ old('data.address', $block->data['address'] ?? '') }}"
            class="admin-input"
            placeholder="e.g. Lagoi Bay, Bintan Island, Indonesia"
        >
    </div>

    <div>
        <label class="admin-form-label">Zoom Level</label>
        <input
            type="number"
            name="data[zoom]"
            value="{{ old('data.zoom', $block->data['zoom'] ?? 14) }}"
            class="admin-input"
            min="1"
            max="20"
        >
        <p class="mt-1 text-xs text-slate-400">1 (world) to 20 (building). Default: 14.</p>
    </div>
</div>
