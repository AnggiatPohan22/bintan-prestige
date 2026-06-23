<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property 'draft'|'published'|'scheduled' $status */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'template_id',
        'status',
        'publish_at',
        'meta_title',
        'meta_description',
        'og_image',
        'seo_robots',
        'sort_order',
    ];

    protected $casts = [
        'sort_order'  => 'integer',
        'template_id' => 'integer',
        'publish_at'  => 'datetime',
    ];

    /** @return HasMany<PageBlock, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    /** @return HasMany<PageBlock, $this> */
    public function rootBlocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->whereNull('parent_block_id')->orderBy('sort_order');
    }

    /** @return HasMany<PageRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('revision_number');
    }

    /** @return BelongsTo<PageTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }
}
