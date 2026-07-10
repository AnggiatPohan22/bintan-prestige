<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    use Translatable;

    /**
     * Phase 7 (B7) — menu structure is shared across locales; only the label is
     * translated per locale via the sidecar (A0 §3.5 Option A). URLs, targets,
     * children, and link resolution stay identical.
     *
     * @var list<string>
     */
    protected array $translatable = ['label'];

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

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return BelongsTo<MenuItem, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<MenuItem, $this> */
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
        $linkable = $this->linkable;

        return match ($this->link_type) {
            'page'        => $linkable instanceof Page ? route('pages.show', $linkable->slug, false) : '#',
            'product'     => $linkable instanceof Product ? route('products.show', $linkable->slug, false) : '#',
            'category'    => $linkable instanceof Category ? route('products.index', ['category' => [$linkable->id]], false) : '#',
            'destination' => $linkable instanceof Destination ? route('products.index', ['destination' => [$linkable->id]], false) : '#',
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

    // Locale-aware accessor (Phase 7 — B7): default locale short-circuits to base.
    public function getLabelAttribute(): ?string
    {
        return $this->translate('label');
    }
}
