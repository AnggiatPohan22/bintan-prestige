<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ContentEntry;
use App\Support\ContentEntryIndexService;
use App\Support\ContentEntryRelationService;

class ContentEntryObserver
{
    public function __construct(
        private readonly ContentEntryIndexService $indexService,
        private readonly ContentEntryRelationService $relationService,
    ) {}

    /** Re-project filterable fields + relationships on every save. */
    public function saved(ContentEntry $entry): void
    {
        $this->indexService->sync($entry);
        $this->relationService->sync($entry);
    }

    /** Re-project on restore so index + relations reflect the current data. */
    public function restored(ContentEntry $entry): void
    {
        $this->indexService->sync($entry);
        $this->relationService->sync($entry);
    }

    // ------------------------------------------------------------ audit log (Phase 4 reuse)

    public function created(ContentEntry $entry): void
    {
        AuditLog::record('created', $entry, null, $entry->getAttributes());
    }

    public function updated(ContentEntry $entry): void
    {
        $changed = array_keys($entry->getChanges());
        $old     = array_intersect_key($entry->getOriginal(), array_flip($changed));

        AuditLog::record('updated', $entry, $old, $entry->getChanges());
    }

    public function deleted(ContentEntry $entry): void
    {
        AuditLog::record('deleted', $entry, $entry->getAttributes(), null);
    }
}
