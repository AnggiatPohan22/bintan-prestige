<?php

namespace App\Providers;

use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Support\BrandColorSettings;
use App\Support\BusinessIdentitySettings;
use App\Support\ContactInformationSettings;
use App\Support\NavigationSettings;
use App\Support\SocialMediaLinkSettings;
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
        });
    }
}
