<?php

namespace App\Support;

class TrackingIntegrationSettings
{
    public const GROUP = 'tracking_integrations';

    public static function fields(): array
    {
        return [
            ['key' => 'tracking.enabled', 'slug' => 'enabled', 'label' => 'Enable tracking integrations', 'type' => 'boolean', 'default' => '1', 'section' => 'General', 'hint' => 'Master switch for all tracking scripts and event tracking.'],
            ['key' => 'tracking.environment_mode', 'slug' => 'environment_mode', 'label' => 'Render mode', 'type' => 'select', 'default' => 'production_only', 'section' => 'General', 'hint' => 'Controls when tracking scripts are rendered on the public frontend.', 'options' => [
                'production_only' => 'Production only',
                'all' => 'All environments',
                'disabled' => 'Disabled',
            ]],
            ['key' => 'tracking.ga4.enabled', 'slug' => 'ga4_enabled', 'label' => 'Enable GA4', 'type' => 'boolean', 'default' => '0', 'section' => 'Google Analytics', 'hint' => 'Render the Google Analytics 4 gtag snippet.'],
            ['key' => 'tracking.ga4.measurement_id', 'slug' => 'ga4_measurement_id', 'label' => 'GA4 measurement ID', 'type' => 'text', 'default' => '', 'section' => 'Google Analytics', 'hint' => 'Example: G-XXXXXXXXXX.'],
            ['key' => 'tracking.gtm.enabled', 'slug' => 'gtm_enabled', 'label' => 'Enable GTM', 'type' => 'boolean', 'default' => '0', 'section' => 'Google Tag Manager', 'hint' => 'Render Google Tag Manager head and noscript snippets.'],
            ['key' => 'tracking.gtm.container_id', 'slug' => 'gtm_container_id', 'label' => 'GTM container ID', 'type' => 'text', 'default' => '', 'section' => 'Google Tag Manager', 'hint' => 'Example: GTM-XXXXXXX.'],
            ['key' => 'tracking.meta_pixel.enabled', 'slug' => 'meta_pixel_enabled', 'label' => 'Enable Meta Pixel', 'type' => 'boolean', 'default' => '0', 'section' => 'Meta Pixel', 'hint' => 'Render the Meta Pixel base code.'],
            ['key' => 'tracking.meta_pixel.pixel_id', 'slug' => 'meta_pixel_id', 'label' => 'Meta Pixel ID', 'type' => 'text', 'default' => '', 'section' => 'Meta Pixel', 'hint' => 'Numeric Pixel ID from Meta Events Manager.'],
            ['key' => 'tracking.google_verification', 'slug' => 'google_verification', 'label' => 'Google site verification', 'type' => 'text', 'default' => '', 'section' => 'Site Verification', 'hint' => 'Content value for the google-site-verification meta tag.'],
            ['key' => 'tracking.clarity.enabled', 'slug' => 'clarity_enabled', 'label' => 'Enable Microsoft Clarity', 'type' => 'boolean', 'default' => '0', 'section' => 'Microsoft Clarity', 'hint' => 'Render Microsoft Clarity session tracking.'],
            ['key' => 'tracking.clarity.project_id', 'slug' => 'clarity_project_id', 'label' => 'Clarity project ID', 'type' => 'text', 'default' => '', 'section' => 'Microsoft Clarity', 'hint' => 'Project ID from Microsoft Clarity.'],
            ['key' => 'tracking.custom_head.enabled', 'slug' => 'custom_head_enabled', 'label' => 'Enable custom head script', 'type' => 'boolean', 'default' => '0', 'section' => 'Custom Scripts', 'hint' => 'Render custom code before the closing head tag.'],
            ['key' => 'tracking.custom_head.script', 'slug' => 'custom_head_script', 'label' => 'Custom head script', 'type' => 'textarea', 'default' => '', 'section' => 'Custom Scripts', 'hint' => 'Paste trusted script or meta markup only.'],
            ['key' => 'tracking.custom_body_start.enabled', 'slug' => 'custom_body_start_enabled', 'label' => 'Enable body start script', 'type' => 'boolean', 'default' => '0', 'section' => 'Custom Scripts', 'hint' => 'Render custom code immediately after the opening body tag.'],
            ['key' => 'tracking.custom_body_start.script', 'slug' => 'custom_body_start_script', 'label' => 'Body start script', 'type' => 'textarea', 'default' => '', 'section' => 'Custom Scripts', 'hint' => 'Useful for noscript pixels or tag manager fallbacks.'],
            ['key' => 'tracking.custom_body_end.enabled', 'slug' => 'custom_body_end_enabled', 'label' => 'Enable body end script', 'type' => 'boolean', 'default' => '0', 'section' => 'Custom Scripts', 'hint' => 'Render custom code before the closing body tag.'],
            ['key' => 'tracking.custom_body_end.script', 'slug' => 'custom_body_end_script', 'label' => 'Body end script', 'type' => 'textarea', 'default' => '', 'section' => 'Custom Scripts', 'hint' => 'Useful for lightweight widgets that should load late.'],
            ['key' => 'tracking.whatsapp.enabled', 'slug' => 'whatsapp_enabled', 'label' => 'Enable WhatsApp CTA tracking', 'type' => 'boolean', 'default' => '1', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Track clicks on marked WhatsApp CTA buttons without blocking the visitor.'],
            ['key' => 'tracking.whatsapp.ga4_event_name', 'slug' => 'whatsapp_ga4_event_name', 'label' => 'GA4/DataLayer event name', 'type' => 'text', 'default' => 'whatsapp_cta_click', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Event name sent to gtag and dataLayer.'],
            ['key' => 'tracking.whatsapp.meta_event_name', 'slug' => 'whatsapp_meta_event_name', 'label' => 'Meta event name', 'type' => 'text', 'default' => 'Lead', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Meta Pixel event name, for example Lead or Contact.'],
            ['key' => 'tracking.whatsapp.track_header', 'slug' => 'whatsapp_track_header', 'label' => 'Track header CTA', 'type' => 'boolean', 'default' => '1', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Track the header Plan Trip action.'],
            ['key' => 'tracking.whatsapp.track_footer', 'slug' => 'whatsapp_track_footer', 'label' => 'Track footer CTA', 'type' => 'boolean', 'default' => '1', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Track footer WhatsApp buttons and contact links.'],
            ['key' => 'tracking.whatsapp.track_product', 'slug' => 'whatsapp_track_product', 'label' => 'Track product CTA', 'type' => 'boolean', 'default' => '1', 'section' => 'WhatsApp CTA Tracking', 'hint' => 'Track product detail WhatsApp booking and chat buttons.'],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        $values = collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?? $field['default']];
            })
            ->all();

        foreach (self::booleanSlugs() as $slug) {
            $values[$slug] = filter_var($values[$slug] ?? false, FILTER_VALIDATE_BOOL);
        }

        return $values;
    }

    public static function shouldRender(array $settings): bool
    {
        if (! ($settings['enabled'] ?? true)) {
            return false;
        }

        $mode = $settings['environment_mode'] ?? 'production_only';

        if ($mode === 'disabled') {
            return false;
        }

        if ($mode === 'all') {
            return true;
        }

        return app()->environment('production');
    }

    public static function booleanSlugs(): array
    {
        return collect(self::fields())
            ->filter(fn (array $field) => $field['type'] === 'boolean')
            ->pluck('slug')
            ->all();
    }
}
