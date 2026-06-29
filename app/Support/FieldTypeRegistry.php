<?php

namespace App\Support;

final class FieldTypeRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        /** @var array<string, array<string, mixed>> */
        return config('field-types');
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    /** @return array<string, mixed>|null */
    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /** @return array<string, array<string, mixed>> */
    public static function filterable(): array
    {
        return array_filter(
            self::all(),
            fn (array $def): bool => (bool) ($def['is_filterable'] ?? false)
        );
    }

    /** @return array<int, string> */
    public static function filterableKeys(): array
    {
        return array_keys(self::filterable());
    }
}
