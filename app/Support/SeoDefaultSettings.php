<?php

namespace App\Support;

class SeoDefaultSettings
{
    public const GROUP = 'seo_default_settings';

    public const OG_IMAGE_KEY = 'seo.default.og_image';

    public static function fields(): array
    {
        return [
            ['key' => 'seo.default.meta_title', 'slug' => 'meta_title', 'label' => 'Default meta title', 'type' => 'text', 'default' => '', 'hint' => 'Fallback title when a page or product does not provide a custom SEO title.'],
            ['key' => 'seo.default.meta_description', 'slug' => 'meta_description', 'label' => 'Default meta description', 'type' => 'textarea', 'default' => '', 'hint' => 'Fallback description for search engines and social previews.'],
            ['key' => 'seo.default.keywords', 'slug' => 'keywords', 'label' => 'Default keywords', 'type' => 'textarea', 'default' => '', 'hint' => 'Optional legacy keywords fallback.'],
            ['key' => 'seo.default.title_suffix', 'slug' => 'title_suffix', 'label' => 'Title suffix', 'type' => 'text', 'default' => '', 'hint' => 'Optional brand suffix appended to page titles.'],
            ['key' => 'seo.default.title_separator', 'slug' => 'title_separator', 'label' => 'Title separator', 'type' => 'text', 'default' => '|', 'hint' => 'Separator used between page title and suffix.'],
            ['key' => 'seo.default.site_name', 'slug' => 'site_name', 'label' => 'Site name', 'type' => 'text', 'default' => '', 'hint' => 'Used for og:site_name and title fallbacks.'],
            ['key' => 'seo.default.canonical_base_url', 'slug' => 'canonical_base_url', 'label' => 'Canonical base URL', 'type' => 'url', 'default' => '', 'hint' => 'Primary public domain used to build canonical URLs.'],
            ['key' => 'seo.default.robots', 'slug' => 'robots', 'label' => 'Default robots', 'type' => 'select', 'default' => 'index, follow', 'hint' => 'Global fallback robots directive.', 'options' => [
                'index, follow' => 'index, follow',
                'index, nofollow' => 'index, nofollow',
                'noindex, follow' => 'noindex, follow',
                'noindex, nofollow' => 'noindex, nofollow',
            ]],
            ['key' => 'seo.default.locale', 'slug' => 'locale', 'label' => 'Default locale', 'type' => 'text', 'default' => 'en_US', 'hint' => 'Open Graph locale, for example en_US or id_ID.'],
            ['key' => 'seo.default.language', 'slug' => 'language', 'label' => 'Default language', 'type' => 'text', 'default' => 'en', 'hint' => 'HTML language code fallback.'],
            ['key' => 'seo.default.og_title', 'slug' => 'og_title', 'label' => 'Default OG title', 'type' => 'text', 'default' => '', 'hint' => 'Fallback social share title. Empty uses meta title.'],
            ['key' => 'seo.default.og_description', 'slug' => 'og_description', 'label' => 'Default OG description', 'type' => 'textarea', 'default' => '', 'hint' => 'Fallback social share description. Empty uses meta description.'],
            ['key' => 'seo.default.og_image_alt', 'slug' => 'og_image_alt', 'label' => 'Default OG image alt', 'type' => 'text', 'default' => '', 'hint' => 'Accessible description for the default OG image.'],
            ['key' => 'seo.default.twitter_card_type', 'slug' => 'twitter_card_type', 'label' => 'Twitter/X card type', 'type' => 'select', 'default' => 'summary_large_image', 'hint' => 'Default Twitter/X card format.', 'options' => [
                'summary_large_image' => 'summary_large_image',
                'summary' => 'summary',
            ]],
            ['key' => 'seo.default.enable_organization_schema', 'slug' => 'enable_organization_schema', 'label' => 'Enable organization schema', 'type' => 'boolean', 'default' => '1', 'hint' => 'Render global Organization JSON-LD using Business Identity, Contact Information, Social Links, and Site Logo.'],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        $values = collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting->value ?? $field['default']];
            })
            ->all();

        $values['enable_organization_schema'] = filter_var($values['enable_organization_schema'] ?? true, FILTER_VALIDATE_BOOL);

        return $values;
    }

    public static function canonicalUrl(?string $explicitCanonical, array $settings): string
    {
        if ($explicitCanonical) {
            if (str_starts_with($explicitCanonical, 'http://') || str_starts_with($explicitCanonical, 'https://')) {
                return $explicitCanonical;
            }

            $baseUrl = trim($settings['canonical_base_url'] ?? '') ?: url('/');

            return rtrim($baseUrl, '/').'/'.ltrim($explicitCanonical, '/');
        }

        $baseUrl = trim($settings['canonical_base_url'] ?? '');

        if ($baseUrl === '') {
            return url()->current();
        }

        return rtrim($baseUrl, '/').'/'.ltrim(request()->path(), '/');
    }

    public static function titleWithSuffix(string $title, array $settings): string
    {
        $suffix = trim($settings['title_suffix'] ?? '');

        if ($suffix === '' || str_contains($title, $suffix)) {
            return $title;
        }

        $separator = trim($settings['title_separator'] ?? '|') ?: '|';

        return trim($title.' '.$separator.' '.$suffix);
    }
}
