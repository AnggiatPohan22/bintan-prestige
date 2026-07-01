<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Taxonomy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'label_singular',
        'label_plural',
        'description',
        'is_hierarchical',
        'content_type_ids',
        'sort_order',
    ];

    protected $casts = [
        'is_hierarchical'  => 'boolean',
        'content_type_ids' => 'array',
        'sort_order'       => 'integer',
    ];

    /** @return HasMany<Term, $this> */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /** Root-level terms only (no parent). */
    /** @return HasMany<Term, $this> */
    public function rootTerms(): HasMany
    {
        return $this->hasMany(Term::class)->whereNull('parent_id');
    }

    /** Returns true when this taxonomy applies to the given content type. */
    public function appliesToType(int $contentTypeId): bool
    {
        $ids = $this->content_type_ids;

        return $ids === null || in_array($contentTypeId, $ids, true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label_singular');
    }
}
