<?php

namespace App\Support;

class BrandColorSettings
{
    public const GROUP = 'brand_colors';

    public static function fields(): array
    {
        return [
            [
                'group_label' => 'Palette',
                'key' => 'brand.palette.primary',
                'slug' => 'palette_primary',
                'label' => 'Primary',
                'default' => '#090806',
                'hint' => 'Main brand color for dark surfaces, strong buttons, and header states.',
            ],
            [
                'group_label' => 'Palette',
                'key' => 'brand.palette.secondary',
                'slug' => 'palette_secondary',
                'label' => 'Secondary',
                'default' => '#c8a24a',
                'hint' => 'Main accent color for highlights, hover states, and premium details.',
            ],
            [
                'group_label' => 'Palette',
                'key' => 'brand.palette.accent',
                'slug' => 'palette_accent',
                'label' => 'Accent',
                'default' => '#8a681a',
                'hint' => 'Supporting accent for labels, badges, and small highlights.',
            ],
            [
                'group_label' => 'Surfaces',
                'key' => 'brand.surface.body',
                'slug' => 'surface_body',
                'label' => 'Body background',
                'default' => '#fbf7ed',
                'hint' => 'Base page background used by public frontend pages.',
            ],
            [
                'group_label' => 'Surfaces',
                'key' => 'brand.surface.card',
                'slug' => 'surface_card',
                'label' => 'Card background',
                'default' => '#ffffff',
                'hint' => 'Default surface for cards, panels, and clean content blocks.',
            ],
            [
                'group_label' => 'Surfaces',
                'key' => 'brand.surface.soft',
                'slug' => 'surface_soft',
                'label' => 'Soft background',
                'default' => '#fbf7ed',
                'hint' => 'Subtle brand-tinted background for soft sections and placeholders.',
            ],
            [
                'group_label' => 'Surfaces',
                'key' => 'brand.surface.dark',
                'slug' => 'surface_dark',
                'label' => 'Dark background',
                'default' => '#090806',
                'hint' => 'Dark surface for footer, dark CTA, and fallback hero areas.',
            ],
            [
                'group_label' => 'Surfaces',
                'key' => 'brand.surface.border',
                'slug' => 'surface_border',
                'label' => 'Border',
                'default' => '#e7dcc2',
                'hint' => 'Shared border color for cards, inputs, frames, and subtle dividers.',
            ],
            [
                'group_label' => 'Typography',
                'key' => 'brand.text.title',
                'slug' => 'text_title',
                'label' => 'Title text',
                'default' => '#17130c',
                'hint' => 'Primary heading and title color.',
            ],
            [
                'group_label' => 'Typography',
                'key' => 'brand.text.body',
                'slug' => 'text_body',
                'label' => 'Paragraph text',
                'default' => '#17130c',
                'hint' => 'Main paragraph and readable body text color.',
            ],
            [
                'group_label' => 'Typography',
                'key' => 'brand.text.muted',
                'slug' => 'text_muted',
                'label' => 'Muted text',
                'default' => '#6f6a60',
                'hint' => 'Secondary descriptions, helper text, and metadata.',
            ],
            [
                'group_label' => 'Typography',
                'key' => 'brand.text.link',
                'slug' => 'text_link',
                'label' => 'Link text',
                'default' => '#8a681a',
                'hint' => 'Default link color for public content.',
            ],
            [
                'group_label' => 'Typography',
                'key' => 'brand.text.on_dark',
                'slug' => 'text_on_dark',
                'label' => 'Text on dark',
                'default' => '#f5ead0',
                'hint' => 'Readable text or icons on dark brand surfaces.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.primary.bg',
                'slug' => 'button_primary_bg',
                'label' => 'Primary button background',
                'default' => '#090806',
                'hint' => 'Default background for primary buttons.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.primary.text',
                'slug' => 'button_primary_text',
                'label' => 'Primary button text',
                'default' => '#f5ead0',
                'hint' => 'Default text color for primary buttons.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.primary.hover_bg',
                'slug' => 'button_primary_hover_bg',
                'label' => 'Primary hover background',
                'default' => '#c8a24a',
                'hint' => 'Hover background for primary buttons.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.primary.hover_text',
                'slug' => 'button_primary_hover_text',
                'label' => 'Primary hover text',
                'default' => '#090806',
                'hint' => 'Hover text color for primary buttons.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.cta.bg',
                'slug' => 'button_cta_bg',
                'label' => 'CTA button background',
                'default' => '#c8a24a',
                'hint' => 'High-emphasis CTA background for future CTA-specific buttons.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.cta.text',
                'slug' => 'button_cta_text',
                'label' => 'CTA button text',
                'default' => '#090806',
                'hint' => 'High-emphasis CTA text color.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.submit.bg',
                'slug' => 'button_submit_bg',
                'label' => 'Submit button background',
                'default' => '#090806',
                'hint' => 'Form submit button background.',
            ],
            [
                'group_label' => 'Buttons',
                'key' => 'brand.button.submit.text',
                'slug' => 'button_submit_text',
                'label' => 'Submit button text',
                'default' => '#f5ead0',
                'hint' => 'Form submit button text color.',
            ],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        return collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?: $field['default']];
            })
            ->all();
    }
}
