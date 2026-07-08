<?php

namespace App\Support;

/**
 * Resolves a content entry's `template` string into a render container + schema
 * type. Entry templates render only the entry body (block tree), so they map to a
 * layout container class rather than a full page template view (cf. PageTemplateRegistry).
 *
 * Unknown / empty template strings fall back to `default`.
 */
final class ContentEntryTemplateRegistry
{
    /** @var array<string, array{container: string, schema_type: string}> */
    private const TEMPLATES = [
        'default' => [
            'container'   => 'mx-auto max-w-3xl px-6',
            'schema_type' => 'Article',
        ],
        'contained' => [
            'container'   => 'mx-auto max-w-4xl px-6',
            'schema_type' => 'Article',
        ],
        'full-width' => [
            'container'   => 'w-full',
            'schema_type' => 'WebPage',
        ],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    /**
     * Human labels for the admin template picker.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'default'    => 'Default (narrow)',
            'contained'  => 'Contained (wider article)',
            'full-width' => 'Full width',
        ];
    }

    public static function keyFor(?string $template): string
    {
        return is_string($template) && array_key_exists($template, self::TEMPLATES)
            ? $template
            : 'default';
    }

    public static function containerFor(?string $template): string
    {
        return self::TEMPLATES[self::keyFor($template)]['container'];
    }

    public static function schemaTypeFor(?string $template): string
    {
        return self::TEMPLATES[self::keyFor($template)]['schema_type'];
    }
}
