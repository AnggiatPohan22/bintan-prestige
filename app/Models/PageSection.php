<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
