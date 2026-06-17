<?php

namespace App\Support;

class ContactInformationSettings
{
    public const GROUP = 'contact_information';

    public static function fields(): array
    {
        return [
            [
                'key' => 'contact.email',
                'slug' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'default' => '',
                'hint' => 'Primary business email for public contact and future forms.',
            ],
            [
                'key' => 'contact.phone',
                'slug' => 'phone',
                'label' => 'Phone',
                'type' => 'text',
                'default' => '',
                'hint' => 'Primary phone number shown in footer or contact sections.',
            ],
            [
                'key' => 'contact.whatsapp_number',
                'slug' => 'whatsapp_number',
                'label' => 'WhatsApp number',
                'type' => 'text',
                'default' => '',
                'hint' => 'Use international format without plus sign when possible, for example 628123456789.',
            ],
            [
                'key' => 'contact.whatsapp_message',
                'slug' => 'whatsapp_message',
                'label' => 'Default WhatsApp message',
                'type' => 'textarea',
                'default' => 'Hello Bintan Prestige, I want to plan a Bintan trip.',
                'hint' => 'Default message used by WhatsApp CTA links.',
            ],
            [
                'key' => 'contact.address',
                'slug' => 'address',
                'label' => 'Address',
                'type' => 'textarea',
                'default' => 'Bintan Island, Indonesia',
                'hint' => 'Business address or public location text.',
            ],
            [
                'key' => 'contact.google_maps_url',
                'slug' => 'google_maps_url',
                'label' => 'Google Maps URL',
                'type' => 'url',
                'default' => '',
                'hint' => 'Optional public Google Maps link for footer or contact page.',
            ],
            [
                'key' => 'contact.opening_hours',
                'slug' => 'opening_hours',
                'label' => 'Opening hours',
                'type' => 'text',
                'default' => 'Open Daily',
                'hint' => 'Short operating hours text shown in footer information.',
            ],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        return collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?: $field['default']];
            })
            ->all();
    }

    public static function whatsappUrl(array $contactInformation): string
    {
        $number = preg_replace('/\D+/', '', $contactInformation['whatsapp_number'] ?? '');
        $message = $contactInformation['whatsapp_message'] ?? '';
        $query = $message !== '' ? '?text=' . urlencode($message) : '';

        return $number !== '' ? 'https://wa.me/' . $number . $query : 'https://wa.me/' . $query;
    }
}
