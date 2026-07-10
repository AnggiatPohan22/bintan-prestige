<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use Translatable;

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

    /**
     * The `value` holds the default-locale text; non-default locales resolve via
     * the translations sidecar. Phase 7 (B1) attaches the trait here as its first
     * consumer; the per-locale admin UI + frontend reads are wired in B2.
     *
     * @var list<string>
     */
    protected array $translatable = ['value'];

    protected static function booted(): void
    {
        static::saved(fn () => app(\App\Services\GlobalSettingsService::class)->forgetSettingsCache());
        static::deleted(fn () => app(\App\Services\GlobalSettingsService::class)->forgetSettingsCache());
    }
}
