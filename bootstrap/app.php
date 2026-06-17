<?php

use App\Console\Commands\ProvisionFirstAdmin;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {

            Route::middleware('web')
                ->group(
                    base_path('routes/frontend.php')
                );

            Route::middleware('web')
                ->group(
                    base_path('routes/admin.php')
                );
        }
    )
    ->withCommands([
        ProvisionFirstAdmin::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
