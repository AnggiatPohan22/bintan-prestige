<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="admin-form-label">YouTube or Vimeo URL</label>
        <input
            type="url"
            name="data[url]"
            value="{{ old('data.url', $block->data['url'] ?? '') }}"
            class="admin-input"
            placeholder="https://www.youtube.com/watch?v=..."
        >
        <p class="mt-1 text-xs text-slate-400">Only YouTube and Vimeo URLs are accepted. The saved URL is converted to a safe embed URL.</p>
    </div>

    <div>
        <label class="admin-form-label">Accessible Title</label>
        <input type="text" name="data[title]" value="{{ old('data.title', $block->data['title'] ?? '') }}" class="admin-input" maxlength="255" placeholder="Bintan island tour video">
    </div>

    <div>
        <label class="admin-form-label">Aspect Ratio</label>
        <select name="data[aspect_ratio]" class="admin-input">
            @foreach(['16-9' => 'Widescreen (16:9)', '4-3' => 'Standard (4:3)', '1-1' => 'Square (1:1)'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['aspect_ratio'] ?? '16-9') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="admin-form-label">Caption</label>
        <input type="text" name="data[caption]" value="{{ old('data.caption', $block->data['caption'] ?? '') }}" class="admin-input" maxlength="1000" placeholder="Optional caption">
    </div>
</div>
