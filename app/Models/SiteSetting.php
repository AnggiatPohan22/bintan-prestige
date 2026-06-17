<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'value',
        'type',
        'group',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(\App\Services\GlobalSettingsService::class)->forgetSettingsCache());
        static::deleted(fn () => app(\App\Services\GlobalSettingsService::class)->forgetSettingsCache());
    }
}
