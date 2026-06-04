<?php

namespace App\Providers;

use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Support\BrandColorSettings;
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

            if (array_key_exists('brandColors', $view->getData())) {
                return;
            }

            $brandColorSettings = Schema::hasTable('site_settings')
                ? SiteSetting::query()
                    ->where('group', BrandColorSettings::GROUP)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('key')
                : collect();

            $view->with('brandColors', BrandColorSettings::valuesFromSettings($brandColorSettings));
        });
    }
}
