<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, array{question: mixed, answer: mixed}> $resolvedFaqItems
 * @property \Illuminate\Database\Eloquent\Collection<int, Product> $resolvedProducts
 */
class PageBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'block_type',
        'label',
        'data',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
