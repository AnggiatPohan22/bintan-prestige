<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Locale catalogue helper (Phase 7 — i18n foundation, Task A2).
 *
 * Single source of truth for reading config/locales.php: which locales exist,
 * which are active, which is the (unprefixed) default, and how to rewrite the
 * current URL into another locale for the switcher. No translation lookups live
 * here — that is the Translatable sidecar / row-per-locale layer (B1+).
 */
class Locales
{
    /** Full catalogue (code => meta), active or not. */
    public static function all(): array
    {
        return (array) config('locales.locales', []);
    }

    /** Active locales only (code => meta), config order preserved. */
    public static function active(): array
    {
        return array_filter(self::all(), fn (array $meta) => (bool) ($meta['is_active'] ?? false));
    }

    /** Active locale codes. */
    public static function activeCodes(): array
    {
        return array_keys(self::active());
    }

    /** The default locale — served with no URL prefix. */
    public static function default(): string
    {
        return (string) config('locales.default', config('app.locale', 'en'));
    }

    /** Active codes excluding the default (these get a /{code} prefix). */
    public static function nonDefaultActive(): array
    {
        return array_values(array_filter(
            self::activeCodes(),
            fn (string $code) => $code !== self::default()
        ));
    }

    /** Exists in the catalogue at all (active or not). */
    public static function isSupported(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /** Exists AND is active. */
    public static function isActive(string $code): bool
    {
        return array_key_exists($code, self::active());
    }

    /** The locale for the current request (Laravel app locale). */
    public static function current(): string
    {
        return app()->getLocale();
    }

    /**
     * Locale derived straight from the URL's first segment — independent of the
     * SetLocale middleware, so it is safe to use during route-model binding
     * (SubstituteBindings runs before SetLocale). Falls back to the default.
     */
    public static function localeFromRequest(): string
    {
        $segment = Request::segment(1);

        return ($segment !== null && $segment !== self::default() && self::isActive($segment))
            ? $segment
            : self::default();
    }

    public static function label(string $code): ?string
    {
        return self::all()[$code]['label'] ?? null;
    }

    public static function native(string $code): ?string
    {
        return self::all()[$code]['native'] ?? null;
    }

    public static function flag(string $code): ?string
    {
        return self::all()[$code]['flag'] ?? null;
    }

    /**
     * Rewrite the current request path into the target locale.
     *
     * A2 (no translation groups yet) simply swaps the locale prefix on the
     * current path: strips any leading active non-default locale segment, then
     * prepends the target's prefix (default locale stays bare). B4+ upgrades the
     * switcher to point at the translation-group counterpart URL.
     */
    public static function localizedUrl(string $target, ?string $path = null): string
    {
        $path = $path ?? Request::path();               // '/', 'products', 'id/products'
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));

        // Strip an existing non-default locale prefix.
        if (isset($segments[0]) && $segments[0] !== self::default() && self::isSupported($segments[0])) {
            array_shift($segments);
        }

        // Prepend the target prefix (default locale is unprefixed).
        if ($target !== self::default()) {
            array_unshift($segments, $target);
        }

        return url('/'.implode('/', $segments));
    }
}
