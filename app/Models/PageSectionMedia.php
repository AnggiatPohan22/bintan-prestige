<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSectionMedia extends Model
{
    public const OBJECT_FIT_OPTIONS = [
        'cover' => 'Cover',
        'contain' => 'Contain',
        'fill' => 'Fill',
        'scale-down' => 'Scale down',
        'none' => 'None',
    ];

    public const OBJECT_POSITION_OPTIONS = [
        'center center' => 'Center center',
        'center top' => 'Center top',
        'center bottom' => 'Center bottom',
        'left center' => 'Left center',
        'left top' => 'Left top',
        'left bottom' => 'Left bottom',
        'right center' => 'Right center',
        'right top' => 'Right top',
        'right bottom' => 'Right bottom',
    ];

    protected $fillable = [
        'page_section_id',
        'role',
        'slot_key',
        'label',
        'path',
        'alt',
        'object_fit',
        'object_position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pageSection(): BelongsTo
    {
        return $this->belongsTo(PageSection::class);
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

    public function getResolvedObjectFitAttribute(): string
    {
        return array_key_exists($this->object_fit ?? '', self::OBJECT_FIT_OPTIONS)
            ? $this->object_fit
            : 'cover';
    }

    public function getResolvedObjectPositionAttribute(): string
    {
        return array_key_exists($this->object_position ?? '', self::OBJECT_POSITION_OPTIONS)
            ? $this->object_position
            : 'center center';
    }

    public function getImageStyleAttribute(): string
    {
        return 'object-fit: ' . $this->resolved_object_fit . '; object-position: ' . $this->resolved_object_position;
    }
}
