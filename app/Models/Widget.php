<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    public const TYPES = ['text', 'html', 'image', 'navigation'];

    protected $fillable = [
        'theme_id',
        'area',
        'widget_type',
        'title',
        'data',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeForArea(Builder $query, string $area): Builder
    {
        return $query->where('area', $area);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return match ($this->widget_type) {
            'text'       => 'Text',
            'html'       => 'HTML',
            'image'      => 'Image',
            'navigation' => 'Navigation',
            default      => ucfirst($this->widget_type),
        };
    }
}
