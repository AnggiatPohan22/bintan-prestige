<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Point-in-time snapshot of a content entry's revisionable state.
 *
 * Isolated to content_entries via `content_entry_id` — deliberately NOT sharing
 * page_revisions, so entry structure can evolve without affecting page logic.
 */
class ContentEntryRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'content_entry_id',
        'revision_number',
        'snapshot',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'snapshot'         => 'array',
        'created_at'       => 'datetime',
        'content_entry_id' => 'integer',
        'revision_number'  => 'integer',
    ];

    /** @return BelongsTo<ContentEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'content_entry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
