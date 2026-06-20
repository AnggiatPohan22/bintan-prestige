<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Theme;

class ThemeObserver
{
    public function created(Theme $theme): void
    {
        AuditLog::record('created', $theme, null, $theme->getAttributes());
    }

    public function updated(Theme $theme): void
    {
        $changed = array_keys($theme->getChanges());
        $old = array_intersect_key($theme->getOriginal(), array_flip($changed));
        $new = $theme->getChanges();

        AuditLog::record('updated', $theme, $old, $new);
    }

    public function deleted(Theme $theme): void
    {
        AuditLog::record('deleted', $theme, $theme->getAttributes(), null);
    }
}
