<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="admin-form-label">Title</label>
        <input type="text" name="data[title]" value="{{ old('data.title', $block->data['title'] ?? '') }}" class="admin-input" placeholder="CTA headline">
    </div>

    <div class="md:col-span-2">
        <label class="admin-form-label">Description</label>
        <textarea name="data[description]" rows="3" class="admin-textarea" placeholder="Supporting text">{{ old('data.description', $block->data['description'] ?? '') }}</textarea>
    </div>

    <div>
        <label class="admin-form-label">Button Text</label>
        <input type="text" name="data[button_text]" value="{{ old('data.button_text', $block->data['button_text'] ?? '') }}" class="admin-input" placeholder="e.g. Book Now">
    </div>

    <div>
        <label class="admin-form-label">Button URL</label>
        <input type="text" name="data[button_url]" value="{{ old('data.button_url', $block->data['button_url'] ?? '') }}" class="admin-input" placeholder="/products">
    </div>

    <div>
        <label class="admin-form-label">Style</label>
        <select name="data[style]" class="admin-input">
            @foreach(['dark' => 'Dark (black background)', 'light' => 'Light (white background)', 'gold' => 'Gold accent'] as $val => $label)
                <option value="{{ $val }}" @selected(($block->data['style'] ?? 'dark') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
