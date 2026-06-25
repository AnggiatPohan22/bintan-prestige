<?php

namespace App\Services;

use App\Models\AdminDashboardAppearance;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminAppearanceService
{
    private const CACHE_KEY = 'admin_dashboard_appearance';
    private const CACHE_TTL = 3600; // 1 jam

    public function getCurrent(): AdminDashboardAppearance
    {
        // Cache raw attributes array — bukan Eloquent object.
        // Menyimpan object ke cache menyebabkan __PHP_Incomplete_Class saat
        // deserialized di request baru sebelum autoloader load class-nya.
        $attributes = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $record = AdminDashboardAppearance::first();
            return $record?->getAttributes();
        });

        if ($attributes) {
            $model = new AdminDashboardAppearance();
            $model->setRawAttributes($attributes);
            $model->exists = true;
            return $model;
        }

        return AdminDashboardAppearance::makeDefault();
    }

    public function update(array $data, User $user): AdminDashboardAppearance
    {
        $appearance = AdminDashboardAppearance::first();

        if ($appearance) {
            $appearance->update(array_merge($data, ['updated_by' => $user->id]));
        } else {
            $appearance = AdminDashboardAppearance::create(
                array_merge($data, ['created_by' => $user->id])
            );
        }

        $this->clearCache();

        return $appearance->fresh();
    }

    public function reset(User $user): AdminDashboardAppearance
    {
        $defaults = AdminDashboardAppearance::makeDefault()->toArray();
        unset($defaults['id'], $defaults['created_at'], $defaults['updated_at']);

        return $this->update($defaults, $user);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Resolve actual UI mode (dark|light) for the given user.
     * Priority: user.ui_mode override -> global preset mode.
     * 'auto' means the user follows whatever superadmin sets globally.
     */
    public function resolveModeForUser(?User $user): string
    {
        $global = $this->getCurrent()->mode ?? config('admin_palettes.defaults.mode', 'light');

        if (! $user || ($user->ui_mode ?? User::UI_MODE_AUTO) === User::UI_MODE_AUTO) {
            return $global;
        }

        return $user->ui_mode;
    }

    /**
     * Return the token bag (flat hex map) for the requested mode.
     *
     * Resolution order:
     *  1. DB column (`dark_palette` or `light_palette` JSON) if present
     *  2. Starter preset from config (defaults.dark_preset / defaults.light_preset)
     *  3. Empty array (the legacy hex columns + admin.css fallback take over)
     */
    public function paletteFor(string $mode): array
    {
        $a = $this->getCurrent();
        $column = $mode === 'dark' ? 'dark_palette' : 'light_palette';

        $stored = is_array($a->{$column} ?? null) ? $a->{$column} : [];
        if (! empty($stored)) {
            return $stored;
        }

        $presetKey = config("admin_palettes.defaults.{$mode}_preset");
        return config("admin_palettes.presets.{$presetKey}.tokens", []);
    }

    /**
     * Emit CSS custom-property block for the given resolved mode.
     *
     * Selector strategy:
     *  - dark mode: `:root` — ties with admin.css :root specificity but our
     *    style tag is appended after the vite bundle so source-order wins.
     *  - light mode: `html[data-admin-mode="light"]` — ties with admin.css
     *    light override block specificity (0,0,1,1), source-order wins for
     *    same reason.
     *
     * Returns '' if no token bag (legacy install) — composer falls back to
     * the original toCssVars() path so the page is never themeless.
     */
    public function toCssVarsForMode(string $mode): string
    {
        $tokens = $this->paletteFor($mode);
        if (empty($tokens)) {
            return '';
        }

        $lines = [];
        foreach ($tokens as $key => $value) {
            // Defense-in-depth: whitelist token keys (alphanumeric + dash)
            // and value chars (hex, rgba, transparent words). FormRequest
            // validates user input on save; this guards raw output.
            if (! preg_match('/^[a-z0-9-]+$/i', $key)) continue;
            if (! preg_match('/^[#\w\s().,%\/-]+$/', $value)) continue;
            $lines[] = "            --admin-{$key}: {$value};";
        }

        $body     = implode("\n", $lines);
        $selector = $mode === 'light' ? 'html[data-admin-mode="light"]' : ':root';

        return "{$selector} {\n{$body}\n        }";
    }

    /**
     * Generate :root CSS string untuk di-inject ke <style id="admin-appearance-vars">
     * di admin.blade.php. Semua hex values sudah divalidasi via FormRequest sebelum masuk DB.
     *
     * Audit fix vs Step 13 prompt:
     * - --admin-bg-surface TIDAK di-override (hanya dipakai legacy .card-header)
     * - --admin-sidebar-bg mapping ke sidebar_bg (benar, terpisah dari bg_base)
     * - custom_vars JSON di-merge di akhir untuk future extensions
     */
    public function toCssVars(AdminDashboardAppearance $a): string
    {
        $primaryHex = ltrim($a->primary_color, '#');
        $accentHex  = ltrim($a->accent_color, '#');
        $goldHex    = ltrim($a->gold_color, '#');

        $modeVars = $a->mode === 'dark'
            ? $this->darkModeVars()
            : $this->lightModeVars();

        $customVars = '';
        if (! empty($a->custom_vars) && is_array($a->custom_vars)) {
            foreach ($a->custom_vars as $varName => $varValue) {
                // Sanitize: only allow CSS var names and safe values
                if (preg_match('/^--[\w-]+$/', $varName) && preg_match('/^[\w\s#().,%\/]+$/', $varValue)) {
                    $customVars .= "            {$varName}: {$varValue};\n";
                }
            }
        }

        return ":root {
            --admin-bg-base:              {$a->bg_base};
            --admin-bg-card:              {$a->bg_card};
            --admin-bg-input:             {$a->bg_input};
            --admin-sidebar-bg:           {$a->sidebar_bg};
            --admin-primary:              {$a->primary_color};
            --admin-primary-hover:        {$a->primary_hover};
            --admin-primary-text:         {$a->primary_text};
            --admin-primary-soft:         #{$primaryHex}26;
            --admin-primary-glow:         #{$primaryHex}66;
            --admin-accent:               {$a->accent_color};
            --admin-accent-soft:          #{$accentHex}26;
            --admin-gold:                 {$a->gold_color};
            --admin-gold-soft:            #{$goldHex}1A;
            --admin-sidebar-active-bg:    #{$primaryHex}33;
            --admin-sidebar-active-text:  {$a->primary_color};
            --admin-sidebar-active-border:{$a->primary_color};
            {$modeVars}{$customVars}
        }";
    }

    private function darkModeVars(): string
    {
        return "
            --admin-border:              rgba(255, 255, 255, 0.08);
            --admin-border-md:           rgba(255, 255, 255, 0.12);
            --admin-border-strong:       rgba(255, 255, 255, 0.20);
            --admin-text-primary:        #F1F5F9;
            --admin-text-secondary:      #94A3B8;
            --admin-text-muted:          #64748B;
            --admin-bg-hover:            #334155;
            --admin-sidebar-border:      rgba(255, 255, 255, 0.06);
            --admin-sidebar-text:        #64748B;
            --admin-sidebar-text-hover:  #CBD5E1;
        ";
    }

    private function lightModeVars(): string
    {
        return "
            --admin-border:              #E2E8F0;
            --admin-border-md:           #CBD5E1;
            --admin-border-strong:       #94A3B8;
            --admin-text-primary:        #0F172A;
            --admin-text-secondary:      #475569;
            --admin-text-muted:          #94A3B8;
            --admin-bg-hover:            #F1F5F9;
            --admin-sidebar-border:      rgba(255, 255, 255, 0.06);
            --admin-sidebar-text:        #64748B;
            --admin-sidebar-text-hover:  #CBD5E1;
        ";
    }
}
