<?php

use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\DashboardAppearanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BuilderPatternController;
use App\Http\Controllers\Admin\BuilderTemplateController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DestinationController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FormDefinitionController;
use App\Http\Controllers\Admin\FormSubmissionController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageBlockController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductFaqController;
use App\Http\Controllers\Admin\ProductFeatureController;
use App\Http\Controllers\Admin\ProductHighlightController;
use App\Http\Controllers\Admin\ProductItineraryController;
use App\Http\Controllers\Admin\ProductNoteController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\SeoRobotsController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UiModeController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Admin Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ])->name('dashboard');

        Route::post('media/upload-quick', [MediaController::class, 'uploadQuick'])
            ->name('media.upload-quick');

        Route::post('media/upload-batch', [MediaController::class, 'uploadBatch'])
            ->name('media.upload-batch');

        Route::delete('media/orphans', [MediaController::class, 'purgeOrphans'])
            ->name('media.orphans.destroy');

        Route::resource('media', MediaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['media' => 'media']);

        Route::middleware('can:manage-users')
            ->prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/', [UserManagementController::class, 'index'])
                    ->name('index');

                Route::get('/create', [UserManagementController::class, 'create'])
                    ->name('create');

                Route::post('/', [UserManagementController::class, 'store'])
                    ->name('store');

                Route::get('/{user}/edit', [UserManagementController::class, 'edit'])
                    ->name('edit');

                Route::put('/{user}', [UserManagementController::class, 'update'])
                    ->name('update');

                Route::patch('/{user}/deactivate', [UserManagementController::class, 'deactivate'])
                    ->name('deactivate');
            });

        Route::get('settings/global-assets', [SiteSettingController::class, 'edit'])
            ->name('settings.global-assets.edit');

        Route::put('settings/global-assets/site-logo', [SiteSettingController::class, 'update'])
            ->name('settings.global-assets.site-logo.update');

        Route::delete('settings/global-assets/site-logo/{variant}', [SiteSettingController::class, 'destroyLogo'])
            ->name('settings.global-assets.site-logo.destroy');

        Route::put('settings/global-assets/favicon', [SiteSettingController::class, 'updateFavicon'])
            ->name('settings.global-assets.favicon.update');

        Route::delete('settings/global-assets/favicon', [SiteSettingController::class, 'destroyFavicon'])
            ->name('settings.global-assets.favicon.destroy');

        Route::put('settings/global-assets/brand-colors', [SiteSettingController::class, 'updateBrandColors'])
            ->name('settings.global-assets.brand-colors.update');

        Route::put('settings/global-assets/social-share-image', [SiteSettingController::class, 'updateSocialShareImage'])
            ->name('settings.global-assets.social-share-image.update');

        Route::delete('settings/global-assets/social-share-image', [SiteSettingController::class, 'destroySocialShareImage'])
            ->name('settings.global-assets.social-share-image.destroy');

        Route::put('settings/global-assets/business-identity', [SiteSettingController::class, 'updateBusinessIdentity'])
            ->name('settings.global-assets.business-identity.update');

        Route::put('settings/global-assets/contact-information', [SiteSettingController::class, 'updateContactInformation'])
            ->name('settings.global-assets.contact-information.update');

        Route::put('settings/global-assets/social-media-links', [SiteSettingController::class, 'updateSocialMediaLinks'])
            ->name('settings.global-assets.social-media-links.update');

        Route::put('settings/global-assets/navigation-settings', [SiteSettingController::class, 'updateNavigationSettings'])
            ->name('settings.global-assets.navigation-settings.update');

        Route::put('settings/global-assets/footer-settings', [SiteSettingController::class, 'updateFooterSettings'])
            ->name('settings.global-assets.footer-settings.update');

        Route::put('settings/global-assets/seo-default', [SiteSettingController::class, 'updateSeoDefaultSettings'])
            ->name('settings.global-assets.seo-default.update');

        Route::delete('settings/global-assets/seo-default/og-image', [SiteSettingController::class, 'destroySeoDefaultOgImage'])
            ->name('settings.global-assets.seo-default.og-image.destroy');

        Route::put('settings/global-assets/tracking-integrations', [SiteSettingController::class, 'updateTrackingIntegrations'])
            ->name('settings.global-assets.tracking-integrations.update');

        Route::put('settings/global-assets/booking-cta', [SiteSettingController::class, 'updateBookingCtaSettings'])
            ->name('settings.global-assets.booking-cta.update');

        Route::put('settings/global-assets/default-media', [SiteSettingController::class, 'updateDefaultMediaAssets'])
            ->name('settings.global-assets.default-media.update');

        Route::delete('settings/global-assets/default-media/{variant}', [SiteSettingController::class, 'destroyDefaultMediaAsset'])
            ->name('settings.global-assets.default-media.destroy');

        Route::put('settings/global-assets/structured-data', [SiteSettingController::class, 'updateStructuredDataSettings'])
            ->name('settings.global-assets.structured-data.update');

        /*
        |--------------------------------------------------------------------------
        | Dashboard Appearance — Customize Dashboard (superadmin only)
        |--------------------------------------------------------------------------
        */
        Route::middleware('can:manage-users')
            ->prefix('settings/appearance')
            ->name('settings.appearance.')
            ->group(function () {
                Route::get('/', [DashboardAppearanceController::class, 'index'])
                    ->name('index');
                Route::post('/', [DashboardAppearanceController::class, 'update'])
                    ->name('update');
                Route::post('/reset', [DashboardAppearanceController::class, 'reset'])
                    ->name('reset');
            });

        // Per-user UI mode toggle — available to every authenticated admin user.
        Route::post('settings/ui-mode', [UiModeController::class, 'update'])
            ->name('settings.ui-mode.update');

        Route::get('page-sections', [PageSectionController::class, 'index'])
            ->name('page-sections.index');

        Route::get('page-sections/sections', [PageSectionController::class, 'sections'])
            ->name('page-sections.sections');

        Route::get('page-sections/{pageSection}/edit', [PageSectionController::class, 'edit'])
            ->name('page-sections.edit');

        Route::put('page-sections/{pageSection}', [PageSectionController::class, 'update'])
            ->name('page-sections.update');

        Route::delete('page-section-media/{media}', [PageSectionController::class, 'destroyMedia'])
            ->name('page-sections.media.destroy');

        /*
        |--------------------------------------------------------------------------
        | Themes (Phase 3)
        |--------------------------------------------------------------------------
        */
        Route::get('themes', [ThemeController::class, 'index'])
            ->name('themes.index');

        Route::post('themes/scan', [ThemeController::class, 'scan'])
            ->name('themes.scan');

        Route::post('themes/import', [ThemeController::class, 'import'])
            ->name('themes.import');

        Route::patch('themes/{theme}/activate', [ThemeController::class, 'activate'])
            ->name('themes.activate');

        Route::get('themes/{theme}/export', [ThemeController::class, 'export'])
            ->name('themes.export');

        Route::get('themes/{theme}/customize', [ThemeController::class, 'customize'])
            ->name('themes.customize');

        Route::put('themes/{theme}/customization', [ThemeController::class, 'updateCustomization'])
            ->name('themes.customization.update');

        Route::delete('themes/{theme}/customization', [ThemeController::class, 'resetCustomization'])
            ->name('themes.customization.destroy');

        /*
        |--------------------------------------------------------------------------
        | Widget Manager (Phase 3 — STEP 4)
        |--------------------------------------------------------------------------
        */
        Route::prefix('themes/{theme}/widgets')
            ->name('themes.widgets.')
            ->group(function () {
                Route::get('/', [WidgetController::class, 'index'])->name('index');
                Route::get('/create', [WidgetController::class, 'create'])->name('create');
                Route::post('/', [WidgetController::class, 'store'])->name('store');
                Route::get('{widget}/edit', [WidgetController::class, 'edit'])->name('edit');
                Route::put('{widget}', [WidgetController::class, 'update'])->name('update');
                Route::delete('{widget}', [WidgetController::class, 'destroy'])->name('destroy');
                Route::post('{widget}/toggle-visible', [WidgetController::class, 'toggleVisible'])->name('toggle-visible');
            });

        /*
        |--------------------------------------------------------------------------
        | Pages
        |--------------------------------------------------------------------------
        */
        Route::post('pages/reorder', [PageController::class, 'reorder'])
            ->name('pages.reorder');

        Route::post('pages/{page}/revisions/{revision}/restore', [PageController::class, 'restoreRevision'])
            ->name('pages.revisions.restore');

        Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])
            ->name('pages.duplicate');

        Route::get('pages/{page}/preview', [FrontendPageController::class, 'preview'])
            ->name('pages.preview');

        Route::post('pages/{page}/preview-payload', [FrontendPageController::class, 'previewPayload'])
            ->name('pages.preview-payload');

        Route::get('pages/{page}/builder', [PageBuilderController::class, 'show'])
            ->name('pages.builder');
        Route::post('pages/{page}/builder/templates', [BuilderTemplateController::class, 'store'])
            ->name('builder-templates.store');

        Route::get('builder-templates', [BuilderTemplateController::class, 'index'])
            ->name('builder-templates.index');
        Route::get('builder-templates/{builderTemplate}', [BuilderTemplateController::class, 'show'])
            ->name('builder-templates.show');
        Route::delete('builder-templates/{builderTemplate}', [BuilderTemplateController::class, 'destroy'])
            ->name('builder-templates.destroy');

        Route::get('builder-patterns', [BuilderPatternController::class, 'index'])
            ->name('builder-patterns.index');
        Route::post('builder-patterns', [BuilderPatternController::class, 'store'])
            ->name('builder-patterns.store');
        Route::get('builder-patterns/{builderPattern}', [BuilderPatternController::class, 'show'])
            ->name('builder-patterns.show');
        Route::delete('builder-patterns/{builderPattern}', [BuilderPatternController::class, 'destroy'])
            ->name('builder-patterns.destroy');

        Route::resource('pages', PageController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | Block Type Registry (JSON API for visual builder)
        |--------------------------------------------------------------------------
        */
        Route::get('api/block-types', [PageBlockController::class, 'apiTypes'])
            ->name('api.block-types');

        /*
        |--------------------------------------------------------------------------
        | Page Blocks
        |--------------------------------------------------------------------------
        */
        Route::prefix('pages/{page}/blocks')
            ->name('page-blocks.')
            ->group(function () {
                Route::post('/', [PageBlockController::class, 'store'])->name('store');
                Route::put('{block}', [PageBlockController::class, 'update'])->name('update');
                Route::delete('{block}', [PageBlockController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [PageBlockController::class, 'reorder'])->name('reorder');
                Route::post('{block}/toggle-visible', [PageBlockController::class, 'toggleVisible'])->name('toggle-visible');
                Route::post('save-tree', [PageBuilderController::class, 'saveTree'])->name('save-tree');
            });

        /*
        |--------------------------------------------------------------------------
        | Menus (Menu Manager) — link structure for header/footer
        |--------------------------------------------------------------------------
        */
        Route::get('menus', [MenuController::class, 'index'])
            ->name('menus.index');

        Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])
            ->name('menus.edit');

        Route::prefix('menus/{menu}/items')
            ->name('menu-items.')
            ->group(function () {
                Route::post('/', [MenuItemController::class, 'store'])->name('store');
                Route::put('{item}', [MenuItemController::class, 'update'])->name('update');
                Route::delete('{item}', [MenuItemController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [MenuItemController::class, 'reorder'])->name('reorder');
                Route::post('{item}/toggle-active', [MenuItemController::class, 'toggleActive'])->name('toggle-active');
            });

        Route::resource('faqs', FaqController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | Product Management
        |--------------------------------------------------------------------------
        */
        Route::resource('products',
            ProductController::class)
            ->except(['show']);

        Route::patch(
            'products/{product}/toggle-featured',
            [ProductController::class, 'toggleFeatured']
        )->name('products.toggle-featured');

        Route::patch(
            'products/{product}/toggle-status',
            [ProductController::class, 'toggleStatus']
        )->name('products.toggle-status');

        Route::put(
            'products/{product}/search-booking',
            [ProductController::class, 'updateSearchBooking']
        )->name('products.search-booking.update');

        Route::patch(
            'product-images/{image}/thumbnail',
            [ProductController::class, 'setThumbnailFromImage']
        )->name('products.images.thumbnail');

        Route::delete(
            'product-images/{image}',
            [ProductController::class, 'destroyImage']
        )->name('products.images.destroy');

        Route::delete(
            'products/{product}/thumbnail',
            [ProductController::class, 'destroyThumbnail']
        )->name('products.thumbnail.destroy');

        /*
        |--------------------------------------------------------------------------
        | Product Highlights
        |--------------------------------------------------------------------------
        */
        Route::post(
            'products/{product}/highlights',
            [ProductHighlightController::class, 'store']
        )->name('products.highlights.store');

        Route::put(
            'product-highlights/{highlight}',
            [ProductHighlightController::class, 'update']
        )->name('products.highlights.update');

        Route::delete(
            'product-highlights/{highlight}',
            [ProductHighlightController::class, 'destroy']
        )->name('products.highlights.destroy');

        /*
        |--------------------------------------------------------------------------
        | Product Features
        |--------------------------------------------------------------------------
        */
        Route::post(
            'products/{product}/features',
            [ProductFeatureController::class, 'store']
        )->name('products.features.store');

        Route::put(
            'product-features/{feature}',
            [ProductFeatureController::class, 'update']
        )->name('products.features.update');

        Route::delete(
            'product-features/{feature}',
            [ProductFeatureController::class, 'destroy']
        )->name('products.features.destroy');

        /*
        |--------------------------------------------------------------------------
        | Product FAQs
        |--------------------------------------------------------------------------
        */
        Route::post(
            'products/{product}/faqs',
            [ProductFaqController::class, 'store']
        )->name('products.faqs.store');

        Route::put(
            'product-faqs/{faq}',
            [ProductFaqController::class, 'update']
        )->name('products.faqs.update');

        Route::delete(
            'product-faqs/{faq}',
            [ProductFaqController::class, 'destroy']
        )->name('products.faqs.destroy');

        /*
        |--------------------------------------------------------------------------
        | Product Itineraries
        |--------------------------------------------------------------------------
        */
        Route::post(
            'products/{product}/itineraries',
            [ProductItineraryController::class, 'store']
        )->name('products.itineraries.store');

        Route::put(
            'product-itineraries/{itinerary}',
            [ProductItineraryController::class, 'update']
        )->name('products.itineraries.update');

        Route::delete(
            'product-itineraries/{itinerary}',
            [ProductItineraryController::class, 'destroy']
        )->name('products.itineraries.destroy');

        /*
        |--------------------------------------------------------------------------
        | Product Notes
        |--------------------------------------------------------------------------
        */
        Route::post(
            'products/{product}/notes',
            [ProductNoteController::class, 'store']
        )->name('products.notes.store');

        Route::put(
            'product-notes/{note}',
            [ProductNoteController::class, 'update']
        )->name('products.notes.update');

        Route::delete(
            'product-notes/{note}',
            [ProductNoteController::class, 'destroy']
        )->name('products.notes.destroy');

        /*
        |--------------------------------------------------------------------------
        | Category Management
        | CRUD, archive restore, and permanent delete for product categories.
        |--------------------------------------------------------------------------
        */
        Route::patch(
            'categories/{category}/restore',
            [CategoryController::class, 'restore']
        )->name('categories.restore');

        Route::delete(
            'categories/{category}/force-delete',
            [CategoryController::class, 'forceDelete']
        )->name('categories.force-delete');

        Route::resource('categories',
            CategoryController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | Destination Management
        | CRUD, archive restore, and permanent delete for travel destinations.
        |--------------------------------------------------------------------------
        */
        Route::patch(
            'destinations/{destination}/restore',
            [DestinationController::class, 'restore']
        )->name('destinations.restore');

        Route::delete(
            'destinations/{destination}/force-delete',
            [DestinationController::class, 'forceDelete']
        )->name('destinations.force-delete');

        Route::resource('destinations',
            DestinationController::class)
            ->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | Contact Form Builder (Phase 4 — STEP 6)
        |--------------------------------------------------------------------------
        */
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('/', [FormDefinitionController::class, 'index'])->name('index');
            Route::get('/create', [FormDefinitionController::class, 'create'])->name('create');
            Route::post('/', [FormDefinitionController::class, 'store'])->name('store');
            Route::get('/{form}/edit', [FormDefinitionController::class, 'edit'])->name('edit');
            Route::put('/{form}', [FormDefinitionController::class, 'update'])->name('update');
            Route::delete('/{form}', [FormDefinitionController::class, 'destroy'])->name('destroy');
            Route::get('/{form}/submissions', [FormSubmissionController::class, 'index'])->name('submissions.index');
        });

        Route::prefix('form-submissions')->name('form-submissions.')->group(function () {
            Route::patch('/{submission}/read', [FormSubmissionController::class, 'markRead'])->name('read');
            Route::delete('/{submission}', [FormSubmissionController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Analytics Dashboard (Phase 4 — STEP 7)
        |--------------------------------------------------------------------------
        */
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('index');
            Route::get('/export', [AnalyticsDashboardController::class, 'exportCsv'])->name('export');
        });

        /*
        |--------------------------------------------------------------------------
        | SEO Manager (Phase 4 — STEP 5)
        |--------------------------------------------------------------------------
        */
        Route::prefix('seo')->name('seo.')->group(function () {
            Route::resource('redirects', RedirectController::class)
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::get('robots', [SeoRobotsController::class, 'edit'])->name('robots.edit');
            Route::put('robots', [SeoRobotsController::class, 'update'])->name('robots.update');
        });

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        /*
        |--------------------------------------------------------------------------
        | Plugin Manager (Phase 4)
        |--------------------------------------------------------------------------
        */
        Route::prefix('plugins')->name('plugins.')->group(function () {
            Route::get('/', [PluginController::class, 'index'])->name('index');
            Route::post('/scan', [PluginController::class, 'scan'])->name('scan');
            Route::get('/{plugin}', [PluginController::class, 'show'])->name('show');
            Route::patch('/{plugin}/activate', [PluginController::class, 'activate'])->name('activate');
            Route::patch('/{plugin}/deactivate', [PluginController::class, 'deactivate'])->name('deactivate');
            Route::delete('/{plugin}', [PluginController::class, 'destroy'])->name('destroy');
        });
    });
