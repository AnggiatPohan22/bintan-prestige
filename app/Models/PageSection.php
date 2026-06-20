<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    public const MEDIA_LIMIT = 10;

    public const ANIMATION_OPTIONS = [
        'ken-burns',
        'zoom-in',
        'zoom-out',
        'fade',
        'pan-left',
        'pan-right',
        'none',
    ];

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

    /** @return HasMany<PageSectionMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(PageSectionMedia::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return HasMany<PageSectionMedia, $this> */
    public function activeMedia(): HasMany
    {
        return $this->media()->where('is_active', true);
    }

    public function mediaSlot(string $role, string $slotKey): ?PageSectionMedia
    {
        return $this->media
            ->first(fn (PageSectionMedia $media) => $media->role === $role && $media->slot_key === $slotKey);
    }

    public function mediaUrl(string $role, string $slotKey): ?string
    {
        return $this->mediaSlot($role, $slotKey)?->url;
    }

    public function galleryMedia()
    {
        return $this->media
            ->filter(fn (PageSectionMedia $media) => $media->role === 'gallery' && $media->is_active)
            ->values();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->image);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->resolveImageUrl($this->mobile_image);
    }

    public function getAnimationAttribute(): string
    {
        $animation = $this->extra_data['animation'] ?? 'ken-burns';

        return in_array($animation, self::ANIMATION_OPTIONS, true)
            ? $animation
            : 'ken-burns';
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
