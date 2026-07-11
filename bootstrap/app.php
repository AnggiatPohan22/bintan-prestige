<?php

use App\Console\Commands\AggregatePageViewStats;
use App\Console\Commands\BackupDatabase;
use App\Console\Commands\BackupSnapshot;
use App\Console\Commands\BackupSnapshotMedia;
use App\Console\Commands\BackupSyncOffsite;
use App\Console\Commands\ProvisionFirstAdmin;
use App\Console\Commands\PublishScheduledContentEntries;
use App\Console\Commands\PublishScheduledPages;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {

            // Phase 7 (A2) — localized frontend routes.
            // Register each active NON-DEFAULT locale first, under a /{code}
            // prefix with `{code}.`-prefixed route names. Registering them
            // before the bare default group is deliberate: Route::fallback (B11)
            // uses a `.*` pattern, so the default's bare fallback would also
            // match `/{locale}/...`; putting the prefixed fallback earlier lets
            // it win for localized paths, while the bare fallback still wins for
            // default-locale paths. Canonical (unprefixed) route names stay on
            // the default group.
            foreach (Locales::nonDefaultActive() as $locale) {
                Route::middleware(['web', SetLocale::class.':'.$locale])
                    ->prefix($locale)
                    ->name($locale.'.')
                    ->group(base_path('routes/frontend.php'));
            }

            Route::middleware(['web', SetLocale::class.':'.Locales::default()])
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
        BackupDatabase::class,
        BackupSnapshot::class,
        BackupSnapshotMedia::class,
        BackupSyncOffsite::class,
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('pages:publish-scheduled')->everyMinute();
        $schedule->command('content-entries:publish-scheduled')->everyMinute();
        $schedule->command('analytics:aggregate-daily')->dailyAt('00:05');

        // Phase 8 A2 — daily DB snapshot with retention prune. Time is
        // config-tunable via BACKUP_DB_DAILY_AT (defaults to 03:00 WIB).
        $schedule->command('backup:snapshot --purpose="Scheduled daily"')
            ->dailyAt((string) config('backup.schedule.db_daily_at', '03:00'))
            ->timezone((string) config('app.timezone', 'UTC'))
            ->withoutOverlapping()
            ->onOneServer();

        // Phase 8 B1 — weekly media snapshot with dedup + retention prune.
        // Day + time are config-tunable via BACKUP_MEDIA_WEEKLY_DAY (0=Sun)
        // + BACKUP_MEDIA_WEEKLY_AT (defaults to Sun 04:00 WIB).
        $schedule->command('backup:snapshot-media --purpose="Scheduled weekly"')
            ->weeklyOn(
                (int) config('backup.schedule.media_weekly_day', 0),
                (string) config('backup.schedule.media_weekly_at', '04:00'),
            )
            ->timezone((string) config('app.timezone', 'UTC'))
            ->withoutOverlapping()
            ->onOneServer();
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
