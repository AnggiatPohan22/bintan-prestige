<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderPattern extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'thumbnail',
        'pattern_data',
        'block_type',
        'is_global',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'pattern_data' => 'array',
        'is_global' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
