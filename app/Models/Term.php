<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Term extends Model
{
    use SoftDeletes;
    use Translatable;

    /** @var list<string> Phase 7 (B7) — sidecar-translated taxonomy copy. */
    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'taxonomy_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'taxonomy_id' => 'integer',
        'parent_id'   => 'integer',
        'sort_order'  => 'integer',
    ];

    /** @return BelongsTo<Taxonomy, $this> */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }

    /** @return BelongsTo<Term, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    /** @return HasMany<Term, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Term::class, 'parent_id');
    }

    /** @return BelongsToMany<ContentEntry, $this> */
    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(ContentEntry::class, 'content_entry_term');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getNameAttribute(): ?string        { return $this->translate('name'); }
    public function getDescriptionAttribute(): ?string { return $this->translate('description'); }
}
