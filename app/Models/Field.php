<?php

namespace App\Models;

use App\Support\FieldTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_group_id',
        'type',
        'key',
        'label',
        'instructions',
        'is_required',
        'is_filterable',
        'settings',
        'default_value',
        'conditional_logic',
        'sort_order',
    ];

    protected $casts = [
        'field_group_id'   => 'integer',
        'is_required'      => 'boolean',
        'is_filterable'    => 'boolean',
        'settings'         => 'array',
        'default_value'    => 'array',
        'conditional_logic' => 'array',
        'sort_order'       => 'integer',
    ];

    /** @return BelongsTo<FieldGroup, $this> */
    public function fieldGroup(): BelongsTo
    {
        return $this->belongsTo(FieldGroup::class);
    }

    /** Definition from config/field-types.php for this field's type. */
    public function typeDefinition(): ?array
    {
        return FieldTypeRegistry::get($this->type);
    }

    public function typeLabel(): string
    {
        return $this->typeDefinition()['label'] ?? $this->type;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}
