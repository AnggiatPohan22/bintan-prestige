<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    protected $fillable = [
        'page_id',
        'visitor_hash',
        'referrer',
        'country_code',
        'viewed_date',
    ];

    // viewed_date stored as plain 'Y-m-d' string for SQLite/MySQL compatibility.
    protected $casts = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
