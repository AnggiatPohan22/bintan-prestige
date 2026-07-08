<?php

use App\Console\Commands\AggregatePageViewStats;
use App\Console\Commands\ProvisionFirstAdmin;
use App\Console\Commands\PublishScheduledContentEntries;
use App\Console\Commands\PublishScheduledPages;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleRedirects;
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
        PublishScheduledPages::class,
        PublishScheduledContentEntries::class,
        AggregatePageViewStats::class,
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('pages:publish-scheduled')->everyMinute();
        $schedule->command('content-entries:publish-scheduled')->everyMinute();
        $schedule->command('analytics:aggregate-daily')->dailyAt('00:05');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
        $middleware->append(HandleRedirects::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
