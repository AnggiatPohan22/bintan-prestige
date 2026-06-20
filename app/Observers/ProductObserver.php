<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        AuditLog::record('created', $product, null, $product->getAttributes());
    }

    public function updated(Product $product): void
    {
        $changed = array_keys($product->getChanges());
        $old = array_intersect_key($product->getOriginal(), array_flip($changed));
        $new = $product->getChanges();

        AuditLog::record('updated', $product, $old, $new);
    }

    public function deleted(Product $product): void
    {
        AuditLog::record('deleted', $product, $product->getAttributes(), null);
    }
}
