<?php

namespace Tests\Feature\Phase8;

use App\Support\DestructiveCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;

/**
 * Phase 8 A1 — regression fences for the destructive-command guards.
 * The scenarios directly exercise `DestructiveCommandGuard::handle` because
 * the actual CommandStarting event fires against sqlite in the suite (which
 * the guard deliberately ignores).
 */
class DestructiveCommandGuardTest extends TestCase
{
    use MockeryPHPUnitIntegration;


    private function makeEvent(string $command, ?OutputInterface $output = null): CommandStarting
    {
        return new CommandStarting(
            $command,
            new ArrayInput([]),
            $output ?? new BufferedOutput(),
        );
    }

    private function makeGuard(?Kernel $kernel = null, ?Application $app = null): DestructiveCommandGuard
    {
        /** @var Repository $config */
        $config = $this->app->make('config');

        return new DestructiveCommandGuard(
            $config,
            $kernel ?? Mockery::mock(Kernel::class),
            $app ?? $this->app,
        );
    }

    private function withMysqlDefault(): void
    {
        config([
            'database.default'                  => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
        ]);
    }

    /* -------------------- G1 -------------------- */

    public function test_g1_blocks_migrate_fresh_on_mysql_without_override(): void
    {
        $this->withMysqlDefault();
        config(['safety.destructive_allowed' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("refused to run `migrate:fresh`");

        $this->makeGuard()->handle($this->makeEvent('migrate:fresh'));
    }

    public function test_g1_blocks_migrate_reset_on_mysql(): void
    {
        $this->withMysqlDefault();
        config(['safety.destructive_allowed' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("refused to run `migrate:reset`");

        $this->makeGuard()->handle($this->makeEvent('migrate:reset'));
    }

    public function test_g1_blocks_db_wipe_on_mysql(): void
    {
        $this->withMysqlDefault();
        config(['safety.destructive_allowed' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("refused to run `db:wipe`");

        $this->makeGuard()->handle($this->makeEvent('db:wipe'));
    }

    public function test_g1_allows_migrate_fresh_on_mysql_when_env_override_is_set(): void
    {
        $this->withMysqlDefault();
        config(['safety.destructive_allowed' => true]);

        // No exception: guard returns normally, letting the command through.
        $this->makeGuard()->handle($this->makeEvent('migrate:fresh'));
        $this->assertTrue(true, 'guard did not throw when override was set');
    }

    public function test_g1_does_not_block_on_sqlite(): void
    {
        // Default suite driver is sqlite; guard must be inert against it.
        config(['safety.destructive_allowed' => false]);

        $this->makeGuard()->handle($this->makeEvent('migrate:fresh'));
        $this->assertTrue(true, 'guard did not throw on sqlite');
    }

    /* -------------------- G2 -------------------- */

    public function test_g2_invokes_db_backup_before_migrate_on_mysql(): void
    {
        $this->withMysqlDefault();
        config([
            'safety.auto_backup_before_migrate' => true,
            'safety.skip_auto_backup'           => false,
        ]);

        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldReceive('call')
            ->once()
            ->withArgs(function (string $command, array $params, $output): bool {
                return $command === 'db:backup'
                    && isset($params['task_id'])
                    && str_starts_with((string) $params['task_id'], 'pre-migrate-')
                    && isset($params['--purpose'])
                    && str_contains((string) $params['--purpose'], 'Auto pre-migrate');
            })
            ->andReturn(0);

        $this->makeGuard($kernel)->handle($this->makeEvent('migrate'));
    }

    public function test_g2_skips_db_backup_when_skip_env_is_set(): void
    {
        $this->withMysqlDefault();
        config([
            'safety.auto_backup_before_migrate' => true,
            'safety.skip_auto_backup'           => true,
        ]);

        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldNotReceive('call');

        $this->makeGuard($kernel)->handle($this->makeEvent('migrate'));
    }

    public function test_g2_skips_db_backup_when_master_toggle_is_off(): void
    {
        $this->withMysqlDefault();
        config([
            'safety.auto_backup_before_migrate' => false,
            'safety.skip_auto_backup'           => false,
        ]);

        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldNotReceive('call');

        $this->makeGuard($kernel)->handle($this->makeEvent('migrate'));
    }

    public function test_g2_aborts_migrate_when_auto_backup_fails(): void
    {
        $this->withMysqlDefault();
        config([
            'safety.auto_backup_before_migrate' => true,
            'safety.skip_auto_backup'           => false,
        ]);

        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldReceive('call')->once()->andReturn(1); // non-zero = failure

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('auto pre-migrate `db:backup` failed');

        $this->makeGuard($kernel)->handle($this->makeEvent('migrate'));
    }

    public function test_g2_does_not_run_on_sqlite(): void
    {
        config([
            'safety.auto_backup_before_migrate' => true,
            'safety.skip_auto_backup'           => false,
        ]);

        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldNotReceive('call');

        $this->makeGuard($kernel)->handle($this->makeEvent('migrate'));
    }

    /* -------------------- G3 -------------------- */

    public function test_g3_warns_when_config_cache_exists_in_local_env(): void
    {
        $tempCache = tempnam(sys_get_temp_dir(), 'phpunit-safety-guard-config-');
        file_put_contents($tempCache, '<?php return [];');

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('environment')->with('local')->andReturn(true);
        $app->shouldReceive('getCachedConfigPath')->andReturn($tempCache);

        $output = new BufferedOutput();
        $event  = $this->makeEvent('route:list', $output);

        $this->makeGuard(null, $app)->handle($event);

        $this->assertStringContainsString('[safety-guard G3]', $output->fetch());

        @unlink($tempCache);
    }

    public function test_g3_silent_when_not_in_local_env(): void
    {
        $tempCache = tempnam(sys_get_temp_dir(), 'phpunit-safety-guard-config-');
        file_put_contents($tempCache, '<?php return [];');

        $app = Mockery::mock(Application::class);
        // 'testing' env: environment('local') → false
        $app->shouldReceive('environment')->with('local')->andReturn(false);
        $app->shouldNotReceive('getCachedConfigPath'); // short-circuits before this

        $output = new BufferedOutput();
        $event  = $this->makeEvent('route:list', $output);

        $this->makeGuard(null, $app)->handle($event);

        $this->assertStringNotContainsString('safety-guard', $output->fetch());

        @unlink($tempCache);
    }

    public function test_g3_silent_when_config_cache_does_not_exist(): void
    {
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('environment')->with('local')->andReturn(true);
        $app->shouldReceive('getCachedConfigPath')->andReturn(
            sys_get_temp_dir().'/phpunit-safety-guard-no-such-file-'.uniqid()
        );

        $output = new BufferedOutput();
        $event  = $this->makeEvent('route:list', $output);

        $this->makeGuard(null, $app)->handle($event);

        $this->assertStringNotContainsString('safety-guard', $output->fetch());
    }

    /* -------------------- Housekeeping -------------------- */

    public function test_guard_ignores_empty_command_string(): void
    {
        // Bare `php artisan` with no subcommand — event.command may be empty.
        $this->makeGuard()->handle($this->makeEvent(''));
        $this->assertTrue(true, 'guard did not throw on empty command');
    }
}
