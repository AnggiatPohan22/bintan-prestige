<?php

namespace App\Support;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Phase 8 A1 — three artisan-lifecycle guards that make the 2026-07-07
 * dev-DB wipe (Phase 6 §16) structurally impossible to repeat.
 *
 *   G1 — refuse `migrate:fresh` / `migrate:reset` / `db:wipe` on a protected
 *        driver (MySQL/MariaDB) unless the operator opts in via env.
 *   G2 — auto-invoke `db:backup` right before `migrate` on a protected
 *        driver; abort `migrate` if the snapshot fails.
 *   G3 — warn (do not block) when a cached config is detected in local env,
 *        because that cache was the vector behind the 2026-07-07 wipe.
 *
 * All logic is here (pure, testable). The provider wire-up only listens for
 * CommandStarting and forwards to `handle()`.
 */
class DestructiveCommandGuard
{
    /** Flag to keep the G3 warning to one line per artisan invocation. */
    private bool $configCacheWarned = false;

    public function __construct(
        private Repository $config,
        private Kernel $artisan,
        private Application $app,
    ) {
    }

    public function handle(CommandStarting $event): void
    {
        $output = $event->output;

        // G3 first — visible even if G1/G2 short-circuit.
        $this->warnConfigCacheIfLocal($output);

        $command = (string) $event->command;
        if ($command === '') {
            return; // e.g. bare `php artisan` with no args
        }

        // G1 — destructive-command block on protected drivers.
        if ($this->isDestructiveCommand($command) && $this->onProtectedDriver()) {
            if (! $this->destructiveAllowed()) {
                throw new RuntimeException($this->destructiveBlockedMessage($command));
            }
        }

        // G2 — auto pre-migrate backup on protected drivers.
        if ($command === $this->autoBackupTrigger()
            && $this->onProtectedDriver()
            && $this->autoBackupBeforeMigrateEnabled()
            && ! $this->skipAutoBackupRequested()) {
            $this->runAutoBackup($output);
        }
    }

    /* ---------- G1 helpers ---------- */

    private function isDestructiveCommand(string $command): bool
    {
        $destructive = (array) $this->config->get('safety.destructive_commands', []);

        return in_array($command, $destructive, true);
    }

    private function destructiveAllowed(): bool
    {
        return (bool) $this->config->get('safety.destructive_allowed', false);
    }

    private function destructiveBlockedMessage(string $command): string
    {
        $driver = $this->currentDriver();

        return <<<MSG

            SAFETY GUARD (Phase 8 A1 / G1) — refused to run `{$command}` on the
            {$driver} connection. This is the exact class of command that wiped
            the dev database on 2026-07-07 (see Phase 6 §16 and AGENTS.md §8).

            If you are ABSOLUTELY sure this is what you want:

                APP_ALLOW_DESTRUCTIVE=true php artisan {$command}

            Prefer `php artisan migrate:rollback --step=<n>` or restoring from
            a backup under storage/app/db-backups/ instead.
            MSG;
    }

    /* ---------- G2 helpers ---------- */

    private function autoBackupTrigger(): string
    {
        return (string) $this->config->get('safety.auto_backup_trigger_command', 'migrate');
    }

    private function autoBackupBeforeMigrateEnabled(): bool
    {
        return (bool) $this->config->get('safety.auto_backup_before_migrate', true);
    }

    private function skipAutoBackupRequested(): bool
    {
        return (bool) $this->config->get('safety.skip_auto_backup', false);
    }

    private function runAutoBackup(?OutputInterface $output): void
    {
        $taskId  = 'pre-migrate-'.date('Ymd-His');
        $purpose = 'Auto pre-migrate snapshot (Phase 8 A1 / G2)';

        if ($output instanceof OutputInterface) {
            $output->writeln("<info>[safety-guard]</info> Taking auto pre-migrate snapshot: <comment>{$taskId}</comment>");
        }

        $exitCode = $this->artisan->call(
            'db:backup',
            ['task_id' => $taskId, '--purpose' => $purpose],
            $output,
        );

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'SAFETY GUARD (Phase 8 A1 / G2) — auto pre-migrate `db:backup` failed with exit code '
                .$exitCode.'. Aborting `migrate` because we no longer have a safety net. Take a manual '
                .'backup or set APP_SKIP_AUTO_BACKUP=true only if you have JUST taken one.'
            );
        }
    }

    /* ---------- G3 helpers ---------- */

    private function warnConfigCacheIfLocal(?OutputInterface $output): void
    {
        if ($this->configCacheWarned) {
            return;
        }

        if (! $this->app->environment('local')) {
            return;
        }

        $cachedConfigPath = $this->app->getCachedConfigPath();
        if (! is_file($cachedConfigPath)) {
            return;
        }

        $this->configCacheWarned = true;

        if (! $output instanceof OutputInterface) {
            return;
        }

        $output->writeln('<comment>[safety-guard G3] Config cache detected in local env — this is the exact vector behind the 2026-07-07 dev DB wipe. Run `php artisan optimize:clear` before continuing.</comment>');
    }

    /* ---------- Shared helpers ---------- */

    private function onProtectedDriver(): bool
    {
        $protected = (array) $this->config->get('safety.protected_drivers', ['mysql', 'mariadb']);
        $driver    = $this->currentDriver();

        return in_array($driver, $protected, true);
    }

    private function currentDriver(): string
    {
        $default = (string) $this->config->get('database.default');

        return (string) $this->config->get("database.connections.{$default}.driver");
    }
}
