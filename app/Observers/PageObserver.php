<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Page;

class PageObserver
{
    public function created(Page $page): void
    {
        AuditLog::record('created', $page, null, $page->getAttributes());
    }

    public function updated(Page $page): void
    {
        $changed = array_keys($page->getChanges());
        $old = array_intersect_key($page->getOriginal(), array_flip($changed));
        $new = $page->getChanges();

        AuditLog::record('updated', $page, $old, $new);
    }

    public function deleted(Page $page): void
    {
        AuditLog::record('deleted', $page, $page->getAttributes(), null);
    }
}
