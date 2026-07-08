<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentType extends Model
{
    use HasFactory, SoftDeletes;

    /** Valid feature-support flags (config-level constant, not queried). */
    public const SUPPORTS = ['title', 'slug', 'editor', 'excerpt', 'featured_image', 'revisions', 'scheduling', 'seo'];

    /** Reserved slugs / route_base prefixes — must never collide with existing routes. */
    public const RESERVED_PREFIXES = [
        'admin', 'api', 'login', 'logout', 'register', 'password',
        'pages', 'products', 'preview', 'media',
        'sitemap', 'sitemap.xml', '_debugbar',
    ];

    protected $fillable = [
        'slug',
        'label_singular',
        'label_plural',
        'icon',
        'description',
        'is_public',
        'has_archive',
        'route_base',
        'supports',
        'menu_position',
        'is_active',
    ];

    protected $casts = [
        'supports'      => 'array',
        'is_public'     => 'boolean',
        'has_archive'   => 'boolean',
        'is_active'     => 'boolean',
        'menu_position' => 'integer',
    ];

    /** @return HasMany<ContentEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ContentEntry::class);
    }

    /** @return HasMany<FieldGroup, $this> */
    public function fieldGroups(): HasMany
    {
        return $this->hasMany(FieldGroup::class)->orderBy('sort_order');
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supports ?? [], true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('menu_position')->orderBy('label_plural');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->where('is_active', true);
    }
}
