<?php

namespace App\Observers;

use App\Models\ContentEntry;
use App\Support\ContentEntryIndexService;

class ContentEntryObserver
{
    public function __construct(private readonly ContentEntryIndexService $service) {}

    /** Re-project filterable fields into the sidecar index on every save. */
    public function saved(ContentEntry $entry): void
    {
        $this->service->sync($entry);
    }

    /** Re-project on restore so the index reflects the current data. */
    public function restored(ContentEntry $entry): void
    {
        $this->service->sync($entry);
    }
}
