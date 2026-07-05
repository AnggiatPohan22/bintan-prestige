<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property array<int, array{question: mixed, answer: mixed}> $resolvedFaqItems
 * @property \Illuminate\Database\Eloquent\Collection<int, Product> $resolvedProducts
 * @property \Illuminate\Database\Eloquent\Collection<int, ContentEntry> $resolvedEntries
 */
class PageBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'blockable_type',
        'blockable_id',
        'parent_block_id',
        'block_type',
        'label',
        'data',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'data'       => 'array',
        'parent_block_id' => 'integer',
        'blockable_id' => 'integer',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * The owning model — a Page (existing) or a ContentEntry (Phase 6, wired
     * at B9). Pages also keep page_id (dual-rail); this morph is the generic
     * accessor used when the owner type is not known ahead of time.
     *
     * @return MorphTo<Model, $this>
     */
    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<PageBlock, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_block_id');
    }

    /** @return HasMany<PageBlock, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_block_id')->orderBy('sort_order');
    }

    public function isContainer(): bool
    {
        return in_array($this->block_type, ['group', 'columns'], true);
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
