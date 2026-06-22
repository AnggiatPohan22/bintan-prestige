<?php

namespace App\Support;

final class InlineContentSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'a',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'span', 'blockquote',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'textarea', 'select', 'button', 'link', 'meta', 'base', 'svg', 'math',
    ];

    public static function plaintext(string $value): string
    {
        return trim(strip_tags($value));
    }

    public static function richtext(string $html): string
    {
        $html = preg_replace('/<!--[\s\S]*?-->/', '', $html) ?? '';
        $dangerous = implode('|', self::DROP_WITH_CONTENT);

        // Remove executable/embedded elements together with their contents. Repeat
        // so nested instances cannot expose a second dangerous opening tag.
        do {
            $previous = $html;
            $html = preg_replace(
                '/<\s*('.$dangerous.')\b[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/i',
                '',
                $html
            ) ?? '';
        } while ($html !== $previous);

        $html = preg_replace('/<\s*\/?\s*('.$dangerous.')\b[^>]*>/i', '', $html) ?? '';
        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $html = strip_tags($html, $allowed);

        return preg_replace_callback(
            '/<\s*(\/?)\s*([a-z0-9]+)\b([^>]*)>/i',
            static function (array $match): string {
                $closing = $match[1] === '/';
                $tag = strtolower($match[2]);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }

                if ($closing) {
                    return $tag === 'br' ? '' : "</{$tag}>";
                }

                $safe = self::safeAttributes($tag, $match[3]);

                return '<'.$tag.$safe.'>';
            },
            $html
        ) ?? '';
    }

    private static function safeAttributes(string $tag, string $raw): string
    {
        preg_match_all(
            '/\b([a-z][a-z0-9:_-]*)\s*=\s*(?:(["\'])(.*?)\2|([^\s"\'=<>`]+))/is',
            $raw,
            $matches,
            PREG_SET_ORDER
        );

        $safe = [];
        foreach ($matches as $attribute) {
            $name = strtolower($attribute[1]);
            $value = html_entity_decode($attribute[3] !== '' ? $attribute[3] : ($attribute[4] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($name === 'class') {
                $classes = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $classes = array_values(array_filter($classes, static fn (string $class): bool => (bool) preg_match('/^[A-Za-z0-9_-]+$/', $class)));
                if ($classes !== []) {
                    $safe['class'] = implode(' ', array_unique($classes));
                }
            }

            if ($tag === 'a' && $name === 'href' && self::isHttpUrl($value)) {
                $safe['href'] = $value;
            }

            if ($tag === 'a' && $name === 'target' && in_array($value, ['_blank', '_self'], true)) {
                $safe['target'] = $value;
            }
        }

        if (($safe['target'] ?? null) === '_blank') {
            $safe['rel'] = 'noopener noreferrer';
        }

        $serialized = '';
        foreach ($safe as $name => $value) {
            $serialized .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return $serialized;
    }

    private static function isHttpUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
