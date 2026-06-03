<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    protected $fillable = [
        'page_key',
        'section_key',
        'label',
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_url',
        'image',
        'mobile_image',
        'extra_data',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(PageSectionMedia::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeMedia(): HasMany
    {
        return $this->media()
            ->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->image);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->mobile_image);
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
