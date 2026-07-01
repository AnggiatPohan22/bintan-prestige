<?php

namespace App\Support;

use App\Models\ContentEntry;
use App\Models\ContentEntryRelation;
use App\Models\Field;
use Illuminate\Database\Eloquent\Collection;

/**
 * Projects relationship-type field values from `content_entries.data` into the
 * normalised `content_entry_relations` table, enabling fast forward and reverse
 * relationship queries without scanning JSON.
 *
 * Mirrors ContentEntryIndexService (B6): a save-time projection cache, wired
 * through ContentEntryObserver.
 */
final class ContentEntryRelationService
{
    /**
     * Sync relation rows for a content entry.
     *
     * For each relationship-type field on the entry's content type:
     *   - Upsert one row per (valid, existing) target ID with its array position
     *     as sort_order.
     *   - Remove targets no longer present.
     * Then drop rows for field_keys that are no longer relationship fields.
     */
    public function sync(ContentEntry $entry): void
    {
        $entry->loadMissing('contentType');

        if ($entry->contentType === null) {
            return;
        }

        $fields     = $this->relationshipFields($entry->content_type_id);
        $syncedKeys = [];

        foreach ($fields as $field) {
            $syncedKeys[] = $field->key;

            $targetIds = $this->existingTargetIds(
                $this->normaliseTargetIds($entry->fieldValue($field->key)),
                $entry->id
            );

            // Remove targets that are no longer linked through this field.
            $stale = ContentEntryRelation::where('source_entry_id', $entry->id)
                ->where('field_key', $field->key);

            if ($targetIds !== []) {
                $stale->whereNotIn('target_entry_id', $targetIds);
            }

            $stale->delete();

            // Upsert each target with its position preserved as sort_order.
            foreach ($targetIds as $position => $targetId) {
                ContentEntryRelation::updateOrCreate(
                    [
                        'source_entry_id' => $entry->id,
                        'field_key'       => $field->key,
                        'target_entry_id' => $targetId,
                    ],
                    ['sort_order' => $position]
                );
            }
        }

        // Remove rows for field_keys that are no longer relationship fields.
        $query = ContentEntryRelation::where('source_entry_id', $entry->id);

        if ($syncedKeys !== []) {
            $query->whereNotIn('field_key', $syncedKeys);
        }

        $query->delete();
    }

    /**
     * Remove all outgoing relation rows for an entry (CASCADE handles hard delete;
     * this is for explicit de-projection).
     */
    public function remove(ContentEntry $entry): void
    {
        ContentEntryRelation::where('source_entry_id', $entry->id)->delete();
    }

    // ---------------------------------------------------------------- private helpers

    /**
     * Relationship-type fields belonging to a content type.
     *
     * @return Collection<int, Field>
     */
    private function relationshipFields(int $contentTypeId): Collection
    {
        return Field::query()
            ->whereHas('fieldGroup', fn ($q) => $q->where('content_type_id', $contentTypeId))
            ->where('type', 'relationship')
            ->select(['id', 'key', 'type'])
            ->get();
    }

    /**
     * Coerce a raw field value to a de-duplicated list of positive integer IDs.
     *
     * @return list<int>
     */
    private function normaliseTargetIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];

        foreach ($raw as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Keep only IDs that reference a real content entry (including soft-deleted),
     * excluding the source itself. Drops dangling/typo IDs so the target FK never
     * fails, and prevents an entry from relating to itself.
     *
     * @param  list<int> $ids
     * @return list<int>
     */
    private function existingTargetIds(array $ids, int $sourceId): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<int> $existing */
        $existing = ContentEntry::withTrashed()
            ->whereIn('id', $ids)
            ->where('id', '!=', $sourceId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // Preserve the original order from the data array.
        return array_values(array_filter($ids, fn (int $id): bool => in_array($id, $existing, true)));
    }
}
