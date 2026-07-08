<?php

use App\Http\Controllers\Frontend\ContactFormController;
use App\Http\Controllers\Frontend\ContentEntryController as FrontendContentEntryController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use App\Http\Controllers\Frontend\ProductController as FrontendProductController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Middleware\TrackPageView;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');

Route::get(
    '/',
    [HomeController::class, 'index']
)->name('home');

Route::get(
    '/products',
    [FrontendProductController::class, 'index']
)->name('products.index');

Route::get(
    '/products/{product:slug}',
    [FrontendProductController::class, 'show']
)->name('products.show');

Route::get(
    '/pages/{page:slug}',
    [FrontendPageController::class, 'show']
)->name('pages.show')->middleware(TrackPageView::class);

Route::post(
    '/forms/{form:slug}/submit',
    [ContactFormController::class, 'submit']
)->name('forms.submit');

/*
|--------------------------------------------------------------------------
| Content Entry public routing (Phase 6 — B11)
|--------------------------------------------------------------------------
| Registered as a fallback so it is ALWAYS the lowest-priority match: every
| explicit route above (and all admin routes) wins first. The controller maps
| /{route_base} → archive and /{route_base}/{slug} → single, resolving the
| content type by its unique, reserved-prefix-guarded route_base. Unmatched
| paths still 404 (thrown from the controller), preserving prior behaviour.
*/
Route::fallback([FrontendContentEntryController::class, 'resolve']);
