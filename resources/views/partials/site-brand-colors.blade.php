@php
    $colors = $brandColors ?? [];
@endphp

@if($colors)
    <style>
        :root {
            --frontend-black: {{ $colors['palette_primary'] }};
            --frontend-gold: {{ $colors['palette_secondary'] }};
            --frontend-brand-accent: {{ $colors['palette_accent'] }};
            --frontend-white: {{ $colors['surface_card'] }};
            --frontend-gold-pale: {{ $colors['surface_soft'] }};
            --frontend-border: {{ $colors['surface_border'] }};
            --frontend-charcoal: {{ $colors['text_title'] }};
            --frontend-gray: {{ $colors['text_muted'] }};
            --frontend-gold-soft: {{ $colors['text_on_dark'] }};
            --frontend-text-title: {{ $colors['text_title'] }};
            --frontend-text-body: {{ $colors['text_body'] }};
            --frontend-text-muted: {{ $colors['text_muted'] }};
            --frontend-text-link: {{ $colors['text_link'] }};
            --frontend-text-on-dark: {{ $colors['text_on_dark'] }};
            --frontend-surface-body: {{ $colors['surface_body'] }};
            --frontend-surface-card: {{ $colors['surface_card'] }};
            --frontend-surface-soft: {{ $colors['surface_soft'] }};
            --frontend-surface-dark: {{ $colors['surface_dark'] }};
            --frontend-button-primary-bg: {{ $colors['button_primary_bg'] }};
            --frontend-button-primary-text: {{ $colors['button_primary_text'] }};
            --frontend-button-primary-hover-bg: {{ $colors['button_primary_hover_bg'] }};
            --frontend-button-primary-hover-text: {{ $colors['button_primary_hover_text'] }};
            --frontend-button-cta-bg: {{ $colors['button_cta_bg'] }};
            --frontend-button-cta-text: {{ $colors['button_cta_text'] }};
            --frontend-button-submit-bg: {{ $colors['button_submit_bg'] }};
            --frontend-button-submit-text: {{ $colors['button_submit_text'] }};
        }
    </style>
@endif
