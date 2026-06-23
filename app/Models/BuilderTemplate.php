<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderTemplate extends Model
{
    public const TYPE_PAGE = 'page';

    public const SCHEMA_VERSION = 1;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'template_type',
        'schema_version',
        'base_template_id',
        'thumbnail',
        'template_data',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'template_data' => 'array',
        'schema_version' => 'integer',
        'base_template_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<PageTemplate, $this> */
    public function baseTemplate(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'base_template_id');
    }

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
