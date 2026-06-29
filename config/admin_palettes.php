<?php

/*
|--------------------------------------------------------------------------
| Admin Dashboard Palettes — Step 19 Theme System
|--------------------------------------------------------------------------
|
| Source of truth for 4 starter presets, each one a flat token bag that maps
| 1:1 to CSS custom properties (--admin-*) emitted by AdminAppearanceService.
|
| Two starter categories:
|   - Dark starters (Command Center Dark, Maroon)  -> seed `dark_palette`
|   - Light starters (Full Light, Studio Light)    -> seed `light_palette`
|
| Section grouping is metadata-only: tokens are flat at runtime. The Customizer
| UI (Step 19.6) uses these section keys to lay out the sectioned editor.
|
| Owner-locked values: see ai/reports/UIUX/step-19-1-handoff.md
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Section catalogue (10 sections — drives Customizer UI grouping)
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'surfaces' => ['label' => 'Surfaces', 'icon' => 'fa-layer-group'],
        'text'     => ['label' => 'Text',     'icon' => 'fa-font'],
        'buttons'  => ['label' => 'Buttons',  'icon' => 'fa-square'],
        'forms'    => ['label' => 'Forms',    'icon' => 'fa-keyboard'],
        'badges'   => ['label' => 'Badges',   'icon' => 'fa-tag'],
        'tables'   => ['label' => 'Tables',   'icon' => 'fa-table'],
        'alerts'   => ['label' => 'Alerts',   'icon' => 'fa-bell'],
        'modal'    => ['label' => 'Modal',    'icon' => 'fa-window-maximize'],
        'topbar'   => ['label' => 'Topbar',   'icon' => 'fa-bars'],
        'sidebar'  => ['label' => 'Sidebar',  'icon' => 'fa-bars-staggered'],
        'brand'    => ['label' => 'Brand',    'icon' => 'fa-star'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token catalogue (key -> [section, label, default-tone hint])
    |--------------------------------------------------------------------------
    | section_for() lookups + Customizer UI consumes this. CSS var name is
    | always `--admin-{key-kebab}` (already kebab here).
    */
    'tokens' => [
        // Surfaces
        'bg-base'                 => ['section' => 'surfaces', 'label' => 'Base background'],
        'bg-surface'              => ['section' => 'surfaces', 'label' => 'Surface'],
        'bg-card'                 => ['section' => 'surfaces', 'label' => 'Card'],
        'card-header-bg'          => ['section' => 'surfaces', 'label' => 'Card header BG'],
        'bg-hover'                => ['section' => 'surfaces', 'label' => 'Hover surface'],
        'border'                  => ['section' => 'surfaces', 'label' => 'Border subtle'],
        'border-md'               => ['section' => 'surfaces', 'label' => 'Border medium'],
        'border-strong'           => ['section' => 'surfaces', 'label' => 'Border strong'],
        // Text
        'text-primary'            => ['section' => 'text', 'label' => 'Text primary'],
        'text-secondary'          => ['section' => 'text', 'label' => 'Text secondary'],
        'text-muted'              => ['section' => 'text', 'label' => 'Text muted'],
        'text-link'               => ['section' => 'text', 'label' => 'Text link'],
        // Buttons
        'primary'                 => ['section' => 'buttons', 'label' => 'Primary BG'],
        'primary-hover'           => ['section' => 'buttons', 'label' => 'Primary hover'],
        'primary-text'            => ['section' => 'buttons', 'label' => 'Primary text'],
        'btn-danger-bg'           => ['section' => 'buttons', 'label' => 'Danger BG'],
        'btn-danger-hover'        => ['section' => 'buttons', 'label' => 'Danger hover'],
        'btn-secondary-bg'        => ['section' => 'buttons', 'label' => 'Secondary BG'],
        'btn-secondary-text'      => ['section' => 'buttons', 'label' => 'Secondary text'],
        'btn-soft-bg'             => ['section' => 'buttons', 'label' => 'Soft BG'],
        'btn-soft-text'           => ['section' => 'buttons', 'label' => 'Soft text'],
        'pill-active-bg'          => ['section' => 'buttons', 'label' => 'Pill active BG'],
        'pill-active-text'        => ['section' => 'buttons', 'label' => 'Pill active text'],
        'pill-hover-bg'           => ['section' => 'buttons', 'label' => 'Pill hover BG'],
        'pill-hover-border'       => ['section' => 'buttons', 'label' => 'Pill hover border'],
        'pill-hover-text'         => ['section' => 'buttons', 'label' => 'Pill hover text'],
        // Forms
        'bg-input'                => ['section' => 'forms', 'label' => 'Input BG'],
        'input-border'            => ['section' => 'forms', 'label' => 'Input border'],
        'input-focus-ring'        => ['section' => 'forms', 'label' => 'Focus ring'],
        'label-color'             => ['section' => 'forms', 'label' => 'Label color'],
        // Badges
        'success'                 => ['section' => 'badges', 'label' => 'Success'],
        'danger'                  => ['section' => 'badges', 'label' => 'Danger'],
        'warning'                 => ['section' => 'badges', 'label' => 'Warning'],
        'info'                    => ['section' => 'badges', 'label' => 'Info'],
        // Tables
        'table-header-bg'         => ['section' => 'tables', 'label' => 'Header BG'],
        'table-row-hover'         => ['section' => 'tables', 'label' => 'Row hover'],
        'table-border'            => ['section' => 'tables', 'label' => 'Row border'],
        // Alerts
        'alert-success-bg'        => ['section' => 'alerts', 'label' => 'Success alert BG'],
        'alert-danger-bg'         => ['section' => 'alerts', 'label' => 'Danger alert BG'],
        'alert-warning-bg'        => ['section' => 'alerts', 'label' => 'Warning alert BG'],
        'alert-info-bg'           => ['section' => 'alerts', 'label' => 'Info alert BG'],
        // Modal
        'modal-bg'                => ['section' => 'modal', 'label' => 'Modal panel BG'],
        'modal-border'            => ['section' => 'modal', 'label' => 'Modal border'],
        'modal-overlay'           => ['section' => 'modal', 'label' => 'Backdrop overlay'],
        // Topbar
        'topbar-bg'               => ['section' => 'topbar', 'label' => 'Topbar BG'],
        'topbar-border'           => ['section' => 'topbar', 'label' => 'Topbar border'],
        'topbar-text'             => ['section' => 'topbar', 'label' => 'Topbar text'],
        // Sidebar
        'sidebar-bg'              => ['section' => 'sidebar', 'label' => 'Sidebar BG'],
        'sidebar-border'          => ['section' => 'sidebar', 'label' => 'Sidebar border'],
        'sidebar-text'            => ['section' => 'sidebar', 'label' => 'Sidebar text'],
        'sidebar-text-hover'      => ['section' => 'sidebar', 'label' => 'Sidebar text hover'],
        'sidebar-active-bg'       => ['section' => 'sidebar', 'label' => 'Sidebar active BG'],
        'sidebar-active-text'     => ['section' => 'sidebar', 'label' => 'Sidebar active text'],
        'sidebar-active-border'   => ['section' => 'sidebar', 'label' => 'Sidebar active border'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 4 STARTER PRESETS
    |--------------------------------------------------------------------------
    */
    'presets' => [

        // ===== DARK STARTERS =====================================================
        'command-center-dark' => [
            'label'    => 'Command Center Dark',
            'mode'     => 'dark',
            'tokens'   => [
                'bg-base'               => '#020617',
                'bg-surface'            => '#0F172A',
                'bg-card'               => '#1E293B',
                'card-header-bg'        => '#0F172A',
                'bg-hover'              => '#334155',
                'border'                => 'rgba(255,255,255,0.08)',
                'border-md'             => 'rgba(255,255,255,0.16)',
                'border-strong'         => 'rgba(255,255,255,0.24)',
                'text-primary'          => '#F1F5F9',
                'text-secondary'        => '#94A3B8',
                'text-muted'            => '#64748B',
                'text-link'             => '#60A5FA',
                'primary'               => '#166AE9',
                'primary-hover'         => '#1054BC',
                'primary-text'          => '#FFFFFF',
                'btn-danger-bg'         => '#DC2626',
                'btn-danger-hover'      => '#B91C1C',
                'btn-secondary-bg'      => '#1E293B',
                'btn-secondary-text'    => '#F1F5F9',
                'btn-soft-bg'           => 'rgba(22,106,233,0.15)',
                'btn-soft-text'         => '#93C5FD',
                'pill-active-bg'        => '#166AE9',
                'pill-active-text'      => '#FFFFFF',
                'pill-hover-bg'         => 'rgba(22,106,233,0.15)',
                'pill-hover-border'     => 'rgba(22,106,233,0.15)',
                'pill-hover-text'       => '#93C5FD',
                'bg-input'              => '#0F172A',
                'input-border'          => 'rgba(255,255,255,0.12)',
                'input-focus-ring'      => 'rgba(22,106,233,0.32)',
                'label-color'           => '#94A3B8',
                'success'               => '#10B981',
                'danger'                => '#DC2626',
                'warning'               => '#F59E0B',
                'info'                  => '#0EA5E9',
                'table-header-bg'       => '#0F172A',
                'table-row-hover'       => '#334155',
                'table-border'          => 'rgba(255,255,255,0.06)',
                'alert-success-bg'      => 'rgba(16,185,129,0.12)',
                'alert-danger-bg'       => 'rgba(220,38,38,0.12)',
                'alert-warning-bg'      => 'rgba(245,158,11,0.12)',
                'alert-info-bg'         => 'rgba(14,165,233,0.12)',
                'modal-bg'              => '#1E293B',
                'modal-border'          => 'rgba(255,255,255,0.16)',
                'modal-overlay'         => 'rgba(2,6,23,0.72)',
                'topbar-bg'             => '#0F172A',
                'topbar-border'         => 'rgba(255,255,255,0.08)',
                'topbar-text'           => '#F1F5F9',
                'sidebar-bg'            => '#020617',
                'sidebar-border'        => 'rgba(255,255,255,0.06)',
                'sidebar-text'          => '#64748B',
                'sidebar-text-hover'    => '#CBD5E1',
                'sidebar-active-bg'     => 'rgba(22,106,233,0.20)',
                'sidebar-active-text'   => '#93C5FD',
                'sidebar-active-border' => '#166AE9',
            ],
        ],

        'maroon' => [
            'label'    => 'Maroon',
            'mode'     => 'dark',
            'tokens'   => [
                'bg-base'               => '#1A0E11',
                'bg-surface'            => '#241419',
                'bg-card'               => '#2E1A20',
                'card-header-bg'        => '#1A0E11',
                'bg-hover'              => '#3D2229',
                'border'                => 'rgba(255,255,255,0.08)',
                'border-md'             => 'rgba(255,255,255,0.16)',
                'border-strong'         => 'rgba(255,255,255,0.24)',
                'text-primary'          => '#FAFAFA',
                'text-secondary'        => '#C9B5B9',
                'text-muted'            => '#9A7E84',
                'text-link'             => '#F4A6B8',
                'primary'               => '#B83E5C',
                'primary-hover'         => '#8B2942',
                'primary-text'          => '#FFFFFF',
                'btn-danger-bg'         => '#EF4444',
                'btn-danger-hover'      => '#DC2626',
                'btn-secondary-bg'      => '#2E1A20',
                'btn-secondary-text'    => '#FAFAFA',
                'btn-soft-bg'           => 'rgba(184,62,92,0.15)',
                'btn-soft-text'         => '#F4A6B8',
                'pill-active-bg'        => '#B83E5C',
                'pill-active-text'      => '#FFFFFF',
                'pill-hover-bg'         => 'rgba(184,62,92,0.15)',
                'pill-hover-border'     => 'rgba(184,62,92,0.15)',
                'pill-hover-text'       => '#F4A6B8',
                'bg-input'              => '#241419',
                'input-border'          => 'rgba(255,255,255,0.12)',
                'input-focus-ring'      => 'rgba(184,62,92,0.32)',
                'label-color'           => '#C9B5B9',
                'success'               => '#10B981',
                'danger'                => '#EF4444',
                'warning'               => '#F59E0B',
                'info'                  => '#0EA5E9',
                'table-header-bg'       => '#241419',
                'table-row-hover'       => '#3D2229',
                'table-border'          => 'rgba(255,255,255,0.06)',
                'alert-success-bg'      => 'rgba(16,185,129,0.12)',
                'alert-danger-bg'       => 'rgba(239,68,68,0.12)',
                'alert-warning-bg'      => 'rgba(245,158,11,0.12)',
                'alert-info-bg'         => 'rgba(14,165,233,0.12)',
                'modal-bg'              => '#2E1A20',
                'modal-border'          => 'rgba(255,255,255,0.16)',
                'modal-overlay'         => 'rgba(26,14,17,0.78)',
                'topbar-bg'             => '#241419',
                'topbar-border'         => 'rgba(255,255,255,0.08)',
                'topbar-text'           => '#FAFAFA',
                'sidebar-bg'            => '#1A0E11',
                'sidebar-border'        => 'rgba(255,255,255,0.06)',
                'sidebar-text'          => '#9A7E84',
                'sidebar-text-hover'    => '#FAFAFA',
                'sidebar-active-bg'     => 'rgba(184,62,92,0.22)',
                'sidebar-active-text'   => '#F4A6B8',
                'sidebar-active-border' => '#B83E5C',
            ],
        ],

        // ===== LIGHT STARTERS ====================================================
        'full-light' => [
            'label'    => 'Full Light',
            'mode'     => 'light',
            'tokens'   => [
                'bg-base'               => '#FFFFFF',
                'bg-surface'            => '#FAFAFA',
                'bg-card'               => '#FFFFFF',
                'card-header-bg'        => '#F3F4F6',
                'bg-hover'              => '#F3F4F6',
                'border'                => '#E5E7EB',
                'border-md'             => '#D1D5DB',
                'border-strong'         => '#9CA3AF',
                'text-primary'          => '#0F172A',
                'text-secondary'        => '#475569',
                'text-muted'            => '#94A3B8',
                'text-link'             => '#8B2942',
                'primary'               => '#8B2942',
                'primary-hover'         => '#722F37',
                'primary-text'          => '#FFFFFF',
                'btn-danger-bg'         => '#B91C1C',
                'btn-danger-hover'      => '#991B1B',
                'btn-secondary-bg'      => '#F3F4F6',
                'btn-secondary-text'    => '#0F172A',
                'btn-soft-bg'           => 'rgba(139,41,66,0.10)',
                'btn-soft-text'         => '#8B2942',
                'pill-active-bg'        => '#8B2942',
                'pill-active-text'      => '#FFFFFF',
                'pill-hover-bg'         => 'rgba(139,41,66,0.10)',
                'pill-hover-border'     => 'rgba(139,41,66,0.10)',
                'pill-hover-text'       => '#8B2942',
                'bg-input'              => '#FFFFFF',
                'input-border'          => '#D1D5DB',
                'input-focus-ring'      => 'rgba(139,41,66,0.18)',
                'label-color'           => '#475569',
                'success'               => '#047857',
                'danger'                => '#B91C1C',
                'warning'               => '#B45309',
                'info'                  => '#0369A1',
                'table-header-bg'       => '#FAFAFA',
                'table-row-hover'       => '#F3F4F6',
                'table-border'          => '#E5E7EB',
                'alert-success-bg'      => '#ECFDF5',
                'alert-danger-bg'       => '#FEF2F2',
                'alert-warning-bg'      => '#FFFBEB',
                'alert-info-bg'         => '#EFF6FF',
                'modal-bg'              => '#FFFFFF',
                'modal-border'          => '#E5E7EB',
                'modal-overlay'         => 'rgba(15,23,42,0.40)',
                'topbar-bg'             => '#FFFFFF',
                'topbar-border'         => '#E5E7EB',
                'topbar-text'           => '#0F172A',
                'sidebar-bg'            => '#FFFFFF',
                'sidebar-border'        => '#E5E7EB',
                'sidebar-text'          => '#475569',
                'sidebar-text-hover'    => '#0F172A',
                'sidebar-active-bg'     => 'rgba(139,41,66,0.08)',
                'sidebar-active-text'   => '#8B2942',
                'sidebar-active-border' => '#8B2942',
            ],
        ],

        'studio-light' => [
            'label'    => 'Studio Light',
            'mode'     => 'light',
            'tokens'   => [
                'bg-base'               => '#F8FAFC',
                'bg-surface'            => '#FFFFFF',
                'bg-card'               => '#FFFFFF',
                'card-header-bg'        => '#F1F5F9',
                'bg-hover'              => '#F1F5F9',
                'border'                => '#E2E8F0',
                'border-md'             => '#CBD5E1',
                'border-strong'         => '#94A3B8',
                'text-primary'          => '#0F172A',
                'text-secondary'        => '#475569',
                'text-muted'            => '#94A3B8',
                'text-link'             => '#8B2942',
                'primary'               => '#8B2942',
                'primary-hover'         => '#722F37',
                'primary-text'          => '#FFFFFF',
                'btn-danger-bg'         => '#B91C1C',
                'btn-danger-hover'      => '#991B1B',
                'btn-secondary-bg'      => '#F1F5F9',
                'btn-secondary-text'    => '#0F172A',
                'btn-soft-bg'           => 'rgba(139,41,66,0.10)',
                'btn-soft-text'         => '#8B2942',
                'pill-active-bg'        => '#8B2942',
                'pill-active-text'      => '#FFFFFF',
                'pill-hover-bg'         => 'rgba(139,41,66,0.10)',
                'pill-hover-border'     => 'rgba(139,41,66,0.10)',
                'pill-hover-text'       => '#8B2942',
                'bg-input'              => '#FFFFFF',
                'input-border'          => '#CBD5E1',
                'input-focus-ring'      => 'rgba(139,41,66,0.18)',
                'label-color'           => '#475569',
                'success'               => '#047857',
                'danger'                => '#B91C1C',
                'warning'               => '#B45309',
                'info'                  => '#0369A1',
                'table-header-bg'       => '#F8FAFC',
                'table-row-hover'       => '#F1F5F9',
                'table-border'          => '#E2E8F0',
                'alert-success-bg'      => '#ECFDF5',
                'alert-danger-bg'       => '#FEF2F2',
                'alert-warning-bg'      => '#FFFBEB',
                'alert-info-bg'         => '#EFF6FF',
                'modal-bg'              => '#FFFFFF',
                'modal-border'          => '#E2E8F0',
                'modal-overlay'         => 'rgba(15,23,42,0.45)',
                'topbar-bg'             => '#FFFFFF',
                'topbar-border'         => '#E2E8F0',
                'topbar-text'           => '#0F172A',
                'sidebar-bg'            => '#FFFFFF',
                'sidebar-border'        => '#E2E8F0',
                'sidebar-text'          => '#475569',
                'sidebar-text-hover'    => '#0F172A',
                'sidebar-active-bg'     => 'rgba(139,41,66,0.08)',
                'sidebar-active-text'   => '#8B2942',
                'sidebar-active-border' => '#8B2942',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults — picked by AdminAppearanceService on fresh install / fallback
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'dark_preset'  => 'command-center-dark',
        'light_preset' => 'full-light',
        // Resolved mode for users with ui_mode='auto' (and for the global
        // appearance.mode column on fresh installs).
        'mode'         => 'light',
    ],

];
