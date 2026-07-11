<?php

namespace App\Services\Backup;

use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * Default DatabaseDumper implementation — shells out to `mysqldump`.
 * Mirrors the argument set of `App\Console\Commands\BackupDatabase` so
 * the two backup paths produce comparable dumps.
 */
class MysqldumpRunner implements DatabaseDumper
{
    private string $lastErrorOutput = '';

    public function dump(array $connection, string $outputSqlPath, int $timeoutSeconds): int
    {
        $bin = $this->resolveMysqldump();
        if ($bin === null) {
            $this->lastErrorOutput = 'mysqldump not found on PATH or known Laragon paths.';

            return 127; // convention: "command not found"
        }

        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $user = (string) ($connection['username'] ?? '');
        $db   = (string) ($connection['database'] ?? '');
        $pass = (string) ($connection['password'] ?? '');

        if ($db === '') {
            $this->lastErrorOutput = 'connection.database is empty.';

            return 2;
        }

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
            '--result-file='.$outputSqlPath,
        ];

        $env = $pass !== '' ? ['MYSQL_PWD' => $pass] : null;

        $process = new Process($args, null, $env, null, $timeoutSeconds);
        try {
            $process->run();
        } catch (ExceptionInterface $e) {
            $this->lastErrorOutput = $e->getMessage();

            return 1;
        }

        $this->lastErrorOutput = trim((string) $process->getErrorOutput());

        return (int) $process->getExitCode();
    }

    public function lastErrorOutput(): string
    {
        return $this->lastErrorOutput;
    }

    /**
     * Locate mysqldump. Mirrors `BackupDatabase::resolveMysqldump` — kept
     * in sync intentionally so the two entry points stay predictable.
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
}
