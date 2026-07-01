<?php

namespace App\Observers;

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
}
