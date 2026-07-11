<?php

namespace Tests\Feature\Phase8;

use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

/**
 * Phase 8 B1 — `backup:snapshot-media` command surface tests. End-to-end
 * pipeline is exercised by BackupServiceMediaTest with a real temp dir.
 */
class BackupSnapshotMediaCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'backup:snapshot-media',
            $this->app->make(Kernel::class)->all(),
        );
    }

    public function test_reports_failure_when_no_source_paths_configured(): void
    {
        // The observable behavior we care about at the command boundary is
        // "did the command exit non-zero?" — the specific error string is
        // asserted at the service boundary in BackupServiceMediaTest.
        config(['backup.media.source_paths' => []]);

        $this->artisan('backup:snapshot-media')->assertFailed();
    }
}
