<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageBlock;
use App\Support\BlockStyle;
use App\Support\InlineContentSanitizer;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageBlockService
{
    public function nextSortOrder(Page $page, ?int $parentId = null): int
    {
        $max = $page->blocks()->where('parent_block_id', $parentId)->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }

    public function reorder(Page $page, array $ids): void
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->all();

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['ids' => 'Block order contains duplicate items.']);
        }

        $submittedBlocks = $page->blocks()->whereIn('id', $ids)->get(['id', 'parent_block_id']);

        if ($submittedBlocks->count() !== count($ids) || $submittedBlocks->pluck('parent_block_id')->unique()->count() !== 1) {
            throw ValidationException::withMessages(['ids' => 'Block order must contain siblings from the same container.']);
        }

        $parentId = $submittedBlocks->first()?->parent_block_id;
        $expected = $page->blocks()
            ->where('parent_block_id', $parentId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $submitted = collect($ids)->sort()->values()->all();

        if ($expected !== $submitted) {
            throw ValidationException::withMessages(['ids' => 'Submit the complete block list for this page when reordering.']);
        }

        DB::transaction(function () use ($page, $ids): void {
            foreach ($ids as $sortOrder => $id) {
                $page->blocks()->whereKey($id)->update(['sort_order' => $sortOrder]);
            }
        });
    }

    private function defaultBackground(): array
    {
        return [
            'color' => '',
            'image' => '',
            'position' => 'center',
            'repeat' => 'no-repeat',
            'size' => 'cover',
            'opacity' => 100,
        ];
    }

    public function defaultDataFor(string $blockType): array
    {
        $bg = ['background' => $this->defaultBackground()];

        return match ($blockType) {
            'group' => $bg + [
                'width' => 'contained',
                'spacing' => 'md',
            ],
            'columns' => $bg + [
                'width' => 'wide',
                'columns' => 2,
                'gap' => 'md',
                'stack_mobile' => true,
            ],
            'hero' => $bg + [
                'title' => '',
                'subtitle' => '',
                'image' => '',
                'cta_text' => '',
                'cta_url' => '',
                'background_color' => '#0f0f0f',
                'overlay_opacity' => 40,
                'has_overlay' => true,
                'min_height' => 'large',
            ],
            'heading' => $bg + [
                'text' => '',
                'level' => 'h2',
                'alignment' => 'left',
            ],
            'text' => $bg + [
                'heading' => '',
                'body_html' => '',
            ],
            'image' => $bg + [
                'src' => '',
                'alt' => '',
                'caption' => '',
                'width_class' => 'full',
            ],
            'gallery' => $bg + [
                'images' => [],
                'columns' => 3,
                'gap' => 'md',
                'aspect_ratio' => 'auto',
                'show_captions' => false,
                'lightbox_enabled' => true,
                'autoplay' => false,
                'caption' => '',
            ],
            'video_embed' => $bg + [
                'url' => '',
                'title' => '',
                'caption' => '',
                'aspect_ratio' => '16-9',
            ],
            'button_group' => $bg + [
                'buttons' => [],
                'alignment' => 'left',
            ],
            'stats' => $bg + [
                'heading' => '',
                'items' => [],
                'alignment' => 'center',
            ],
            'tour_itinerary' => $bg + [
                'heading' => '',
                'intro' => '',
                'items' => [],
            ],
            'pricing_table' => $bg + [
                'heading' => '',
                'intro' => '',
                'plans' => [],
            ],
            'cta' => $bg + [
                'title' => '',
                'description' => '',
                'button_text' => '',
                'button_url' => '',
                'style' => 'dark',
            ],
            'products_grid' => $bg + [
                'category_id' => null,
                'destination_id' => null,
                'limit' => 6,
                'show_price' => true,
            ],
            'content_query' => $bg + [
                'content_type' => null,
                'heading' => '',
                'orderby' => 'newest',
                'columns' => '3',
                'limit' => 6,
                'show_excerpt' => true,
            ],
            'content_field' => $bg + [
                'field_key' => '',
                'entry_id' => null,
                'label' => '',
                'show_label' => true,
            ],
            'faq' => $bg + [
                'source' => 'inline',
                'faq_ids' => [],
                'items' => [],
            ],
            'testimonials' => $bg + [
                'items' => [],
            ],
            'map' => $bg + [
                'embed_url' => '',
                'address' => '',
                'zoom' => 14,
            ],
            'divider' => $bg + [
                'style' => 'line',
            ],
            'contact_form' => $bg + [
                'form_definition_id' => null,
                'title' => '',
                'description' => '',
            ],
            default => $bg,
        };
    }

    public function validateAndSanitizeData(string $blockType, array $data): array
    {
        if ($blockType === 'faq' && isset($data['faq_ids']) && is_string($data['faq_ids'])) {
            $data['faq_ids'] = array_values(array_filter(
                array_map('trim', explode(',', $data['faq_ids'])),
                static fn (string $id): bool => $id !== ''
            ));
        }

        if (in_array($blockType, ['group', 'columns'], true)) {
            $data = $this->normalizeAdvanced($data);
        }

        $validated = Validator::make($data, $this->rulesFor($blockType))->validate();

        $validated = $this->sanitizeInlineFields($blockType, $validated);

        if ($blockType === 'video_embed' && ! empty($validated['url'])) {
            $validated['url'] = $this->canonicalVideoEmbedUrl($validated['url']);
        }

        return $validated;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeInlineFields(string $blockType, array $data): array
    {
        $fields = config("blocks.{$blockType}.fields", []);

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            $editType = $field['inline'] ?? null;

            if (! is_string($key) || ! is_string($editType) || ! isset($data[$key]) || ! is_string($data[$key])) {
                continue;
            }

            $data[$key] = $editType === 'richtext'
                ? InlineContentSanitizer::richtext($data[$key])
                : InlineContentSanitizer::plaintext($data[$key]);
        }

        return $data;
    }

    private function rulesFor(string $blockType): array
    {
        $rules = $this->backgroundRules();

        return $rules + match ($blockType) {
            'group' => $this->advancedRules() + [
                'width' => ['nullable', Rule::in(['contained', 'wide', 'full'])],
                'spacing' => ['nullable', Rule::in(['none', 'sm', 'md', 'lg'])],
            ],
            'columns' => $this->advancedRules() + [
                'width' => ['nullable', Rule::in(['contained', 'wide', 'full'])],
                'columns' => ['nullable', 'integer', 'between:2,4'],
                'gap' => ['nullable', Rule::in(['none', 'sm', 'md', 'lg'])],
                'stack_mobile' => ['nullable', 'boolean'],
            ],
            'hero' => [
                'title' => ['nullable', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:1000'],
                'image' => $this->imagePathRules(),
                'cta_text' => ['nullable', 'string', 'max:100'],
                'cta_url' => $this->linkRules(),
                'background_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'overlay_opacity' => ['nullable', 'integer', 'between:0,90'],
                'has_overlay' => ['nullable', 'boolean'],
                'min_height' => ['nullable', Rule::in(['small', 'medium', 'large'])],
            ],
            'heading' => [
                'text' => ['nullable', 'string', 'max:500'],
                'level' => ['nullable', Rule::in(['h2', 'h3', 'h4', 'h5', 'h6'])],
                'alignment' => ['nullable', Rule::in(['left', 'center', 'right'])],
            ],
            'text' => [
                'heading' => ['nullable', 'string', 'max:255'],
                'body_html' => ['nullable', 'string', 'max:50000'],
            ],
            'image' => [
                'src' => $this->imagePathRules(),
                'alt' => ['nullable', 'string', 'max:500'],
                'caption' => ['nullable', 'string', 'max:1000'],
                'width_class' => ['nullable', Rule::in(['full', 'half', 'third'])],
            ],
            'gallery' => [
                'images' => ['nullable', 'array', 'max:50'],
                'images.*' => ['array:src,alt,caption'],
                'images.*.src' => $this->imagePathRules(),
                'images.*.alt' => ['nullable', 'string', 'max:500'],
                'images.*.caption' => ['nullable', 'string', 'max:1000'],
                'columns' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
                'gap' => ['nullable', Rule::in(['sm', 'md', 'lg'])],
                'aspect_ratio' => ['nullable', Rule::in(['auto', 'square', '16-9', '4-3'])],
                'show_captions' => ['nullable', 'boolean'],
                'lightbox_enabled' => ['nullable', 'boolean'],
                'autoplay' => ['nullable', 'boolean'],
                'caption' => ['nullable', 'string', 'max:1000'],
            ],
            'video_embed' => [
                'url' => $this->videoUrlRules(),
                'title' => ['nullable', 'string', 'max:255'],
                'caption' => ['nullable', 'string', 'max:1000'],
                'aspect_ratio' => ['nullable', Rule::in(['16-9', '4-3', '1-1'])],
            ],
            'button_group' => [
                'buttons' => ['nullable', 'array', 'max:6'],
                'buttons.*' => ['array:text,url,style'],
                'buttons.*.text' => ['nullable', 'string', 'max:100'],
                'buttons.*.url' => $this->linkRules(),
                'buttons.*.style' => ['nullable', Rule::in(['primary', 'secondary', 'link'])],
                'alignment' => ['nullable', Rule::in(['left', 'center', 'right'])],
            ],
            'stats' => [
                'heading' => ['nullable', 'string', 'max:255'],
                'items' => ['nullable', 'array', 'max:8'],
                'items.*' => ['array:value,label,description'],
                'items.*.value' => ['nullable', 'string', 'max:50'],
                'items.*.label' => ['nullable', 'string', 'max:100'],
                'items.*.description' => ['nullable', 'string', 'max:500'],
                'alignment' => ['nullable', Rule::in(['left', 'center'])],
            ],
            'tour_itinerary' => [
                'heading' => ['nullable', 'string', 'max:255'],
                'intro' => ['nullable', 'string', 'max:1000'],
                'items' => ['nullable', 'array', 'max:30'],
                'items.*' => ['array:marker,title,description'],
                'items.*.marker' => ['nullable', 'string', 'max:50'],
                'items.*.title' => ['nullable', 'string', 'max:255'],
                'items.*.description' => ['nullable', 'string', 'max:3000'],
            ],
            'pricing_table' => [
                'heading' => ['nullable', 'string', 'max:255'],
                'intro' => ['nullable', 'string', 'max:1000'],
                'plans' => ['nullable', 'array', 'max:6'],
                'plans.*' => ['array:name,price,currency,period,features,button_text,button_url,featured'],
                'plans.*.name' => ['nullable', 'string', 'max:150'],
                'plans.*.price' => ['nullable', 'string', 'max:50'],
                'plans.*.currency' => ['nullable', 'string', 'max:10'],
                'plans.*.period' => ['nullable', 'string', 'max:50'],
                'plans.*.features' => ['nullable', 'array', 'max:20'],
                'plans.*.features.*' => ['nullable', 'string', 'max:255'],
                'plans.*.button_text' => ['nullable', 'string', 'max:100'],
                'plans.*.button_url' => $this->linkRules(),
                'plans.*.featured' => ['nullable', 'boolean'],
            ],
            'cta' => [
                'title' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
                'button_text' => ['nullable', 'string', 'max:100'],
                'button_url' => $this->linkRules(),
                'style' => ['nullable', Rule::in(['dark', 'light', 'gold'])],
            ],
            'products_grid' => [
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
                'limit' => ['nullable', 'integer', 'between:3,12'],
                'show_price' => ['nullable', 'boolean'],
            ],
            'content_query' => [
                'content_type' => ['nullable', 'integer', 'exists:content_types,id'],
                'heading' => ['nullable', 'string', 'max:255'],
                'orderby' => ['nullable', Rule::in(['newest', 'oldest', 'title', 'sort_order'])],
                'columns' => ['nullable', Rule::in(['1', '2', '3'])],
                'limit' => ['nullable', 'integer', 'between:1,24'],
                'show_excerpt' => ['nullable', 'boolean'],
            ],
            'content_field' => [
                'field_key' => ['nullable', 'string', 'max:100'],
                'entry_id' => ['nullable', 'integer', 'exists:content_entries,id'],
                'label' => ['nullable', 'string', 'max:255'],
                'show_label' => ['nullable', 'boolean'],
            ],
            'faq' => [
                'source' => ['nullable', Rule::in(['inline', 'ids'])],
                'faq_ids' => ['nullable', 'array', 'max:100'],
                'faq_ids.*' => ['integer', 'distinct', 'exists:faqs,id'],
                'items' => ['nullable', 'array', 'max:100'],
                'items.*' => ['array:question,answer'],
                'items.*.question' => ['nullable', 'string', 'max:1000'],
                'items.*.answer' => ['nullable', 'string', 'max:5000'],
            ],
            'testimonials' => [
                'items' => ['nullable', 'array', 'max:50'],
                'items.*' => ['array:name,text,rating,avatar'],
                'items.*.name' => ['nullable', 'string', 'max:255'],
                'items.*.text' => ['nullable', 'string', 'max:5000'],
                'items.*.rating' => ['nullable', 'integer', 'between:1,5'],
                'items.*.avatar' => $this->imagePathRules(),
            ],
            'map' => [
                'embed_url' => $this->httpUrlRules(),
                'address' => ['nullable', 'string', 'max:1000'],
                'zoom' => ['nullable', 'integer', 'between:1,20'],
            ],
            'divider' => [
                'style' => ['nullable', Rule::in(['line', 'space', 'gold-line'])],
            ],
            'contact_form' => [
                'form_definition_id' => ['nullable', 'integer', 'exists:form_definitions,id'],
                'title' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ],
            default => throw new \InvalidArgumentException("Unsupported block type [{$blockType}]."),
        };
    }

    private function backgroundRules(): array
    {
        return [
            'background' => ['nullable', 'array:color,image,position,repeat,size,opacity'],
            'background.color' => ['nullable', 'regex:/^(transparent|#[0-9a-fA-F]{6})$/'],
            'background.image' => $this->imagePathRules(),
            'background.position' => ['nullable', Rule::in([
                'center', 'top center', 'bottom center', 'top left', 'top right',
                'bottom left', 'bottom right',
            ])],
            'background.repeat' => ['nullable', Rule::in(['no-repeat', 'repeat', 'repeat-x', 'repeat-y'])],
            'background.size' => ['nullable', Rule::in(['cover', 'contain', 'auto'])],
            'background.opacity' => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    /**
     * Advanced-tab rules shared by Group and Columns (margin, padding, z-index,
     * CSS id/classes, responsive visibility, custom CSS). All optional.
     *
     * @return array<string, mixed>
     */
    private function advancedRules(): array
    {
        $box = ['nullable', 'array:top,right,bottom,left'];
        $side = ['nullable', 'integer', 'between:-2000,2000'];

        return [
            'margin' => $box,
            'margin.top' => $side, 'margin.right' => $side, 'margin.bottom' => $side, 'margin.left' => $side,
            'padding' => $box,
            'padding.top' => $side, 'padding.right' => $side, 'padding.bottom' => $side, 'padding.left' => $side,
            'z_index' => ['nullable', 'integer', 'between:-999,9999'],
            'css_id' => ['nullable', 'string', 'max:64'],
            'css_classes' => ['nullable', 'string', 'max:255'],
            'hide_desktop' => ['nullable', 'boolean'],
            'hide_tablet' => ['nullable', 'boolean'],
            'hide_mobile' => ['nullable', 'boolean'],
            'custom_css' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Coerce Advanced inputs to clean shapes before validation so empty builder
     * values ('' from number inputs) never fail the integer rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeAdvanced(array $data): array
    {
        foreach (['margin', 'padding'] as $box) {
            if (isset($data[$box]) && is_array($data[$box])) {
                $clean = [];
                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    $v = $data[$box][$side] ?? null;
                    if (is_numeric($v)) {
                        $clean[$side] = (int) $v;
                    }
                }
                $data[$box] = $clean === [] ? null : $clean;
            }
        }

        if (isset($data['z_index'])) {
            $data['z_index'] = is_numeric($data['z_index']) ? (int) $data['z_index'] : null;
        }
        if (isset($data['css_id'])) {
            $data['css_id'] = BlockStyle::cssId($data['css_id']);
        }
        if (isset($data['css_classes'])) {
            $data['css_classes'] = BlockStyle::cssClasses($data['css_classes']) ?: null;
        }
        if (isset($data['custom_css'])) {
            $data['custom_css'] = BlockStyle::customCss($data['custom_css']) ?: null;
        }
        foreach (['hide_desktop', 'hide_tablet', 'hide_mobile'] as $flag) {
            if (isset($data[$flag])) {
                $data[$flag] = filter_var($data[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }

    public function validateParent(Page $page, PageBlock $block, ?int $parentId): ?int
    {
        if ($parentId === null) {
            return null;
        }

        $parent = $page->blocks()->find($parentId);

        if (! $parent || ! $parent->isContainer()) {
            throw ValidationException::withMessages(['parent_block_id' => 'Choose a Group or Columns block from this page.']);
        }

        if ($parent->is($block)) {
            throw ValidationException::withMessages(['parent_block_id' => 'A block cannot contain itself.']);
        }

        if ($parent->block_type === 'columns' && $block->block_type !== 'group') {
            throw ValidationException::withMessages(['parent_block_id' => 'Columns can contain Group blocks only.']);
        }

        $depth = 1;
        $ancestor = $parent;
        while ($ancestor->parent_block_id !== null) {
            if ($ancestor->parent_block_id === $block->id) {
                throw ValidationException::withMessages(['parent_block_id' => 'A block cannot be moved inside its own descendants.']);
            }

            $ancestor = $page->blocks()->find($ancestor->parent_block_id);
            if (! $ancestor) {
                break;
            }

            $depth++;
            if ($depth >= 5) {
                throw ValidationException::withMessages(['parent_block_id' => 'Block nesting is limited to five levels.']);
            }
        }

        return $parent->id;
    }

    private function imagePathRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === '') {
                return;
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                if (! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $fail("The {$attribute} field must use an HTTP or HTTPS URL.");
                }

                return;
            }

            if (str_contains($value, '..') || str_contains($value, '\\') || str_starts_with($value, '//')
                || preg_match('/[\x00-\x1F\x7F]/', $value)
                || ! preg_match('/^\/?[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $value)) {
                $fail("The {$attribute} field must be a safe image path or HTTP(S) URL.");
            }
        }];
    }

    private function linkRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === '') {
                return;
            }

            $isLocal = (str_starts_with($value, '/') && ! str_starts_with($value, '//'))
                || str_starts_with($value, '#');
            $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

            if (! $isLocal && ! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                $fail("The {$attribute} field must be a safe local link or supported URL.");
            }
        }];
    }

    private function httpUrlRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            $scheme = strtolower((string) parse_url((string) $value, PHP_URL_SCHEME));

            if ($value !== '' && (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true))) {
                $fail("The {$attribute} field must be a valid HTTP or HTTPS URL.");
            }
        }];
    }

    private function videoUrlRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            if ($value !== '' && $this->canonicalVideoEmbedUrl($value) === '') {
                $fail("The {$attribute} field must be a supported YouTube or Vimeo URL.");
            }
        }];
    }

    private function canonicalVideoEmbedUrl(string $url): string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $youtubeId = null;
        if ($host === 'youtu.be') {
            $youtubeId = explode('/', $path)[0];
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if (str_starts_with($path, 'embed/')) {
                $youtubeId = explode('/', $path)[1] ?? null;
            } elseif ($path === 'watch') {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $youtubeId = $query['v'] ?? null;
            }
        } elseif (in_array($host, ['youtube-nocookie.com', 'www.youtube-nocookie.com'], true) && str_starts_with($path, 'embed/')) {
            $youtubeId = explode('/', $path)[1] ?? null;
        }

        if (is_string($youtubeId) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $youtubeId)) {
            return 'https://www.youtube-nocookie.com/embed/'.$youtubeId;
        }

        $vimeoId = null;
        if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true)) {
            $vimeoId = explode('/', $path)[0];
        } elseif ($host === 'player.vimeo.com' && str_starts_with($path, 'video/')) {
            $vimeoId = explode('/', $path)[1] ?? null;
        }

        if (is_string($vimeoId) && preg_match('/^[0-9]{6,12}$/', $vimeoId)) {
            return 'https://player.vimeo.com/video/'.$vimeoId;
        }

        return '';
    }
}
