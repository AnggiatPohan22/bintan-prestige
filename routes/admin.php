<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DestinationController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductFaqController;
use App\Http\Controllers\Admin\ProductFeatureController;
use App\Http\Controllers\Admin\ProductHighlightController;
use App\Http\Controllers\Admin\ProductItineraryController;
use App\Http\Controllers\Admin\ProductNoteController;
use App\Http\Controllers\Admin\SiteSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
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
            'index'
        ])->name('dashboard');

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

        Route::get('page-sections', [PageSectionController::class, 'index'])
            ->name('page-sections.index');

        Route::get('page-sections/{pageSection}/edit', [PageSectionController::class, 'edit'])
            ->name('page-sections.edit');

        Route::put('page-sections/{pageSection}', [PageSectionController::class, 'update'])
            ->name('page-sections.update');

        Route::delete('page-section-media/{media}', [PageSectionController::class, 'destroyMedia'])
            ->name('page-sections.media.destroy');

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
    });
