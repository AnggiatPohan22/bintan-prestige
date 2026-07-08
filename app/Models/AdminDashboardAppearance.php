<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDashboardAppearance extends Model
{
    protected $fillable = [
        'mode', 'sidebar_bg', 'sidebar_style',
        'primary_color', 'primary_hover', 'primary_text',
        'accent_color', 'gold_color', 'show_gold',
        'bg_base', 'bg_card', 'bg_input',
        'custom_vars',
        'preset_name', 'is_default',
        'dark_palette', 'light_palette',
        'dark_preset_name', 'light_preset_name',
        'brand_abbr', 'brand_name', 'brand_tagline',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'show_gold'     => 'boolean',
        'is_default'    => 'boolean',
        'custom_vars'   => 'array',
        'dark_palette'  => 'array',
        'light_palette' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ambil record appearance aktif (global, single-row pattern).
     * Jika DB kosong, return in-memory default — tidak menyimpan ke DB.
     */
    public static function getCurrent(): self
    {
        return self::first() ?? self::makeDefault();
    }

    /**
     * Buat instance default "Command Center Dark" tanpa persist ke DB.
     * Digunakan sebagai fallback dan sebagai template reset.
     */
    public static function makeDefault(): self
    {
        return new self([
            'mode'          => 'dark',
            'sidebar_bg'    => '#020617',
            'sidebar_style' => 'dark',
            'primary_color' => '#7C3AED',
            'primary_hover' => '#6D28D9',
            'primary_text'  => '#FFFFFF',
            'accent_color'  => '#06B6D4',
            'gold_color'    => '#D4AF37',
            'show_gold'     => true,
            'bg_base'       => '#020617',
            'bg_card'       => '#1E293B',
            'bg_input'      => '#0F172A',
            'custom_vars'   => null,
            'preset_name'   => 'Command Center Dark',
            'is_default'    => true,
            'brand_abbr'    => 'BP',
            'brand_name'    => 'Travel Admin',
            'brand_tagline' => 'Bintan Prestige',
        ]);
    }
}
