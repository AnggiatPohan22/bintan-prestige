<?php

namespace Tests\Feature\Phase8;

use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

/**
 * Phase 8 A2 — `backup:snapshot` command surface tests. Real BackupService
 * end-to-end is exercised by BackupServiceTest with a stub dumper; this test
 * pins the input-validation contract so the command can't drift silently
 * (mirrors `BackupDatabaseCommandTest`).
 */
class BackupSnapshotCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'backup:snapshot',
            $this->app->make(Kernel::class)->all(),
        );
    }

    public function test_refuses_non_mysql_default_connection(): void
    {
        // Suite runs on sqlite by default — the command must refuse cleanly
        // instead of trying to shell out to mysqldump against nothing.
        $this->artisan('backup:snapshot')
            ->expectsOutputToContain('backup:snapshot only supports the mysql connection')
            ->assertFailed();
    }
}
