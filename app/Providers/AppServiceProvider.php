<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Services\GlobalSettingsService;
use App\Services\MenuService;
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

        $forgetMenus = fn () => app(MenuService::class)->forget();

        foreach ([Menu::class, MenuItem::class, Page::class, Product::class, Category::class, Destination::class] as $model) {
            $model::saved($forgetMenus);
            $model::deleted($forgetMenus);
        }

        Category::restored($forgetMenus);
        Destination::restored($forgetMenus);

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

            // Menu Manager trees (single source of truth for header/footer links).
            // Empty arrays fall back to the legacy Global Assets settings in the views.
            $menuSources = null;
            $resolveMenuSources = function () use (&$menuSources): array {
                return $menuSources ??= app(MenuService::class)->sources();
            };

            foreach ([
                'headerMenu' => ['header', 'headerMenuManaged'],
                'footerQuickLinks' => ['footer_quick', 'footerQuickLinksManaged'],
                'footerUtilityLinks' => ['footer_utility', 'footerUtilityLinksManaged'],
            ] as $key => [$location, $managedKey]) {
                $hasExplicitMenu = array_key_exists($key, $viewData);
                $source = null;

                if (! $hasExplicitMenu) {
                    $source = $resolveMenuSources()[$location] ?? ['managed' => false, 'items' => []];
                    $view->with($key, $source['items']);
                }

                if (! array_key_exists($managedKey, $viewData)) {
                    $view->with($managedKey, $hasExplicitMenu ? true : (bool) ($source['managed'] ?? false));
                }
            }
        });
    }
}
