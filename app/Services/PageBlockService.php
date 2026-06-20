<?php

namespace App\Services;

use App\Models\Page;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageBlockService
{
    public function nextSortOrder(Page $page): int
    {
        $max = $page->blocks()->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }

    public function reorder(Page $page, array $ids): void
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->all();

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['ids' => 'Block order contains duplicate items.']);
        }

        $expected = $page->blocks()->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
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
                'title'              => '',
                'description'        => '',
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

        $validated = Validator::make($data, $this->rulesFor($blockType))->validate();

        if ($blockType === 'text' && isset($validated['body_html'])) {
            $validated['body_html'] = $this->sanitizeRichHtml($validated['body_html']);
        }

        return $validated;
    }

    private function rulesFor(string $blockType): array
    {
        $rules = $this->backgroundRules();

        return $rules + match ($blockType) {
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
                'title'              => ['nullable', 'string', 'max:255'],
                'description'        => ['nullable', 'string', 'max:1000'],
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

    private function sanitizeRichHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>');

        return preg_replace_callback(
            '/<([a-z0-9]+)\b([^>]*)>/i',
            function (array $match): string {
                $tag = strtolower($match[1]);

                if ($tag !== 'a') {
                    return "<{$tag}>";
                }

                preg_match_all(
                    '/\b(href|title|target|rel)\s*=\s*(["\'])(.*?)\2/i',
                    $match[2],
                    $attributes,
                    PREG_SET_ORDER
                );

                $safe = [];
                foreach ($attributes as $attribute) {
                    $name = strtolower($attribute[1]);
                    $value = $attribute[3];

                    if ($name === 'href' && ! $this->isSafeRichTextLink($value)) {
                        continue;
                    }

                    if ($name === 'target' && ! in_array($value, ['_blank', '_self'], true)) {
                        continue;
                    }

                    if ($name === 'rel') {
                        $tokens = array_intersect(preg_split('/\s+/', strtolower($value)), ['noopener', 'noreferrer', 'nofollow']);
                        $value = implode(' ', array_unique($tokens));
                        if ($value === '') {
                            continue;
                        }
                    }

                    $safe[$name] = $value;
                }

                if (($safe['target'] ?? null) === '_blank') {
                    $rel = preg_split('/\s+/', $safe['rel'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
                    $safe['rel'] = implode(' ', array_unique([...$rel, 'noopener', 'noreferrer']));
                }

                $serialized = '';
                foreach ($safe as $name => $value) {
                    $serialized .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
                }

                return '<a'.$serialized.'>';
            },
            $html
        ) ?? '';
    }

    private function isSafeRichTextLink(string $url): bool
    {
        if ((str_starts_with($url, '/') && ! str_starts_with($url, '//')) || str_starts_with($url, '#')) {
            return true;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https', 'mailto', 'tel'], true);
    }
}
