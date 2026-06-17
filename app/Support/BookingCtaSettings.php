<?php

namespace App\Support;

class BookingCtaSettings
{
    public const GROUP = 'booking_cta_settings';

    public static function fields(): array
    {
        return [
            ['key' => 'booking_cta.enabled', 'slug' => 'enabled', 'label' => 'Enable global booking CTA', 'type' => 'boolean', 'default' => '1', 'section' => 'General', 'hint' => 'Master switch for global booking CTA labels, messages, and WhatsApp destination.'],
            ['key' => 'booking_cta.use_on_header', 'slug' => 'use_on_header', 'label' => 'Use on header CTA', 'type' => 'boolean', 'default' => '0', 'section' => 'Placement', 'hint' => 'Override header CTA label and URL with global booking CTA.'],
            ['key' => 'booking_cta.use_on_footer', 'slug' => 'use_on_footer', 'label' => 'Use on footer CTA', 'type' => 'boolean', 'default' => '0', 'section' => 'Placement', 'hint' => 'Use global booking CTA for the large footer WhatsApp CTA.'],
            ['key' => 'booking_cta.use_on_product', 'slug' => 'use_on_product', 'label' => 'Use on product CTA', 'type' => 'boolean', 'default' => '0', 'section' => 'Placement', 'hint' => 'Use global product CTA labels and message template on product detail pages.'],
            ['key' => 'booking_cta.header_label', 'slug' => 'header_label', 'label' => 'Header CTA label', 'type' => 'text', 'default' => 'Plan Trip', 'section' => 'Labels', 'hint' => 'Short text for the global header booking action.'],
            ['key' => 'booking_cta.footer_label', 'slug' => 'footer_label', 'label' => 'Footer CTA label', 'type' => 'text', 'default' => 'Chat via WhatsApp', 'section' => 'Labels', 'hint' => 'Text for the large footer booking button.'],
            ['key' => 'booking_cta.product_chat_label', 'slug' => 'product_chat_label', 'label' => 'Product chat label', 'type' => 'text', 'default' => 'Chat via WhatsApp', 'section' => 'Labels', 'hint' => 'Primary product detail WhatsApp button label fallback.'],
            ['key' => 'booking_cta.product_booking_label', 'slug' => 'product_booking_label', 'label' => 'Product booking label', 'type' => 'text', 'default' => 'Book via WhatsApp', 'section' => 'Labels', 'hint' => 'Sticky booking card WhatsApp button label fallback.'],
            ['key' => 'booking_cta.whatsapp_number_source', 'slug' => 'whatsapp_number_source', 'label' => 'WhatsApp number source', 'type' => 'select', 'default' => 'contact_information', 'section' => 'WhatsApp Booking', 'hint' => 'Choose whether booking CTA uses Contact Information or a booking-specific override number.', 'options' => [
                'contact_information' => 'Contact Information',
                'override' => 'Override number',
            ]],
            ['key' => 'booking_cta.whatsapp_number_override', 'slug' => 'whatsapp_number_override', 'label' => 'WhatsApp override number', 'type' => 'text', 'default' => '', 'section' => 'WhatsApp Booking', 'hint' => 'International number without plus sign, used only when source is Override number.'],
            ['key' => 'booking_cta.default_message', 'slug' => 'default_message', 'label' => 'Default WhatsApp message', 'type' => 'textarea', 'default' => 'Hello {site_name}, I want to plan a Bintan trip.', 'section' => 'WhatsApp Booking', 'hint' => 'Used by header and footer booking CTA. Supports {site_name} and {page_url}.'],
            ['key' => 'booking_cta.product_message_template', 'slug' => 'product_message_template', 'label' => 'Product message template', 'type' => 'textarea', 'default' => "Hello {site_name}, I want to ask about:\n\n{product_name}\n{product_url}", 'section' => 'WhatsApp Booking', 'hint' => 'Used on product detail CTA. Supports {site_name}, {product_name}, {product_url}, and {page_url}.'],
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

    public static function booleanSlugs(): array
    {
        return collect(self::fields())
            ->filter(fn (array $field) => $field['type'] === 'boolean')
            ->pluck('slug')
            ->all();
    }

    public static function isEnabledFor(array $settings, string $placement): bool
    {
        return (bool) ($settings['enabled'] ?? true)
            && (bool) ($settings['use_on_' . $placement] ?? false);
    }

    public static function whatsappNumber(array $settings, array $contactInformation = [], ?string $productNumber = null): string
    {
        $productNumber = preg_replace('/\D+/', '', $productNumber ?? '');

        if ($productNumber !== '') {
            return $productNumber;
        }

        if (($settings['whatsapp_number_source'] ?? 'contact_information') === 'override') {
            $override = preg_replace('/\D+/', '', $settings['whatsapp_number_override'] ?? '');

            if ($override !== '') {
                return $override;
            }
        }

        return preg_replace('/\D+/', '', $contactInformation['whatsapp_number'] ?? '');
    }

    public static function whatsappUrl(array $settings, array $context = [], array $contactInformation = [], ?string $productNumber = null, ?string $messageTemplate = null): string
    {
        $number = self::whatsappNumber($settings, $contactInformation, $productNumber);
        $message = self::renderMessage($messageTemplate ?: ($settings['default_message'] ?? ''), $context);
        $query = $message !== '' ? '?text=' . urlencode($message) : '';

        return $number !== '' ? 'https://wa.me/' . $number . $query : 'https://wa.me/' . $query;
    }

    public static function renderMessage(string $template, array $context = []): string
    {
        $tokens = [
            '{site_name}' => $context['site_name'] ?? config('app.name'),
            '{product_name}' => $context['product_name'] ?? '',
            '{product_url}' => $context['product_url'] ?? '',
            '{page_url}' => $context['page_url'] ?? url()->current(),
        ];

        return trim(strtr($template, $tokens));
    }
}
