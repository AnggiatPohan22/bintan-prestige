<?php

namespace App\Providers;

use App\Services\GlobalSettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GlobalSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn ($user) => $user->isSuperAdmin());

        View::composer([
            'frontend.partials.header',
            'frontend.partials.footer',
            'frontend.frontend',
            'layouts.frontend',
            'layouts.admin',
            'layouts.app',
            'layouts.guest',
        ], function ($view) {
            $viewData = $view->getData();
            $globalSettings = null;
            $resolveGlobalSettings = function () use (&$globalSettings): array {
                return $globalSettings ??= app(GlobalSettingsService::class)->viewData();
            };

            foreach ([
                'siteAssets',
                'brandColors',
                'businessIdentity',
                'navigationSettings',
                'footerSettings',
                'seoDefaultSettings',
                'trackingIntegrationSettings',
                'bookingCtaSettings',
                'defaultMediaSettings',
                'structuredDataSettings',
            ] as $key) {
                if (! array_key_exists($key, $viewData)) {
                    $globalSettings = $resolveGlobalSettings();
                    $view->with($key, $globalSettings[$key]);
                }
            }

            if (! array_key_exists('contactInformation', $viewData)) {
                $globalSettings = $resolveGlobalSettings();
                $view->with('contactInformation', $globalSettings['contactInformation']);
                $view->with('contactWhatsappUrl', $globalSettings['contactWhatsappUrl']);
            }

            if (! array_key_exists('socialMediaLinks', $viewData)) {
                $globalSettings = $resolveGlobalSettings();
                $view->with('socialMediaLinks', $globalSettings['socialMediaLinks']);
                $view->with('activeSocialMediaLinks', $globalSettings['activeSocialMediaLinks']);
            }
        });
    }
}
