<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Artisan wrapper around `scripts/backup-db.sh` — cross-platform mysqldump
 * helper enforcing the AGENTS.md §8/§13 naming convention. Use this from
 * migrations, deploy scripts, or ad-hoc:
 *
 *   php artisan db:backup {task-id} [--purpose="short desc"]
 *
 * Output: storage/app/db-backups/pre-{task-id}-YYYYMMDD-HHMMSS.sql
 * Exits non-zero on failure so upstream tooling can fail loudly.
 */
class BackupDatabase extends Command
{
    /** @var string */
    protected $signature = 'db:backup
                            {task_id : Short task identifier (e.g. b6.1-product-children)}
                            {--purpose= : Optional one-line description saved to a companion .meta file}';

    /** @var string */
    protected $description = 'Take a mysqldump of the configured MySQL database with the enforced naming convention.';

    public function handle(): int
    {
        $taskId = (string) $this->argument('task_id');

        if (! preg_match('/^[a-z0-9][a-z0-9._-]{0,80}$/i', $taskId)) {
            $this->error("Invalid task id: {$taskId}. Use lower-case alphanumerics + . _ -.");

            return self::INVALID;
        }

        if (config('database.default') !== 'mysql') {
            $this->error('db:backup only supports the mysql connection. Current default: '
                .config('database.default'));

            return self::FAILURE;
        }

        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port', '3306');
        $user = (string) config('database.connections.mysql.username');
        $db   = (string) config('database.connections.mysql.database');
        $pass = (string) config('database.connections.mysql.password', '');

        if ($db === '') {
            $this->error('No database configured under database.connections.mysql.database.');

            return self::FAILURE;
        }

        $bin = $this->resolveMysqldump();
        if ($bin === null) {
            $this->error('mysqldump not found on PATH or in known Laragon locations.');
            $this->line('  Install MySQL client tools or extend BackupDatabase::resolveMysqldump().');

            return self::FAILURE;
        }

        $outDir = Storage::disk('local')->path('db-backups');
        if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
            $this->error("Could not create backup dir: {$outDir}");

            return self::FAILURE;
        }

        $ts       = date('Ymd-His');
        $filename = "pre-{$taskId}-{$ts}.sql";
        $outPath  = $outDir.DIRECTORY_SEPARATOR.$filename;

        $args = [
            $bin,
            '-h'.$host,
            '-P'.$port,
            '-u'.$user,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--skip-lock-tables',
            '--databases', $db,
            '--result-file='.$outPath,
        ];

        // Pass password via MYSQL_PWD env so it never lands in the process list.
        $env = null;
        if ($pass !== '') {
            $env = ['MYSQL_PWD' => $pass];
        }

        $this->info("→ Backing up {$db} to storage/app/db-backups/{$filename}");

        $process = new Process($args, null, $env, null, 600);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            @unlink($outPath); // don't leave partial dumps around

            $this->error('mysqldump failed:');
            $this->line($process->getErrorOutput() ?: $e->getMessage());

            return self::FAILURE;
        }

        // Trust-but-verify: valid dumps start with the MySQL header and are
        // bigger than 1 KB. A silent 0-byte failure is the exact kind of gotcha
        // this whole rule set exists to catch.
        if (! is_file($outPath)) {
            $this->error("Backup file was not written: {$outPath}");

            return self::FAILURE;
        }

        $header = (string) file_get_contents($outPath, false, null, 0, 200);
        if (! str_contains($header, 'MySQL dump')) {
            @unlink($outPath);
            $this->error('Backup file has no MySQL header — refusing to trust it.');

            return self::FAILURE;
        }

        $bytes = (int) filesize($outPath);
        if ($bytes < 1024) {
            @unlink($outPath);
            $this->error("Backup file suspiciously small ({$bytes} bytes). Aborting.");

            return self::FAILURE;
        }

        $purpose = (string) ($this->option('purpose') ?? '');
        if ($purpose !== '') {
            file_put_contents(
                $outPath.'.meta',
                "task_id: {$taskId}\ncreated_at: {$ts}\npurpose: {$purpose}\ndb: {$db}\nsize_bytes: {$bytes}\n"
            );
        }

        $this->info("✓ Backup OK: {$filename} ({$bytes} bytes)");

        $this->warnStaleBackups($outDir);

        return self::SUCCESS;
    }

    /**
     * Look for mysqldump on PATH first, then fall back to well-known Laragon
     * install paths. Extend the list here when the dev environment changes.
     */
    private function resolveMysqldump(): ?string
    {
        $onPath = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'command -v mysqldump 2>/dev/null'));
        if ($onPath !== '' && is_executable(explode("\n", $onPath)[0])) {
            return trim(explode("\n", $onPath)[0]);
        }

        $candidates = [
            'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe',
            'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** Print a note when there are backups older than 90 days (AGENTS.md §13 retention). */
    private function warnStaleBackups(string $outDir): void
    {
        $threshold = time() - (90 * 86400);
        $stale = 0;
        foreach (glob($outDir.DIRECTORY_SEPARATOR.'pre-*.sql') ?: [] as $file) {
            if (filemtime($file) < $threshold) {
                $stale++;
            }
        }

        if ($stale > 0) {
            $this->line("NOTE: {$stale} backup(s) older than 90 days in db-backups/ — review + archive.");
        }
    }
}
