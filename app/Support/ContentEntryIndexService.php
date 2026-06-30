<?php

namespace App\Support;

use App\Models\ContentEntry;
use App\Models\ContentEntryIndex;
use App\Models\Field;
use Illuminate\Database\Eloquent\Collection;

final class ContentEntryIndexService
{
    /**
     * Sync the sidecar index rows for a content entry.
     *
     * For every filterable field belonging to the entry's content type:
     *   - Upsert an index row with the projected value.
     *   - Remove rows whose field_key is no longer filterable or whose
     *     current value is null/empty.
     */
    public function sync(ContentEntry $entry): void
    {
        $entry->loadMissing('contentType');
        $contentType = $entry->contentType;

        if ($contentType === null) {
            return;
        }

        $fields  = $this->filterableFields($entry->content_type_id);
        $indexed = [];

        foreach ($fields as $field) {
            $raw = $entry->fieldValue($field->key);
            $row = $this->projectValue($field->type, $raw);

            if ($row !== null) {
                ContentEntryIndex::updateOrCreate(
                    ['content_entry_id' => $entry->id, 'field_key' => $field->key],
                    $row
                );
                $indexed[] = $field->key;
            }
        }

        // Remove stale rows: fields no longer filterable, or whose value is now null.
        $query = ContentEntryIndex::where('content_entry_id', $entry->id);

        if (! empty($indexed)) {
            $query->whereNotIn('field_key', $indexed);
        }

        $query->delete();
    }

    /**
     * Remove all index rows for an entry (used when force-deleting without CASCADE,
     * or when an entry is explicitly de-indexed).
     */
    public function remove(ContentEntry $entry): void
    {
        ContentEntryIndex::where('content_entry_id', $entry->id)->delete();
    }

    // ---------------------------------------------------------------- private helpers

    /**
     * Load all filterable fields that belong to a content type.
     *
     * @return Collection<int, Field>
     */
    private function filterableFields(int $contentTypeId): Collection
    {
        return Field::query()
            ->whereHas('fieldGroup', fn ($q) => $q->where('content_type_id', $contentTypeId))
            ->where('is_filterable', true)
            ->select(['id', 'key', 'type'])
            ->get();
    }

    /**
     * Map a raw field value to the appropriate value column.
     * Returns null when the value is empty (no index row should be written).
     *
     * @return array{value_string: string|null, value_number: float|null, value_date: string|null}|null
     */
    private function projectValue(string $type, mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw) && empty($raw)) {
            return null;
        }

        $typeDef = FieldTypeRegistry::get($type);
        $cast    = $typeDef !== null ? (string) ($typeDef['cast'] ?? 'string') : 'string';

        return match ($cast) {

            'float', 'integer' => [
                'value_string' => null,
                'value_number' => is_numeric($raw) ? (float) $raw : null,
                'value_date'   => null,
            ],

            'date', 'datetime' => [
                'value_string' => null,
                'value_number' => null,
                'value_date'   => $this->toDateString($raw),
            ],

            'boolean' => [
                'value_string' => ((bool) $raw) ? '1' : '0',
                'value_number' => null,
                'value_date'   => null,
            ],

            'array' => [
                // Relationship fields: store CSV of entry IDs for simple in-list filtering.
                'value_string' => is_array($raw)
                    ? implode(',', array_filter(array_map('strval', $raw), fn (string $v): bool => $v !== ''))
                    : (is_scalar($raw) ? (string) $raw : null),
                'value_number' => null,
                'value_date'   => null,
            ],

            default => [  // 'string' and any unknown cast
                'value_string' => is_scalar($raw) ? (string) $raw : null,
                'value_number' => null,
                'value_date'   => null,
            ],
        };
    }

    private function toDateString(mixed $raw): ?string
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return (new \DateTime($raw))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
