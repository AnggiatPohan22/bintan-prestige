<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteAsset extends Model
{
    protected $fillable = [
        'key',
        'label',
        'path',
        'alt',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(\App\Services\GlobalSettingsService::class)->forgetAssetsCache());
        static::deleted(fn () => app(\App\Services\GlobalSettingsService::class)->forgetAssetsCache());
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        return asset('storage/' . $this->path);
    }
}
