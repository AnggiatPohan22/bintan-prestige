<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'directory',
        'version',
        'author',
        'description',
        'screenshot',
        'is_active',
        'customization',
        'sort_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'customization' => 'array',
        'sort_order'    => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active themes first, then alphabetical. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_active')->orderBy('sort_order')->orderBy('name');
    }

    /** Absolute path to the theme's root directory on disk. */
    public function basePath(): string
    {
        return base_path($this->directory);
    }

    /** Absolute path to the theme's manifest file. */
    public function manifestPath(): string
    {
        return $this->basePath() . '/theme.json';
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Read the widget_areas declaration from theme.json.
     * Returns [] if the manifest is missing or has no widget_areas section.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function widgetAreas(): array
    {
        if (! file_exists($this->manifestPath())) {
            return [];
        }

        try {
            $data = json_decode(
                file_get_contents($this->manifestPath()),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            return $data['widget_areas'] ?? [];
        } catch (\JsonException) {
            return [];
        }
    }

    /** True if a screenshot file is declared and exists on disk. */
    public function hasScreenshot(): bool
    {
        return filled($this->screenshot)
            && file_exists($this->basePath() . '/' . $this->screenshot);
    }

    /**
     * Read the customization_schema from this theme's theme.json.
     * Returns [] if the manifest is missing or has no schema section.
     *
     * @return array<string, array{label: string, tokens: array<int, array{key: string, label: string, type: string, default: string}>}>
     */
    public function customizationSchema(): array
    {
        if (! file_exists($this->manifestPath())) {
            return [];
        }

        try {
            $data = json_decode(
                file_get_contents($this->manifestPath()),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            return $data['customization_schema'] ?? [];
        } catch (\JsonException) {
            return [];
        }
    }
}
