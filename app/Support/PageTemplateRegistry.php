<?php

namespace App\Support;

final class PageTemplateRegistry
{
    private const TEMPLATES = [
        'default' => [
            'view' => 'frontend.templates.default',
            'schema_type' => 'WebPage',
        ],
        'full-width' => [
            'view' => 'frontend.templates.full-width',
            'schema_type' => 'WebPage',
        ],
        'contained' => [
            'view' => 'frontend.templates.contained',
            'schema_type' => 'Article',
        ],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function keyFor(?string $bladeFile): string
    {
        return is_string($bladeFile) && array_key_exists($bladeFile, self::TEMPLATES)
            ? $bladeFile
            : 'default';
    }

    public static function viewFor(?string $bladeFile): string
    {
        return self::TEMPLATES[self::keyFor($bladeFile)]['view'];
    }

    public static function schemaTypeFor(?string $bladeFile): string
    {
        return self::TEMPLATES[self::keyFor($bladeFile)]['schema_type'];
    }
}
