<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'version',
        'author',
        'description',
        'is_active',
        'config',
        'installed_at',
        'activated_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'config'       => 'array',
        'installed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
