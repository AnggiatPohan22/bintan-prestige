<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentEntryIndex extends Model
{
    public $timestamps = false;

    protected $table = 'content_entry_index';

    protected $fillable = [
        'content_entry_id',
        'field_key',
        'value_string',
        'value_number',
        'value_date',
    ];

    protected $casts = [
        'content_entry_id' => 'integer',
        'value_number'     => 'float',
        'value_date'       => 'date',
    ];

    /** @return BelongsTo<ContentEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ContentEntry::class, 'content_entry_id');
    }
}
