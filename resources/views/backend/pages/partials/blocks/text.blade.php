<div class="space-y-4">
    <div>
        <label class="admin-form-label">Heading</label>
        <input type="text" name="data[heading]" value="{{ old('data.heading', $block->data['heading'] ?? '') }}" class="admin-input" placeholder="Section heading (optional)">
    </div>

    <div>
        <label class="admin-form-label">Body HTML</label>
        <textarea
            name="data[body_html]"
            rows="8"
            class="admin-textarea font-mono text-sm"
            placeholder="Allowed tags: p, br, strong, em, ul, ol, li, a, h2, h3, blockquote"
        >{{ old('data.body_html', $block->data['body_html'] ?? '') }}</textarea>
        <p class="mt-1 text-xs text-admin-secondary">HTML is sanitized on save. Allowed: &lt;p&gt; &lt;br&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt; &lt;a&gt; &lt;h2&gt; &lt;h3&gt; &lt;blockquote&gt;</p>
    </div>
</div>
