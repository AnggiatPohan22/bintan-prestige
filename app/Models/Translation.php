<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single translated attribute value (Phase 7 — B1).
 *
 * Polymorphic sidecar row: "on {translatable}, field {field} in locale {locale}
 * is {value}". The default-locale value is NOT stored here — it lives in the
 * owner's base column. See the Translatable trait for resolution + fallback.
 */
class Translation extends Model
{
    protected $table = 'translations';

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];

    /** @return MorphTo<Model, $this> */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
