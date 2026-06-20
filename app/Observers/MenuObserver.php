<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Menu;

class MenuObserver
{
    public function created(Menu $menu): void
    {
        AuditLog::record('created', $menu, null, $menu->getAttributes());
    }

    public function updated(Menu $menu): void
    {
        $changed = array_keys($menu->getChanges());
        $old = array_intersect_key($menu->getOriginal(), array_flip($changed));
        $new = $menu->getChanges();

        AuditLog::record('updated', $menu, $old, $new);
    }

    public function deleted(Menu $menu): void
    {
        AuditLog::record('deleted', $menu, $menu->getAttributes(), null);
    }
}
