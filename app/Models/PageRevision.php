<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'revision_number',
        'content_snapshot',
        'meta_snapshot',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'content_snapshot' => 'array',
        'meta_snapshot'    => 'array',
        'created_at'       => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
