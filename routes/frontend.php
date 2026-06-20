<?php

use App\Http\Controllers\Frontend\ContactFormController;
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
