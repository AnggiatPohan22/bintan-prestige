<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Destination extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Translatable;

    /** @var list<string> Phase 7 (B6) — sidecar-translated copy. */
    protected array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getNameAttribute(): ?string        { return $this->translate('name'); }
    public function getDescriptionAttribute(): ?string { return $this->translate('description'); }
}
