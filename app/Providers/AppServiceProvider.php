<?php

namespace App\Providers;

use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Support\BrandColorSettings;
use App\Support\BookingCtaSettings;
use App\Support\BusinessIdentitySettings;
use App\Support\ContactInformationSettings;
use App\Support\FooterSettings;
use App\Support\NavigationSettings;
use App\Support\SeoDefaultSettings;
use App\Support\SocialMediaLinkSettings;
use App\Support\TrackingIntegrationSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'frontend.partials.header',
            'frontend.partials.footer',
            'frontend.frontend',
            'layouts.frontend',
            'layouts.admin',
            'layouts.app',
            'layouts.guest',
        ], function ($view) {
            if (! array_key_exists('siteAssets', $view->getData())) {
                $view->with('siteAssets', SiteAsset::query()
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('key'));
            }

            $siteSettingsTableExists = Schema::hasTable('site_settings');

            if (! array_key_exists('brandColors', $view->getData())) {
                $brandColorSettings = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', BrandColorSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('brandColors', BrandColorSettings::valuesFromSettings($brandColorSettings));
            }

            if (! array_key_exists('businessIdentity', $view->getData())) {
                $identitySettings = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', BusinessIdentitySettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('businessIdentity', BusinessIdentitySettings::valuesFromSettings($identitySettings));
            }

            if (! array_key_exists('contactInformation', $view->getData())) {
                $contactSettings = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', ContactInformationSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $contactInformation = ContactInformationSettings::valuesFromSettings($contactSettings);

                $view->with('contactInformation', $contactInformation);
                $view->with('contactWhatsappUrl', ContactInformationSettings::whatsappUrl($contactInformation));
            }

            if (! array_key_exists('socialMediaLinks', $view->getData())) {
                $socialSettings = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', SocialMediaLinkSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $socialMediaLinks = SocialMediaLinkSettings::valuesFromSettings($socialSettings);

                $view->with('socialMediaLinks', $socialMediaLinks);
                $view->with('activeSocialMediaLinks', SocialMediaLinkSettings::activeLinks($socialMediaLinks));
            }

            if (! array_key_exists('navigationSettings', $view->getData())) {
                $navigationRows = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', NavigationSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('navigationSettings', NavigationSettings::valuesFromSettings($navigationRows));
            }

            if (! array_key_exists('footerSettings', $view->getData())) {
                $footerRows = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', FooterSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('footerSettings', FooterSettings::valuesFromSettings($footerRows));
            }

            if (! array_key_exists('seoDefaultSettings', $view->getData())) {
                $seoRows = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', SeoDefaultSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('seoDefaultSettings', SeoDefaultSettings::valuesFromSettings($seoRows));
            }

            if (! array_key_exists('trackingIntegrationSettings', $view->getData())) {
                $trackingRows = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', TrackingIntegrationSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('trackingIntegrationSettings', TrackingIntegrationSettings::valuesFromSettings($trackingRows));
            }

            if (! array_key_exists('bookingCtaSettings', $view->getData())) {
                $bookingCtaRows = $siteSettingsTableExists
                    ? SiteSetting::query()
                        ->where('group', BookingCtaSettings::GROUP)
                        ->where('is_active', true)
                        ->get()
                        ->keyBy('key')
                    : collect();

                $view->with('bookingCtaSettings', BookingCtaSettings::valuesFromSettings($bookingCtaRows));
            }
        });
    }
}
