<?php

namespace App\Support;

use App\Models\ContentEntry;
use App\Models\ContentEntryRevision;
use Illuminate\Support\Facades\Auth;

/**
 * Creates and restores content-entry revisions.
 *
 * Mirrors PageService::saveRevision but writes to the isolated
 * content_entry_revisions table. Keeps the newest N revisions per entry.
 */
final class ContentEntryRevisionService
{
    /** Maximum revisions retained per entry. */
    private const KEEP = 20;

    /**
     * Attributes captured in a snapshot. Kept explicit so nothing sensitive or
     * volatile (timestamps, author_id) leaks into revision history.
     *
     * @var list<string>
     */
    private const REVISIONABLE = [
        'title', 'slug', 'excerpt', 'status', 'published_at',
        'template', 'sort_order', 'data', 'seo',
    ];

    /**
     * Store the entry's current state as a new revision, then prune old ones.
     * No-op for unauthenticated context with no explicit author (e.g. console).
     */
    public function snapshot(ContentEntry $entry, ?int $authorId = null): void
    {
        if (! Auth::check() && $authorId === null) {
            return;
        }

        $lastNumber = (int) ($entry->revisions()->max('revision_number') ?? 0);

        ContentEntryRevision::create([
            'content_entry_id' => $entry->id,
            'revision_number'  => $lastNumber + 1,
            'snapshot'         => $this->buildSnapshot($entry),
            'created_by'       => $authorId ?? Auth::id(),
            'created_at'       => now(),
        ]);

        // Prune oldest beyond the retention limit.
        $keepIds = $entry->revisions()
            ->orderByDesc('revision_number')
            ->limit(self::KEEP)
            ->pluck('id');

        $entry->revisions()->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * Restore an entry to a past revision. The current state is snapshotted
     * first, so a restore is itself reversible.
     */
    public function restore(ContentEntry $entry, ContentEntryRevision $revision): void
    {
        // Checkpoint the present before overwriting it.
        $this->snapshot($entry);

        /** @var array<string, mixed> $snap */
        $snap    = $revision->snapshot;
        $payload = [];

        foreach (self::REVISIONABLE as $key) {
            // Only apply keys the snapshot actually carried, so a snapshot taken
            // before a future column existed simply leaves that column untouched.
            if (array_key_exists($key, $snap)) {
                $payload[$key] = $snap[$key];
            }
        }

        $entry->update($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(ContentEntry $entry): array
    {
        // Read from a fresh instance so every column is loaded with its real
        // persisted value. Model::only() fabricates null for attributes not held
        // in memory (e.g. a DB-default sort_order on a just-created model), which
        // would otherwise write null back into a NOT-NULL column on restore.
        $source = $entry->fresh() ?? $entry;

        return $source->only(self::REVISIONABLE);
    }
}
