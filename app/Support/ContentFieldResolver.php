<?php

namespace App\Support;

use App\Models\ContentEntry;
use App\Models\Field;

/**
 * Resolves a `content_field` builder block's data into a display-ready field
 * value from the current entry (on entry bodies) or a specific entry (by ID).
 *
 * Shared by PageRenderData (pages — specific entry only) and the entry frontend
 * controller (entry bodies — current or specific entry). All lookups live here,
 * never in Blade; the render partial only echoes the resolved, escaped value.
 */
final class ContentFieldResolver
{
    /**
     * @param  array<string, mixed>  $data           the block's data bag
     * @param  ContentEntry|null      $currentEntry   entry whose body is rendering, if any
     * @return array{label: string, type: string, text: string, html: string, href: string, show_label: bool}|null
     */
    public function resolve(array $data, ?ContentEntry $currentEntry = null): ?array
    {
        $key = trim((string) ($data['field_key'] ?? ''));

        if ($key === '') {
            return null;
        }

        $entry = $this->targetEntry($data, $currentEntry);

        if ($entry === null) {
            return null;
        }

        $raw = $entry->fieldValue($key);

        if ($raw === null || $raw === '' || (is_array($raw) && $raw === [])) {
            return null;
        }

        // A data key can exist without a matching Field definition (orphaned key),
        // so the lookup is genuinely nullable. optional() keeps it null-safe.
        $field = $this->findField($entry->content_type_id, $key);

        $type = optional($field)->type ?? 'text';

        [$text, $html, $href] = $this->format($type, $raw);

        if ($text === '' && $html === '') {
            return null;
        }

        return [
            'label'      => trim((string) ($data['label'] ?? '')) ?: (optional($field)->label ?? $key),
            'type'       => $type,
            'text'       => $text,
            'html'       => $html,
            'href'       => $href,
            'show_label' => (bool) ($data['show_label'] ?? true),
        ];
    }

    /** Look up a field definition by key within a content type (nullable). */
    private function findField(int $contentTypeId, string $key): ?Field
    {
        return Field::query()
            ->whereHas('fieldGroup', fn ($q) => $q->where('content_type_id', $contentTypeId))
            ->where('key', $key)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function targetEntry(array $data, ?ContentEntry $currentEntry): ?ContentEntry
    {
        $id = ($data['entry_id'] ?? null) ?: null;

        if ($id !== null && is_numeric($id)) {
            return ContentEntry::query()
                ->published()
                ->whereHas('contentType', fn ($q) => $q->where('is_public', true)->where('is_active', true))
                ->find((int) $id);
        }

        return $currentEntry;
    }

    /**
     * Produce [displayText, sanitizedHtml, safeHref].
     * - richtext → html (sanitized), text/href empty.
     * - url/email → text = value, href = validated safe link (empty if unsafe →
     *   renders as plain text, never a javascript: link).
     * - everything else → text only.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function format(string $type, mixed $raw): array
    {
        return match ($type) {
            'toggle' => [((bool) $raw) ? 'Yes' : 'No', '', ''],
            'richtext' => ['', InlineContentSanitizer::richtext(is_scalar($raw) ? (string) $raw : ''), ''],
            'url' => $this->urlValue($raw),
            'email' => $this->emailValue($raw),
            'checkbox', 'gallery', 'relationship' => [
                is_array($raw)
                    ? implode(', ', array_filter(array_map('strval', $raw), fn (string $v): bool => $v !== ''))
                    : (is_scalar($raw) ? (string) $raw : ''),
                '',
                '',
            ],
            default => [is_scalar($raw) ? (string) $raw : '', '', ''],
        };
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function urlValue(mixed $raw): array
    {
        $value = is_scalar($raw) ? (string) $raw : '';
        // Only http(s) becomes a link; anything else renders as plain text.
        $href = preg_match('#^https?://#i', $value) === 1 ? $value : '';

        return [$value, '', $href];
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function emailValue(mixed $raw): array
    {
        $value = is_scalar($raw) ? (string) $raw : '';
        $href  = filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:'.$value : '';

        return [$value, '', $href];
    }
}
