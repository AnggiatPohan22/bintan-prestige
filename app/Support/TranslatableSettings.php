<?php

namespace App\Support;

/**
 * Registry of the global-chrome SiteSetting keys that hold human-facing copy and
 * are therefore translatable per locale (Phase 7 — B2).
 *
 * This is a deliberate ALLOW-LIST: URLs, colours, tracking IDs, locale/language
 * codes, separators, phone numbers, selects and booleans are intentionally
 * excluded — translating them would break the site. The base column keeps the
 * default-locale value; translations live in the `translations` sidecar
 * (field = 'value') on each SiteSetting row.
 */
class TranslatableSettings
{
    /**
     * Sections of translatable copy: label => list of
     * ['key', 'label', 'type' => 'text'|'textarea'].
     *
     * @return array<string, list<array{key: string, label: string, type: string}>>
     */
    public static function sections(): array
    {
        return [
            'Header & Navigation' => [
                ['key' => 'navigation.header.cta_label', 'label' => 'Header CTA label', 'type' => 'text'],
            ],
            'Footer' => [
                ['key' => 'footer.bottom_note', 'label' => 'Footer bottom note', 'type' => 'text'],
            ],
            'Business identity' => [
                ['key' => 'business.identity.tagline', 'label' => 'Tagline', 'type' => 'text'],
                ['key' => 'business.identity.short_description', 'label' => 'Short description', 'type' => 'textarea'],
                ['key' => 'business.identity.location_label', 'label' => 'Location label', 'type' => 'text'],
                ['key' => 'business.identity.copyright_text', 'label' => 'Copyright text', 'type' => 'text'],
            ],
            'Booking CTA' => [
                ['key' => 'booking_cta.header_label', 'label' => 'Header CTA label', 'type' => 'text'],
                ['key' => 'booking_cta.footer_label', 'label' => 'Footer CTA label', 'type' => 'text'],
                ['key' => 'booking_cta.product_chat_label', 'label' => 'Product chat label', 'type' => 'text'],
                ['key' => 'booking_cta.product_booking_label', 'label' => 'Product booking label', 'type' => 'text'],
                ['key' => 'booking_cta.default_message', 'label' => 'Default WhatsApp message', 'type' => 'textarea'],
                ['key' => 'booking_cta.product_message_template', 'label' => 'Product message template', 'type' => 'textarea'],
            ],
            'SEO defaults' => [
                ['key' => 'seo.default.meta_title', 'label' => 'Default meta title', 'type' => 'text'],
                ['key' => 'seo.default.meta_description', 'label' => 'Default meta description', 'type' => 'textarea'],
                ['key' => 'seo.default.title_suffix', 'label' => 'Title suffix', 'type' => 'text'],
                ['key' => 'seo.default.site_name', 'label' => 'Site name', 'type' => 'text'],
                ['key' => 'seo.default.og_title', 'label' => 'Default OG title', 'type' => 'text'],
                ['key' => 'seo.default.og_description', 'label' => 'Default OG description', 'type' => 'textarea'],
                ['key' => 'seo.default.og_image_alt', 'label' => 'Default OG image alt', 'type' => 'text'],
            ],
        ];
    }

    /**
     * Flat list of every translatable setting key.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (self::sections() as $fields) {
            foreach ($fields as $field) {
                $keys[] = $field['key'];
            }
        }

        return $keys;
    }

    public static function isTranslatableKey(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }
}
