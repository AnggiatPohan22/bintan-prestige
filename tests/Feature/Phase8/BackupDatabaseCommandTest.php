<?php

namespace Tests\Feature\Phase8;

use Tests\TestCase;

/**
 * Phase 8 (A1) — `db:backup` command surface tests. Real mysqldump execution
 * is covered by the developer running the command against MySQL (documented in
 * `docs/runbooks/`). These tests lock in the input-validation contract so the
 * command can't drift silently.
 */
class BackupDatabaseCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'db:backup',
            $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all(),
        );
    }

    public function test_rejects_invalid_task_id(): void
    {
        $this->artisan('db:backup', ['task_id' => 'BAD ID with spaces'])
            ->expectsOutputToContain('Invalid task id')
            ->assertExitCode(2); // INVALID
    }

    public function test_refuses_non_mysql_default_connection(): void
    {
        // Suite runs on sqlite in-memory — perfect fence for the "mysql only"
        // guard: even if a developer misconfigures the default connection, the
        // command refuses to try and helpfully names the current default.
        $this->artisan('db:backup', ['task_id' => 'x-safety'])
            ->expectsOutputToContain('db:backup only supports the mysql connection')
            ->assertFailed();
    }
}
