<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use App\Http\Controllers\Frontend\ProductController as FrontendProductController;
use Illuminate\Support\Facades\Route;

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
)->name('pages.show');
