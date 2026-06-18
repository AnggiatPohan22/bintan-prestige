<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    public const LINK_TYPES = [
        'page',
        'product',
        'category',
        'destination',
        'url',
        'anchor',
    ];

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'link_type',
        'linkable_type',
        'linkable_id',
        'url',
        'target',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Resolve the final URL for this item.
     *
     * Internal targets use relative URLs (absolute: false) so the existing
     * NavigationSettings::isActiveUrl() highlighting keeps working.
     */
    public function resolveUrl(): string
    {
        return match ($this->link_type) {
            'page'        => $this->linkable ? route('pages.show', $this->linkable->slug, false) : '#',
            'product'     => $this->linkable ? route('products.show', $this->linkable->slug, false) : '#',
            'category'    => $this->linkable ? route('products.index', ['category' => [$this->linkable->id]], false) : '#',
            'destination' => $this->linkable ? route('products.index', ['destination' => [$this->linkable->id]], false) : '#',
            'anchor', 'url' => $this->url ?: '#',
            default       => '#',
        };
    }

    public function isExternal(): bool
    {
        if ($this->target === '_blank') {
            return true;
        }

        return $this->link_type === 'url'
            && (str_starts_with((string) $this->url, 'http://') || str_starts_with((string) $this->url, 'https://'));
    }
}
