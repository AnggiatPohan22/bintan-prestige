<?php

namespace App\Services;

use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Support\BrandColorSettings;
use App\Support\BookingCtaSettings;
use App\Support\BusinessIdentitySettings;
use App\Support\ContactInformationSettings;
use App\Support\DefaultMediaAssets;
use App\Support\FooterSettings;
use App\Support\Locales;
use App\Support\NavigationSettings;
use App\Support\SeoDefaultSettings;
use App\Support\SocialMediaLinkSettings;
use App\Support\StructuredDataSettings;
use App\Support\TrackingIntegrationSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GlobalSettingsService
{
    public const SETTINGS_CACHE_KEY = 'global_settings.public.v1';
    public const ASSETS_CACHE_KEY = 'global_assets.public.v1';
    public const CACHE_TTL_MINUTES = 30;

    private const PUBLIC_SETTING_GROUPS = [
        BrandColorSettings::GROUP,
        BusinessIdentitySettings::GROUP,
        ContactInformationSettings::GROUP,
        SocialMediaLinkSettings::GROUP,
        NavigationSettings::GROUP,
        FooterSettings::GROUP,
        SeoDefaultSettings::GROUP,
        TrackingIntegrationSettings::GROUP,
        BookingCtaSettings::GROUP,
        DefaultMediaAssets::SETTINGS_GROUP,
        StructuredDataSettings::GROUP,
    ];

    private ?Collection $settingsByGroup = null;

    private ?Collection $siteAssets = null;

    private ?array $viewData = null;

    public function viewData(): array
    {
        if ($this->viewData !== null) {
            return $this->viewData;
        }

        $brandColors = BrandColorSettings::valuesFromSettings($this->settingsForGroup(BrandColorSettings::GROUP));
        $businessIdentity = BusinessIdentitySettings::valuesFromSettings($this->settingsForGroup(BusinessIdentitySettings::GROUP));
        $contactInformation = ContactInformationSettings::valuesFromSettings($this->settingsForGroup(ContactInformationSettings::GROUP));
        $socialMediaLinks = SocialMediaLinkSettings::valuesFromSettings($this->settingsForGroup(SocialMediaLinkSettings::GROUP));

        return $this->viewData = [
            'siteAssets' => $this->siteAssets(),
            'brandColors' => $brandColors,
            'businessIdentity' => $businessIdentity,
            'contactInformation' => $contactInformation,
            'contactWhatsappUrl' => ContactInformationSettings::whatsappUrl($contactInformation),
            'socialMediaLinks' => $socialMediaLinks,
            'activeSocialMediaLinks' => SocialMediaLinkSettings::activeLinks($socialMediaLinks),
            'navigationSettings' => NavigationSettings::valuesFromSettings($this->settingsForGroup(NavigationSettings::GROUP)),
            'footerSettings' => FooterSettings::valuesFromSettings($this->settingsForGroup(FooterSettings::GROUP)),
            'seoDefaultSettings' => SeoDefaultSettings::valuesFromSettings($this->settingsForGroup(SeoDefaultSettings::GROUP)),
            'trackingIntegrationSettings' => TrackingIntegrationSettings::valuesFromSettings($this->settingsForGroup(TrackingIntegrationSettings::GROUP)),
            'bookingCtaSettings' => BookingCtaSettings::valuesFromSettings($this->settingsForGroup(BookingCtaSettings::GROUP)),
            'defaultMediaSettings' => DefaultMediaAssets::valuesFromSettings($this->settingsForGroup(DefaultMediaAssets::SETTINGS_GROUP)),
            'structuredDataSettings' => StructuredDataSettings::valuesFromSettings($this->settingsForGroup(StructuredDataSettings::GROUP)),
        ];
    }

    public function settingsForGroup(string $group): Collection
    {
        return $this->settingsByGroup()->get($group, collect());
    }

    public function settingsByGroup(): Collection
    {
        if ($this->settingsByGroup !== null) {
            return $this->settingsByGroup;
        }

        return $this->settingsByGroup = $this->hydrateSettingsPayload($this->settingsPayload());
    }

    public function siteAssets(): Collection
    {
        if ($this->siteAssets !== null) {
            return $this->siteAssets;
        }

        return $this->siteAssets = $this->hydrateAssetsPayload($this->assetsPayload());
    }

    public function forgetSettingsCache(): void
    {
        // Forget the legacy (unsuffixed) key plus every per-locale variant.
        Cache::forget(self::SETTINGS_CACHE_KEY);

        foreach (Locales::activeCodes() as $locale) {
            Cache::forget(self::SETTINGS_CACHE_KEY.'.'.$locale);
        }

        $this->settingsByGroup = null;
        $this->viewData = null;
    }

    public function forgetAssetsCache(): void
    {
        Cache::forget(self::ASSETS_CACHE_KEY);

        $this->siteAssets = null;
        $this->viewData = null;
    }

    public function forgetAll(): void
    {
        $this->forgetSettingsCache();
        $this->forgetAssetsCache();
    }

    private function settingsPayload(): array
    {
        // Cache is keyed per locale (Phase 7 — B2): each locale gets its own
        // already-resolved payload, so the whole downstream pipeline (Support
        // classes + Blade) stays locale-agnostic and unchanged.
        return $this->rememberArrayPayload(
            self::SETTINGS_CACHE_KEY.'.'.app()->getLocale(),
            fn () => $this->buildSettingsPayload()
        );
    }

    private function assetsPayload(): array
    {
        return $this->rememberArrayPayload(
            self::ASSETS_CACHE_KEY,
            fn () => $this->buildAssetsPayload()
        );
    }

    private function rememberArrayPayload(string $key, callable $builder): array
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable $exception) {
            Cache::forget($key);

            Log::warning('Invalid global settings cache payload was discarded.', [
                'cache_key' => $key,
                'exception' => $exception::class,
            ]);

            $cached = null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        if ($cached !== null) {
            Cache::forget($key);

            Log::warning('Legacy global settings cache payload was discarded.', [
                'cache_key' => $key,
                'payload_type' => get_debug_type($cached),
            ]);
        }

        $payload = $builder();

        Cache::put($key, $payload, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $payload;
    }

    private function buildSettingsPayload(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        $locale = app()->getLocale();

        // `id` is required so the polymorphic translations relation resolves;
        // withTranslations() eager-loads only the current locale (N+1 guard).
        return SiteSetting::query()
            ->where('is_active', true)
            ->whereIn('group', self::PUBLIC_SETTING_GROUPS)
            ->withTranslations($locale)
            ->get(['id', 'key', 'label', 'value', 'type', 'group', 'is_active'])
            ->groupBy('group')
            ->map(fn (Collection $settings) => $settings
                ->mapWithKeys(fn (SiteSetting $setting) => [
                    $setting->key => [
                        'key' => $setting->key,
                        'label' => $setting->label,
                        // Resolved for the current locale, falling back to the
                        // default-locale base column when no translation exists.
                        'value' => $setting->translate('value', $locale),
                        'type' => $setting->type,
                        'group' => $setting->group,
                        'is_active' => $setting->is_active,
                    ],
                ])
                ->all())
            ->all();
    }

    private function buildAssetsPayload(): array
    {
        if (! Schema::hasTable('site_assets')) {
            return [];
        }

        return SiteAsset::query()
            ->where('is_active', true)
            ->get(['key', 'label', 'path', 'alt', 'is_active'])
            ->mapWithKeys(fn (SiteAsset $asset) => [
                $asset->key => [
                    'key' => $asset->key,
                    'label' => $asset->label,
                    'path' => $asset->path,
                    'alt' => $asset->alt,
                    'is_active' => $asset->is_active,
                ],
            ])
            ->all();
    }

    private function hydrateSettingsPayload(array $payload): Collection
    {
        return collect($payload)
            ->map(fn (array $settings) => collect($settings)
                ->map(fn (array $attributes) => new SiteSetting($attributes)));
    }

    private function hydrateAssetsPayload(array $payload): Collection
    {
        return collect($payload)
            ->map(fn (array $attributes) => new SiteAsset($attributes));
    }
}
