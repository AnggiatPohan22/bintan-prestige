<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_type_id',
        'label',
        'key',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'content_type_id' => 'integer',
        'sort_order'      => 'integer',
    ];

    /** @return BelongsTo<ContentType, $this> */
    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    /** @return HasMany<Field, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
