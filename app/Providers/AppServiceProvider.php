<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Theme;
use App\Models\Widget;
use App\Observers\ContentEntryObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Observers\ProductObserver;
use App\Observers\RedirectObserver;
use App\Observers\ThemeObserver;
use App\Facades\CmsHooks;
use App\Services\AdminAppearanceService;
use App\Services\GlobalSettingsService;
use App\Services\MenuService;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginRegistry;
use App\Services\ThemeService;
use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\MysqldumpRunner;
use App\Support\DestructiveCommandGuard;
use App\Support\HookManager;
use App\View\Composers\AdminAppearanceComposer;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(AdminAppearanceService::class);
        $this->app->singleton(GlobalSettingsService::class);
        $this->app->singleton(ThemeService::class);
        $this->app->singleton(HookManager::class);
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(PluginManager::class);

        // Phase 8 A2 — bind the default DatabaseDumper implementation.
        // Tests bind a fake so BackupService can run without real mysqldump.
        $this->app->bind(DatabaseDumper::class, MysqldumpRunner::class);

        // Boot active plugin providers so they participate in the full boot cycle.
        $this->app->make(PluginManager::class)->boot();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phase 8 A1 — destructive-command guards (G1/G2/G3). Kept in a
        // dedicated support class so the logic is unit-testable without
        // needing to boot artisan; the listener here is a thin forwarder.
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $this->app->make(DestructiveCommandGuard::class)->handle($event);
        });

        Gate::define('manage-users', fn ($user) => $user->isSuperAdmin());

        ContentEntry::observe(ContentEntryObserver::class);
        Page::observe(PageObserver::class);
        Product::observe(ProductObserver::class);
        Menu::observe(MenuObserver::class);
        Theme::observe(ThemeObserver::class);
        Redirect::observe(RedirectObserver::class);

        $forgetMenus = fn () => app(MenuService::class)->forget();

        foreach ([Menu::class, MenuItem::class, Page::class, Product::class, Category::class, Destination::class] as $model) {
            $model::saved($forgetMenus);
            $model::deleted($forgetMenus);
        }

        Category::restored($forgetMenus);
        Destination::restored($forgetMenus);

        $forgetTheme = fn () => app(ThemeService::class)->forget();

        Theme::saved($forgetTheme);
        Theme::deleted($forgetTheme);

        Widget::saved($forgetTheme);
        Widget::deleted($forgetTheme);

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

        // Inject admin appearance (CSS vars + mode attrs) into every admin layout render.
        // backend.media.picker renders standalone inside the picker iframe and needs
        // the same vars to follow the dashboard light/dark theme.
        View::composer(['layouts.admin', 'backend.media.picker'], AdminAppearanceComposer::class);

        CmsHooks::doAction('cms.init');
    }
}
