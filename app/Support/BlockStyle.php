<?php

namespace App\Support;

/**
 * Builds the Advanced-tab presentation attributes (margin, padding, z-index,
 * CSS id/classes, responsive visibility, custom CSS) for Group / Columns blocks.
 *
 * Everything here is defensive: values are re-sanitized at render time even
 * though PageBlockService already sanitizes on save. Empty/absent keys produce
 * no output, so existing blocks are completely unaffected.
 */
class BlockStyle
{
    /**
     * @param  array<string, mixed>  $data  the block's data array
     * @return array{id: ?string, classes: string, style: string, css: string}
     */
    public static function advanced(array $data, int|string|null $fallbackId = null): array
    {
        return [
            'id' => self::cssId($data['css_id'] ?? null),
            'classes' => trim(self::responsiveClasses($data).' '.self::cssClasses($data['css_classes'] ?? null)),
            'style' => self::boxStyle('margin', $data['margin'] ?? null).self::boxStyle('padding', $data['padding'] ?? null).self::zIndex($data['z_index'] ?? null),
            'css' => self::customCss($data['custom_css'] ?? null),
        ];
    }

    public static function cssId(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $clean = preg_replace('/[^A-Za-z0-9_-]/', '', $value) ?? '';

        return preg_match('/^[A-Za-z]/', $clean) === 1 ? $clean : null;
    }

    public static function cssClasses(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        return collect(preg_split('/\s+/', trim($value)) ?: [])
            ->map(static fn (string $c): string => preg_replace('/[^A-Za-z0-9_-]/', '', $c) ?? '')
            ->filter()
            ->take(20)
            ->implode(' ');
    }

    /** Strip anything that could break out of a <style> block. CSS needs no <, >. */
    public static function customCss(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        return trim(mb_substr(str_replace(['<', '>'], '', $value), 0, 5000));
    }

    /**
     * @param  array{top?: mixed, right?: mixed, bottom?: mixed, left?: mixed}|mixed  $box
     */
    private static function boxStyle(string $prop, mixed $box): string
    {
        if (! is_array($box)) {
            return '';
        }
        $sides = ['top', 'right', 'bottom', 'left'];
        $values = [];
        $hasAny = false;
        foreach ($sides as $side) {
            $raw = $box[$side] ?? null;
            if ($raw === '' || $raw === null || ! is_numeric($raw)) {
                $values[] = '0';

                continue;
            }
            $hasAny = true;
            $values[] = (string) ((int) $raw).'px';
        }

        return $hasAny ? $prop.':'.implode(' ', $values).';' : '';
    }

    private static function zIndex(mixed $value): string
    {
        if ($value === '' || $value === null || ! is_numeric($value)) {
            return '';
        }

        return 'position:relative;z-index:'.((int) $value).';';
    }

    /**
     * Minimal Tailwind classes (min-breakpoint only, v3.1-safe) that hide the
     * block on the chosen devices. mobile <md, tablet md..lg, desktop >=lg.
     *
     * @param  array<string, mixed>  $data
     */
    private static function responsiveClasses(array $data): string
    {
        $m = ! filter_var($data['hide_mobile'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $t = ! filter_var($data['hide_tablet'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $d = ! filter_var($data['hide_desktop'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $classes = [];
        $classes[] = $m ? '' : 'hidden';            // mobile (base)
        if ($t !== $m) {                            // tablet (md)
            $classes[] = $t ? 'md:block' : 'md:hidden';
        }
        if ($d !== $t) {                            // desktop (lg)
            $classes[] = $d ? 'lg:block' : 'lg:hidden';
        }

        return trim(implode(' ', array_filter($classes)));
    }
}
