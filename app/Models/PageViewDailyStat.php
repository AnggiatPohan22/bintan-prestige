<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageViewDailyStat extends Model
{
    protected $fillable = [
        'page_id',
        'stat_date',
        'view_count',
        'unique_visitors',
    ];

    // stat_date stored as plain 'Y-m-d' string for SQLite/MySQL compatibility.
    protected $casts = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
