<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Services\PageSectionImageService;
use App\Support\BrandColorSettings;
use App\Support\BookingCtaSettings;
use App\Support\BusinessIdentitySettings;
use App\Support\ContactInformationSettings;
use App\Support\DefaultMediaAssets;
use App\Support\FooterSettings;
use App\Support\HomepageSectionMedia;
use App\Support\NavigationSettings;
use App\Support\SeoDefaultSettings;
use App\Support\SocialMediaLinkSettings;
use App\Support\StructuredDataSettings;
use App\Support\TrackingIntegrationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SiteSettingController extends Controller
{
    public function __construct(protected PageSectionImageService $imageService) {}

    public function edit()
    {
        $activeTab = request('tab', 'site-logo');

        if (! in_array($activeTab, array_column($this->assetTabs(), 'key'), true)) {
            $activeTab = 'site-logo';
        }

        $assetTabs = $this->assetTabs();
        $logoVariants = $this->logoVariants();
        $siteAssets = SiteAsset::query()
            ->whereIn('key', [
                ...array_column($logoVariants, 'key'),
                $this->faviconConfig()['key'],
                $this->socialShareConfig()['key'],
                SeoDefaultSettings::OG_IMAGE_KEY,
                ...DefaultMediaAssets::keys(),
            ])
            ->get()
            ->keyBy('key');
        $siteLogos = $siteAssets->filter(fn ($asset, string $key) => in_array($key, array_column($logoVariants, 'key'), true));
        $favicon = $siteAssets[$this->faviconConfig()['key']] ?? null;
        $faviconConfig = $this->faviconConfig();
        $socialShareImage = $siteAssets[$this->socialShareConfig()['key']] ?? null;
        $socialShareConfig = $this->socialShareConfig();
        $seoDefaultOgImage = $siteAssets[SeoDefaultSettings::OG_IMAGE_KEY] ?? null;
        $defaultMediaVariants = DefaultMediaAssets::variants();
        $defaultMediaAssets = $siteAssets->filter(fn ($asset, string $key) => in_array($key, DefaultMediaAssets::keys(), true));
        $defaultMediaSettingsRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', DefaultMediaAssets::SETTINGS_GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $defaultMediaSettings = DefaultMediaAssets::valuesFromSettings($defaultMediaSettingsRows);
        $brandColorFields = BrandColorSettings::fields();
        $brandColorSettings = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->whereIn('key', array_column($brandColorFields, 'key'))
                ->get()
                ->keyBy('key')
            : collect();
        $brandColors = BrandColorSettings::valuesFromSettings($brandColorSettings);
        $businessIdentityFields = BusinessIdentitySettings::fields();
        $businessIdentitySettings = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->whereIn('key', array_column($businessIdentityFields, 'key'))
                ->get()
                ->keyBy('key')
            : collect();
        $businessIdentity = BusinessIdentitySettings::valuesFromSettings($businessIdentitySettings);
        $contactInformationFields = ContactInformationSettings::fields();
        $contactInformationSettings = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->whereIn('key', array_column($contactInformationFields, 'key'))
                ->get()
                ->keyBy('key')
            : collect();
        $contactInformation = ContactInformationSettings::valuesFromSettings($contactInformationSettings);
        $socialMediaLinkFields = SocialMediaLinkSettings::fields();
        $socialMediaLinkSettings = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->whereIn('key', array_column($socialMediaLinkFields, 'key'))
                ->get()
                ->keyBy('key')
            : collect();
        $socialMediaLinks = SocialMediaLinkSettings::valuesFromSettings($socialMediaLinkSettings);
        $navigationFields = NavigationSettings::fields();
        $navigationSettingsRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', NavigationSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $navigationSettings = NavigationSettings::valuesFromSettings($navigationSettingsRows);
        $footerFields = FooterSettings::fields();
        $footerSettingsRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', FooterSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $footerSettings = FooterSettings::valuesFromSettings($footerSettingsRows);
        $seoDefaultFields = SeoDefaultSettings::fields();
        $seoDefaultSettingsRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', SeoDefaultSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $seoDefaultSettings = SeoDefaultSettings::valuesFromSettings($seoDefaultSettingsRows);
        $trackingIntegrationFields = TrackingIntegrationSettings::fields();
        $trackingIntegrationRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', TrackingIntegrationSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $trackingIntegrationSettings = TrackingIntegrationSettings::valuesFromSettings($trackingIntegrationRows);
        $bookingCtaFields = BookingCtaSettings::fields();
        $bookingCtaRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', BookingCtaSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $bookingCtaSettings = BookingCtaSettings::valuesFromSettings($bookingCtaRows);
        $structuredDataFields = StructuredDataSettings::fields();
        $structuredDataRows = Schema::hasTable('site_settings')
            ? SiteSetting::query()
                ->where('group', StructuredDataSettings::GROUP)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
            : collect();
        $structuredDataSettings = StructuredDataSettings::valuesFromSettings($structuredDataRows);

        return view('backend.settings.global-assets', compact('activeTab', 'assetTabs', 'logoVariants', 'siteLogos', 'favicon', 'faviconConfig', 'socialShareImage', 'socialShareConfig', 'seoDefaultOgImage', 'defaultMediaVariants', 'defaultMediaAssets', 'defaultMediaSettings', 'brandColorFields', 'brandColors', 'businessIdentityFields', 'businessIdentity', 'contactInformationFields', 'contactInformation', 'socialMediaLinkFields', 'socialMediaLinks', 'navigationFields', 'navigationSettings', 'footerFields', 'footerSettings', 'seoDefaultFields', 'seoDefaultSettings', 'trackingIntegrationFields', 'trackingIntegrationSettings', 'bookingCtaFields', 'bookingCtaSettings', 'structuredDataFields', 'structuredDataSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'logos' => ['nullable', 'array'],
            'logos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'logo_alts' => ['nullable', 'array'],
            'logo_alts.*' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->logoVariants() as $variant) {
            $slug = $variant['slug'];
            $alt = $validated['logo_alts'][$slug] ?? null;

            if ($request->hasFile("logos.$slug")) {
                $this->imageService->storeSiteAssetUpload(
                    $request->file("logos.$slug"),
                    $variant['key'],
                    $variant['label'],
                    $alt
                );

                continue;
            }

            if ($alt !== null) {
                SiteAsset::query()
                    ->where('key', $variant['key'])
                    ->first()
                    ?->update(['alt' => $alt]);
            }
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'site-logo'])
            ->with('success', 'Logo variants updated successfully.');
    }

    public function destroyLogo(string $variant)
    {
        $variantConfig = collect($this->logoVariants())->firstWhere('slug', $variant);

        abort_unless($variantConfig, 404);

        $asset = SiteAsset::query()
            ->where('key', $variantConfig['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'site-logo'])
            ->with('success', $variantConfig['label'] . ' deleted successfully.');
    }

    public function updateFavicon(Request $request)
    {
        $validated = $request->validate([
            'favicon' => ['required', 'file', 'mimes:ico,png,svg,webp,jpg,jpeg', 'extensions:ico,png,svg,webp,jpg,jpeg', 'max:1024'],
            'favicon_alt' => ['nullable', 'string', 'max:255'],
        ]);

        $favicon = $this->faviconConfig();

        $this->imageService->storeSiteAssetUpload(
            $request->file('favicon'),
            $favicon['key'],
            $favicon['label'],
            $validated['favicon_alt'] ?? null
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'favicon'])
            ->with('success', 'Browser favicon updated successfully.');
    }

    public function destroyFavicon()
    {
        $asset = SiteAsset::query()
            ->where('key', $this->faviconConfig()['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'favicon'])
            ->with('success', 'Browser favicon deleted successfully.');
    }

    public function updateBrandColors(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'brand-colors'])
                ->withErrors(['brand_colors' => 'Please run database migrations before updating brand colors.']);
        }

        $rules = collect(BrandColorSettings::fields())
            ->mapWithKeys(fn (array $field) => [
                'brand_colors.' . $field['slug'] => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ])
            ->all();

        $validated = $request->validate($rules);
        $colors = $validated['brand_colors'] ?? [];

        foreach (BrandColorSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $colors[$field['slug']],
                    'type' => 'color',
                    'group' => BrandColorSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'brand-colors'])
            ->with('success', 'Brand colors updated successfully.');
    }

    public function updateSocialShareImage(Request $request)
    {
        $validated = $request->validate([
            'social_share_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
            'social_share_image_alt' => ['nullable', 'string', 'max:255'],
        ]);

        $asset = $this->socialShareConfig();

        $this->imageService->storeSiteAssetUpload(
            $request->file('social_share_image'),
            $asset['key'],
            $asset['label'],
            $validated['social_share_image_alt'] ?? null
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'social-share-image'])
            ->with('success', 'Default social share image updated successfully.');
    }

    public function destroySocialShareImage()
    {
        $asset = SiteAsset::query()
            ->where('key', $this->socialShareConfig()['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'social-share-image'])
            ->with('success', 'Default social share image deleted successfully.');
    }

    public function updateBusinessIdentity(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'business-identity'])
                ->withErrors(['business_identity' => 'Please run database migrations before updating business identity.']);
        }

        $rules = collect(BusinessIdentitySettings::fields())
            ->mapWithKeys(fn (array $field) => [
                'business_identity.' . $field['slug'] => ['nullable', 'string', 'max:' . ($field['type'] === 'textarea' ? 1000 : 255)],
            ])
            ->all();

        $validated = $request->validate($rules);
        $identity = $validated['business_identity'] ?? [];

        foreach (BusinessIdentitySettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $identity[$field['slug']] ?? null,
                    'type' => $field['type'],
                    'group' => BusinessIdentitySettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'business-identity'])
            ->with('success', 'Business identity updated successfully.');
    }

    public function updateContactInformation(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'contact-information'])
                ->withErrors(['contact_information' => 'Please run database migrations before updating contact information.']);
        }

        $rules = collect(ContactInformationSettings::fields())
            ->mapWithKeys(function (array $field) {
                $max = $field['type'] === 'textarea' ? 1000 : 500;
                $rules = ['nullable', 'string', 'max:' . $max];

                if ($field['type'] === 'email') {
                    $rules[] = 'email';
                }

                if ($field['type'] === 'url') {
                    $rules[] = 'url';
                }

                return ['contact_information.' . $field['slug'] => $rules];
            })
            ->all();

        $validated = $request->validate($rules);
        $contactInformation = $validated['contact_information'] ?? [];

        foreach (ContactInformationSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $contactInformation[$field['slug']] ?? null,
                    'type' => $field['type'],
                    'group' => ContactInformationSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'contact-information'])
            ->with('success', 'Contact information updated successfully.');
    }

    public function updateSocialMediaLinks(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'social-media-links'])
                ->withErrors(['social_media_links' => 'Please run database migrations before updating social media links.']);
        }

        $rules = collect(SocialMediaLinkSettings::fields())
            ->mapWithKeys(fn (array $field) => [
                'social_media_links.' . $field['slug'] => ['nullable', 'url', 'max:500'],
            ])
            ->all();
        $rules['custom_social_links'] = ['nullable', 'array'];
        $rules['custom_social_links.*.label'] = ['nullable', 'string', 'max:100'];
        $rules['custom_social_links.*.abbr'] = ['nullable', 'string', 'max:8'];
        $rules['custom_social_links.*.url'] = ['nullable', 'url', 'max:500'];

        $validated = $request->validate($rules);
        $links = $validated['social_media_links'] ?? [];
        $customLinks = collect($validated['custom_social_links'] ?? [])
            ->map(fn (array $link) => [
                'label' => trim($link['label'] ?? ''),
                'abbr' => trim($link['abbr'] ?? ''),
                'url' => trim($link['url'] ?? ''),
            ])
            ->filter(fn (array $link) => $link['label'] !== '' && $link['url'] !== '')
            ->values()
            ->all();

        foreach (SocialMediaLinkSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'] . ' URL',
                    'value' => $links[$field['slug']] ?? null,
                    'type' => 'url',
                    'group' => SocialMediaLinkSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        SiteSetting::updateOrCreate(
            ['key' => SocialMediaLinkSettings::CUSTOM_LINKS_KEY],
            [
                'label' => 'Custom social media links',
                'value' => json_encode($customLinks),
                'type' => 'json',
                'group' => SocialMediaLinkSettings::GROUP,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'social-media-links'])
            ->with('success', 'Social media links updated successfully.');
    }

    public function updateNavigationSettings(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'navigation-settings'])
                ->withErrors(['navigation_settings' => 'Please run database migrations before updating navigation settings.']);
        }

        $navigationSettingRules = collect(NavigationSettings::fields())
            ->mapWithKeys(function (array $field) {
                $rules = match ($field['type']) {
                    'boolean' => ['nullable', 'boolean'],
                    'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                    default => ['nullable', 'string', 'max:500'],
                };

                if ($field['slug'] === 'cta_label') {
                    $rules = ['nullable', 'string', 'max:100'];
                }

                return ['navigation_settings.' . $field['slug'] => $rules];
            })
            ->all();

        $validated = $request->validate([
            ...$navigationSettingRules,
            'navigation_items' => ['nullable', 'array'],
            'navigation_items.*.label' => ['nullable', 'string', 'max:80'],
            'navigation_items.*.url' => ['nullable', 'string', 'max:500'],
            'navigation_items.*.is_external' => ['nullable', 'boolean'],
            'navigation_items.*.children' => ['nullable', 'array'],
            'navigation_items.*.children.*.label' => ['nullable', 'string', 'max:80'],
            'navigation_items.*.children.*.url' => ['nullable', 'string', 'max:500'],
            'navigation_items.*.children.*.is_external' => ['nullable', 'boolean'],
        ]);

        $settings = $validated['navigation_settings'] ?? [];
        $items = NavigationSettings::normalizeItems($validated['navigation_items'] ?? []);

        foreach (NavigationSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['slug'] === 'is_sticky'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => NavigationSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        SiteSetting::updateOrCreate(
            ['key' => NavigationSettings::ITEMS_KEY],
            [
                'label' => 'Header navigation items',
                'value' => json_encode($items !== [] ? $items : NavigationSettings::defaultItems()),
                'type' => 'json',
                'group' => NavigationSettings::GROUP,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'navigation-settings'])
            ->with('success', 'Header navigation settings updated successfully.');
    }

    public function updateFooterSettings(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'footer-settings'])
                ->withErrors(['footer_settings' => 'Please run database migrations before updating footer settings.']);
        }

        $footerSettingRules = collect(FooterSettings::fields())
            ->mapWithKeys(function (array $field) {
                $rules = match ($field['type']) {
                    'boolean' => ['nullable', 'boolean'],
                    'select' => ['required', 'in:' . implode(',', array_keys($field['options']))],
                    default => ['nullable', 'string', 'max:255'],
                };

                return ['footer_settings.' . $field['slug'] => $rules];
            })
            ->all();

        $validated = $request->validate([
            ...$footerSettingRules,
            'footer_quick_links' => ['nullable', 'array'],
            'footer_quick_links.*.label' => ['nullable', 'string', 'max:80'],
            'footer_quick_links.*.url' => ['nullable', 'string', 'max:500'],
            'footer_quick_links.*.is_external' => ['nullable', 'boolean'],
            'footer_utility_links' => ['nullable', 'array'],
            'footer_utility_links.*.label' => ['nullable', 'string', 'max:80'],
            'footer_utility_links.*.url' => ['nullable', 'string', 'max:500'],
            'footer_utility_links.*.is_external' => ['nullable', 'boolean'],
            'footer_layout_blocks' => ['nullable', 'array'],
            'footer_layout_blocks.*.type' => ['nullable', 'string', 'in:' . implode(',', array_keys(FooterSettings::blockTypes()))],
            'footer_layout_blocks.*.title' => ['nullable', 'string', 'max:80'],
            'footer_layout_blocks.*.width' => ['nullable', 'string', 'in:' . implode(',', array_keys(FooterSettings::widthOptions()))],
            'footer_layout_blocks.*.is_active' => ['nullable', 'boolean'],
            'footer_layout_blocks.*.settings' => ['nullable', 'array'],
            'footer_layout_blocks.*.settings.maps_embed_url' => ['nullable', 'string', 'max:1000'],
            'footer_layout_blocks.*.settings.custom_body' => ['nullable', 'string', 'max:1500'],
        ]);

        $settings = $validated['footer_settings'] ?? [];
        $quickLinks = FooterSettings::normalizeLinks($validated['footer_quick_links'] ?? []);
        $utilityLinks = FooterSettings::normalizeLinks($validated['footer_utility_links'] ?? []);
        $layoutBlocks = FooterSettings::normalizeLayoutBlocks($validated['footer_layout_blocks'] ?? []);

        if (FooterSettings::layoutWidthTotal($layoutBlocks) > 3) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'footer-settings'])
                ->withInput()
                ->withErrors(['footer_layout_blocks' => 'Footer layout can only use up to 3 active columns. Disable another block or reduce a block width before saving.']);
        }

        foreach (FooterSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['type'] === 'boolean'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => FooterSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        SiteSetting::updateOrCreate(
            ['key' => FooterSettings::QUICK_LINKS_KEY],
            [
                'label' => 'Footer quick links',
                'value' => json_encode($quickLinks !== [] ? $quickLinks : FooterSettings::defaultQuickLinks()),
                'type' => 'json',
                'group' => FooterSettings::GROUP,
                'is_active' => true,
            ]
        );

        SiteSetting::updateOrCreate(
            ['key' => FooterSettings::UTILITY_LINKS_KEY],
            [
                'label' => 'Footer utility links',
                'value' => json_encode($utilityLinks !== [] ? $utilityLinks : FooterSettings::defaultUtilityLinks()),
                'type' => 'json',
                'group' => FooterSettings::GROUP,
                'is_active' => true,
            ]
        );

        SiteSetting::updateOrCreate(
            ['key' => FooterSettings::LAYOUT_BLOCKS_KEY],
            [
                'label' => 'Footer layout blocks',
                'value' => json_encode($layoutBlocks !== [] ? $layoutBlocks : FooterSettings::defaultLayoutBlocks()),
                'type' => 'json',
                'group' => FooterSettings::GROUP,
                'is_active' => true,
            ]
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'footer-settings'])
            ->with('success', 'Footer settings updated successfully.');
    }

    public function updateSeoDefaultSettings(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'seo-default'])
                ->withErrors(['seo_default' => 'Please run database migrations before updating SEO defaults.']);
        }

        $rules = [];

        foreach (SeoDefaultSettings::fields() as $field) {
            $rules['seo_default.' . $field['slug']] = match ($field['type']) {
                'boolean' => ['nullable', 'boolean'],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                'url' => ['nullable', 'url', 'max:500'],
                'textarea' => ['nullable', 'string', 'max:1000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $rules['seo_default_og_image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'];

        $validated = $request->validate($rules);
        $settings = $validated['seo_default'] ?? [];

        foreach (SeoDefaultSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['type'] === 'boolean'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => SeoDefaultSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        if ($request->hasFile('seo_default_og_image')) {
            $this->imageService->storeSiteAssetUpload(
                $request->file('seo_default_og_image'),
                SeoDefaultSettings::OG_IMAGE_KEY,
                'Default SEO OG image',
                $settings['og_image_alt'] ?? null
            );
        } elseif (array_key_exists('og_image_alt', $settings)) {
            SiteAsset::query()
                ->where('key', SeoDefaultSettings::OG_IMAGE_KEY)
                ->first()
                ?->update(['alt' => $settings['og_image_alt']]);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'seo-default'])
            ->with('success', 'SEO defaults updated successfully.');
    }

    public function destroySeoDefaultOgImage()
    {
        $asset = SiteAsset::query()
            ->where('key', SeoDefaultSettings::OG_IMAGE_KEY)
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'seo-default'])
            ->with('success', 'Default SEO OG image deleted successfully.');
    }

    public function updateTrackingIntegrations(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'tracking-integrations'])
                ->withErrors(['tracking_integrations' => 'Please run database migrations before updating tracking integrations.']);
        }

        $rules = [];

        foreach (TrackingIntegrationSettings::fields() as $field) {
            $rules['tracking_integrations.' . $field['slug']] = match ($field['type']) {
                'boolean' => ['nullable', 'boolean'],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                'textarea' => ['nullable', 'string', 'max:10000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);
        $settings = $validated['tracking_integrations'] ?? [];

        foreach (TrackingIntegrationSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['type'] === 'boolean'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => TrackingIntegrationSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'tracking-integrations'])
            ->with('success', 'Tracking integrations updated successfully.');
    }

    public function updateBookingCtaSettings(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'booking-cta'])
                ->withErrors(['booking_cta' => 'Please run database migrations before updating booking CTA settings.']);
        }

        $rules = [];

        foreach (BookingCtaSettings::fields() as $field) {
            $rules['booking_cta.' . $field['slug']] = match ($field['type']) {
                'boolean' => ['nullable', 'boolean'],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                'textarea' => ['nullable', 'string', 'max:1500'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);
        $settings = $validated['booking_cta'] ?? [];

        foreach (BookingCtaSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['type'] === 'boolean'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => BookingCtaSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'booking-cta'])
            ->with('success', 'Booking CTA settings updated successfully.');
    }

    public function updateDefaultMediaAssets(Request $request)
    {
        $validated = $request->validate([
            'default_media' => ['nullable', 'array'],
            'default_media.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
            'default_media_alts' => ['nullable', 'array'],
            'default_media_alts.*' => ['nullable', 'string', 'max:255'],
            'default_media_fits' => ['nullable', 'array'],
            'default_media_fits.*' => ['nullable', Rule::in(array_keys(DefaultMediaAssets::fitOptions()))],
        ]);

        foreach (DefaultMediaAssets::variants() as $variant) {
            $slug = $variant['slug'];
            $alt = $validated['default_media_alts'][$slug] ?? null;
            $fit = $validated['default_media_fits'][$slug] ?? DefaultMediaAssets::defaultFit($slug);

            if (Schema::hasTable('site_settings')) {
                SiteSetting::updateOrCreate(
                    ['key' => DefaultMediaAssets::fitKey($slug)],
                    [
                        'label' => $variant['label'] . ' fit',
                        'value' => $fit,
                        'type' => 'select',
                        'group' => DefaultMediaAssets::SETTINGS_GROUP,
                        'is_active' => true,
                    ]
                );
            }

            if ($request->hasFile("default_media.$slug")) {
                $this->imageService->storeSiteAssetUpload(
                    $request->file("default_media.$slug"),
                    $variant['key'],
                    $variant['label'],
                    $alt
                );

                continue;
            }

            if ($alt !== null) {
                SiteAsset::query()
                    ->where('key', $variant['key'])
                    ->first()
                    ?->update(['alt' => $alt]);
            }
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'default-media'])
            ->with('success', 'Default media placeholder assets updated successfully.');
    }

    public function destroyDefaultMediaAsset(string $variant)
    {
        $variantConfig = DefaultMediaAssets::variantForSlug($variant);

        abort_unless($variantConfig, 404);

        $asset = SiteAsset::query()
            ->where('key', $variantConfig['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'default-media'])
            ->with('success', $variantConfig['label'] . ' deleted successfully.');
    }

    public function updateStructuredDataSettings(Request $request)
    {
        if (! Schema::hasTable('site_settings')) {
            return redirect()
                ->route('admin.settings.global-assets.edit', ['tab' => 'structured-data'])
                ->withErrors(['structured_data' => 'Please run database migrations before updating structured data settings.']);
        }

        $rules = [];

        foreach (StructuredDataSettings::fields() as $field) {
            $rules['structured_data.' . $field['slug']] = match ($field['type']) {
                'boolean' => ['nullable', 'boolean'],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                'textarea' => ['nullable', 'string', 'max:1500'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);
        $settings = $validated['structured_data'] ?? [];

        foreach (StructuredDataSettings::fields() as $field) {
            SiteSetting::updateOrCreate(
                ['key' => $field['key']],
                [
                    'label' => $field['label'],
                    'value' => $field['type'] === 'boolean'
                        ? (string) (int) (bool) ($settings[$field['slug']] ?? false)
                        : ($settings[$field['slug']] ?? $field['default']),
                    'type' => $field['type'],
                    'group' => StructuredDataSettings::GROUP,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'structured-data'])
            ->with('success', 'Structured data settings updated successfully.');
    }

    private function assetTabs(): array
    {
        return [
            [
                'key' => 'site-logo',
                'label' => 'Site Logo',
                'description' => 'Logo variants for header, footer, sections, and compact placements.',
            ],
            [
                'key' => 'favicon',
                'label' => 'Browser Favicon',
                'description' => 'Browser tab, bookmark, and shortcut icon.',
            ],
            [
                'key' => 'brand-colors',
                'label' => 'Brand Colors',
                'description' => 'Global frontend palette for brand surfaces, text, and CTAs.',
            ],
            [
                'key' => 'social-share-image',
                'label' => 'Social Share Image',
                'description' => 'Default image for WhatsApp, Facebook, X, LinkedIn, and other link previews.',
            ],
            [
                'key' => 'business-identity',
                'label' => 'Business Identity',
                'description' => 'Global brand name, legal name, tagline, description, business type, and footer identity.',
            ],
            [
                'key' => 'contact-information',
                'label' => 'Contact Information',
                'description' => 'Global email, phone, WhatsApp, address, maps URL, and opening hours.',
            ],
            [
                'key' => 'social-media-links',
                'label' => 'Social Media Links',
                'description' => 'Global social profile URLs rendered in footer and future menus.',
            ],
            [
                'key' => 'navigation-settings',
                'label' => 'Header Navigation',
                'description' => 'Global header menu items, CTA link, and sticky behavior for the public frontend.',
            ],
            [
                'key' => 'footer-settings',
                'label' => 'Footer Settings',
                'description' => 'Footer logo source, footer menus, display controls, and global contact/social rendering.',
            ],
            [
                'key' => 'seo-default',
                'label' => 'SEO Default',
                'description' => 'Global fallback metadata, canonical base URL, social preview, and schema controls.',
            ],
            [
                'key' => 'tracking-integrations',
                'label' => 'Tracking / Integrations',
                'description' => 'Analytics, pixels, site verification, custom scripts, and WhatsApp CTA tracking.',
            ],
            [
                'key' => 'booking-cta',
                'label' => 'Booking / CTA',
                'description' => 'Global booking labels, WhatsApp message templates, and CTA placement rules.',
            ],
            [
                'key' => 'default-media',
                'label' => 'Default Media',
                'description' => 'Global placeholder images for products, destinations, sections, heroes, and avatars.',
            ],
            [
                'key' => 'structured-data',
                'label' => 'Structured Data',
                'description' => 'Business schema, website schema, breadcrumbs, and product/tour JSON-LD controls.',
            ],
        ];
    }

    private function logoVariants(): array
    {
        return [
            [
                'slug' => 'main',
                'key' => HomepageSectionMedia::SITE_LOGO_KEY,
                'label' => 'Main website logo',
                'hint' => 'Default logo used when no specific variant is available.',
            ],
            [
                'slug' => 'dark',
                'key' => 'site.logo.dark',
                'label' => 'Dark logo',
                'hint' => 'Logo for light backgrounds, such as the main header.',
            ],
            [
                'slug' => 'light',
                'key' => 'site.logo.light',
                'label' => 'Light logo',
                'hint' => 'Logo for dark backgrounds, such as the footer.',
            ],
            [
                'slug' => 'icon',
                'key' => 'site.logo.icon',
                'label' => 'Icon logo',
                'hint' => 'Compact mark for small spaces, favicon, or future mobile UI.',
            ],
        ];
    }

    private function faviconConfig(): array
    {
        return [
            'key' => 'site.favicon',
            'label' => 'Browser favicon',
            'hint' => 'Icon shown in browser tabs, bookmarks, and shortcut previews.',
        ];
    }

    private function socialShareConfig(): array
    {
        return [
            'key' => 'site.social_share.default_image',
            'label' => 'Default social share image',
            'hint' => 'Fallback image used by social link previews when a page or post does not provide its own share image.',
        ];
    }
}
