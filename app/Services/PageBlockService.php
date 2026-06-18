<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageBlock;

class PageBlockService
{
    public function nextSortOrder(Page $page): int
    {
        $max = $page->blocks()->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }

    public function reorder(Page $page, array $ids): void
    {
        foreach ($ids as $sortOrder => $id) {
            $page->blocks()->where('id', $id)->update(['sort_order' => $sortOrder]);
        }
    }

    private function defaultBackground(): array
    {
        return [
            'color'    => '',
            'image'    => '',
            'position' => 'center',
            'repeat'   => 'no-repeat',
            'size'     => 'cover',
            'opacity'  => 100,
        ];
    }

    public function defaultDataFor(string $blockType): array
    {
        $bg = ['background' => $this->defaultBackground()];

        return match ($blockType) {
            'hero' => $bg + [
                'title'            => '',
                'subtitle'         => '',
                'image'            => '',
                'cta_text'         => '',
                'cta_url'          => '',
                'background_color' => '#0f0f0f',
                'overlay_opacity'  => 40,
                'has_overlay'      => true,
                'min_height'       => 'large',
            ],
            'text' => $bg + [
                'heading'   => '',
                'body_html' => '',
            ],
            'image' => $bg + [
                'src'         => '',
                'alt'         => '',
                'caption'     => '',
                'width_class' => 'full',
            ],
            'gallery' => $bg + [
                'images'           => [],
                'columns'          => 3,
                'gap'              => 'md',
                'aspect_ratio'     => 'auto',
                'show_captions'    => false,
                'lightbox_enabled' => true,
                'autoplay'         => false,
                'caption'          => '',
            ],
            'cta' => $bg + [
                'title'       => '',
                'description' => '',
                'button_text' => '',
                'button_url'  => '',
                'style'       => 'dark',
            ],
            'products_grid' => $bg + [
                'category_id'    => null,
                'destination_id' => null,
                'limit'          => 6,
                'show_price'     => true,
            ],
            'faq' => $bg + [
                'source'  => 'inline',
                'faq_ids' => [],
                'items'   => [],
            ],
            'testimonials' => $bg + [
                'items' => [],
            ],
            'map' => $bg + [
                'embed_url' => '',
                'address'   => '',
                'zoom'      => 14,
            ],
            'divider' => $bg + [
                'style' => 'line',
            ],
            default => $bg,
        };
    }

    public function sanitizeData(string $blockType, array $data): array
    {
        if ($blockType === 'text' && isset($data['body_html'])) {
            $data['body_html'] = strip_tags(
                $data['body_html'],
                '<p><br><strong><em><ul><ol><li><a><h2><h3><blockquote>'
            );
        }

        return $data;
    }
}
