<?php

namespace App\Services\Backup;

/**
 * Contract for running a database dump. The default binding is
 * `MysqldumpRunner`; tests bind a fake so BackupService can be exercised
 * without a real MySQL / mysqldump on the box.
 */
interface DatabaseDumper
{
    /**
     * Write a full SQL dump of $connection to $outputSqlPath (uncompressed).
     * Return the process exit code (0 = success). Implementations must NOT
     * throw on non-zero exit — return the code so BackupService can decide.
     *
     * @param  array<string, mixed>  $connection  Laravel database.connections.<name> array
     */
    public function dump(array $connection, string $outputSqlPath, int $timeoutSeconds): int;

    /**
     * Error output from the last dump call, for logging into `meta`.
     * Returns empty string if there was none / no dump has been called yet.
     */
    public function lastErrorOutput(): string;
}
