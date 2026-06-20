<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'data',
        'is_read',
        'ip_address',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_id');
    }
}
