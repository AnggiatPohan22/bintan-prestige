<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Normalised projection of a relationship-type field value.
 *
 * Source of truth stays in `content_entries.data` JSON; these rows are kept in
 * sync by ContentEntryRelationService on every entry save. They exist purely to
 * make forward/reverse relationship queries indexable without scanning JSON.
 */
class ContentEntryRelation extends Model
{
    public $timestamps = false;

    protected $table = 'content_entry_relations';

    protected $fillable = [
        'source_entry_id',
        'target_entry_id',
        'field_key',
        'sort_order',
    ];

    protected $casts = [
        'source_entry_id' => 'integer',
        'target_entry_id' => 'integer',
        'sort_order'      => 'integer',
    ];

    /** @return BelongsTo<ContentEntry, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'source_entry_id');
    }

    /** @return BelongsTo<ContentEntry, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'target_entry_id');
    }
}
