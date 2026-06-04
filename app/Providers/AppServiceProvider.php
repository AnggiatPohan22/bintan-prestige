<?php

namespace App\Providers;

use App\Models\SiteAsset;
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
        View::composer(['frontend.partials.header', 'frontend.partials.footer'], function ($view) {
            if (array_key_exists('siteAssets', $view->getData())) {
                return;
            }

            $view->with('siteAssets', SiteAsset::query()
                ->where('is_active', true)
                ->get()
                ->keyBy('key'));
        });
    }
}
