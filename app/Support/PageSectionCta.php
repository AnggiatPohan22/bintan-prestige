<?php

namespace App\Support;

use Illuminate\Support\Str;

class PageSectionCta
{
    public static function safeUrl(?string $url, ?string $fallback = null): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || $url === '#') {
            if ($fallback === null) {
                return null;
            }

            return self::safeUrl($fallback);
        }

        $lowerUrl = Str::lower($url);

        if (Str::startsWith($lowerUrl, ['javascript:', 'data:', 'vbscript:'])) {
            return self::safeUrl($fallback);
        }

        if (Str::startsWith($lowerUrl, ['/', 'http://', 'https://', 'mailto:', 'tel:'])) {
            return $url;
        }

        return self::safeUrl($fallback);
    }

    public static function hasButton(?string $text, ?string $url): bool
    {
        return filled($text) && filled(self::safeUrl($url));
    }
}
