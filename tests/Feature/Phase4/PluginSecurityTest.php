<?php

namespace Tests\Feature\Phase4;

use App\Exceptions\PluginSecurityException;
use App\Models\Plugin;
use App\Models\User;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginRegistry;
use App\Services\Plugin\PluginScanner;
use FilesystemIterator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class PluginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private PluginScanner $scanner;
    private PluginManager $manager;
    private PluginRegistry $registry;
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner  = $this->app->make(PluginScanner::class);
        $this->registry = $this->app->make(PluginRegistry::class);
        $this->registry->forget();
        $this->manager = $this->app->make(PluginManager::class);
        $this->tmpRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bp_scan_' . uniqid();
        mkdir($this->tmpRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTmpDir($this->tmpRoot);
        parent::tearDown();
    }

    // =========================================================================
    // P1–P4 — PHP token scanner
    // =========================================================================

    /** @test */
    public function test_scanner_blocks_exec_function(): void // P1
    {
        $this->createPluginFile('dangerous-plugin', '<?php exec("ls -la");');

        $this->expectException(PluginSecurityException::class);
        $this->expectExceptionMessageMatches('/exec\(/');

        $this->scanner->scan('dangerous-plugin', $this->tmpRoot);
    }

    /** @test */
    public function test_scanner_blocks_eval_function(): void // P2
    {
        $this->createPluginFile('eval-plugin', '<?php eval($code);');

        $this->expectException(PluginSecurityException::class);
        $this->expectExceptionMessageMatches('/eval\(/');

        $this->scanner->scan('eval-plugin', $this->tmpRoot);
    }

    /** @test */
    public function test_scanner_blocks_shell_exec_function(): void // P3
    {
        $this->createPluginFile('shell-plugin', '<?php return shell_exec("whoami");');

        $this->expectException(PluginSecurityException::class);
        $this->expectExceptionMessageMatches('/shell_exec\(/');

        $this->scanner->scan('shell-plugin', $this->tmpRoot);
    }

    /** @test */
    public function test_scanner_passes_clean_plugin(): void // P4
    {
        $this->createPluginFile('clean-plugin', '<?php class CleanPlugin { public function run(): void {} }');

        $this->expectNotToPerformAssertions();

        $this->scanner->scan('clean-plugin', $this->tmpRoot);
    }

    // =========================================================================
    // P5–P7 — Permission scope validation
    // =========================================================================

    /** @test */
    public function test_unknown_permission_scope_throws_exception(): void // P5
    {
        $this->expectException(PluginSecurityException::class);
        $this->expectExceptionMessageMatches('/destroy:everything/');

        $this->scanner->validatePermissions([
            'slug'        => 'evil-plugin',
            'permissions' => ['read:pages', 'destroy:everything'],
        ]);
    }

    /** @test */
    public function test_valid_permission_scopes_pass_validation(): void // P6
    {
        $this->expectNotToPerformAssertions();

        $this->scanner->validatePermissions([
            'slug'        => 'seo-manager',
            'permissions' => ['read:pages', 'manage:redirects'],
        ]);
    }

    /** @test */
    public function test_missing_permissions_field_passes_validation(): void // P7
    {
        $this->expectNotToPerformAssertions();

        $this->scanner->validatePermissions([
            'slug' => 'minimal-plugin',
        ]);
    }

    // =========================================================================
    // P8–P10 — AuditLog integration
    // =========================================================================

    /** @test */
    public function test_audit_log_created_on_plugin_activated(): void // P8
    {
        $user   = User::factory()->create();
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->actingAs($user);

        $this->manager->activate($plugin);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $user->id,
            'action'         => 'plugin.activated',
            'auditable_type' => 'Plugin',
            'auditable_id'   => $plugin->id,
        ]);
    }

    /** @test */
    public function test_audit_log_created_on_plugin_deactivated(): void // P9
    {
        $user   = User::factory()->create();
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->actingAs($user);

        $this->manager->deactivate($plugin);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $user->id,
            'action'         => 'plugin.deactivated',
            'auditable_type' => 'Plugin',
            'auditable_id'   => $plugin->id,
        ]);
    }

    /** @test */
    public function test_audit_log_created_on_plugin_uninstalled(): void // P10
    {
        $user     = User::factory()->create();
        $plugin   = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);
        $pluginId = $plugin->id;

        $this->actingAs($user);

        $this->manager->uninstall($plugin);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $user->id,
            'action'         => 'plugin.uninstalled',
            'auditable_type' => 'Plugin',
            'auditable_id'   => $pluginId,
        ]);
    }

    // =========================================================================
    // P11–P12 — Exception isolation
    // =========================================================================

    /** @test */
    public function test_boot_survives_multiple_unresolvable_plugins(): void // P11
    {
        Plugin::factory()->active()->create(['slug' => 'ghost-one']);
        Plugin::factory()->active()->create(['slug' => 'ghost-two']);

        $this->expectNotToPerformAssertions();

        $this->registry->forget();
        $this->manager->boot();
    }

    /** @test */
    public function test_scanner_recurses_into_subdirectories(): void // P12
    {
        // Uses a committed fixture file (tests/Fixtures/PluginSecurity/recurse-test/sub/BadClass.php)
        // so the file already exists on disk — avoids Windows/PHPUnit timing issues with
        // file_get_contents on freshly-created files inside newly-created subdirectories.
        $fixtureRoot = base_path('tests') . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'PluginSecurity';

        $this->expectException(PluginSecurityException::class);
        $this->expectExceptionMessageMatches('/passthru\(/');

        $this->scanner->scan('recurse-test', $fixtureRoot);
    }

    // =========================================================================
    // P13 — Scanner no-op for non-existent plugin directory
    // =========================================================================

    /** @test */
    public function test_scanner_is_noop_when_plugin_directory_does_not_exist(): void // P13
    {
        $this->expectNotToPerformAssertions();

        $this->scanner->scan('phantom-plugin', $this->tmpRoot);
    }

    // =========================================================================
    // P14 — Activation blocked when scan fails; plugin stays inactive
    // =========================================================================

    /** @test */
    public function test_activate_blocked_and_plugin_stays_inactive_when_scanner_finds_blocked_function(): void // P14
    {
        $dangerDir = app_path('Plugins/danger-plugin');
        @mkdir($dangerDir, 0777, true);
        file_put_contents($dangerDir . '/Danger.php', '<?php system("rm -rf /");');

        $plugin = Plugin::factory()->inactive()->create(['slug' => 'danger-plugin']);

        try {
            $this->manager->activate($plugin);
            $this->fail('Expected PluginSecurityException was not thrown.');
        } catch (PluginSecurityException $e) {
            $this->assertStringContainsString('system(', $e->getMessage());
        } finally {
            @unlink($dangerDir . '/Danger.php');
            @rmdir($dangerDir);
        }

        $this->assertFalse($plugin->fresh()->is_active);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createPluginFile(string $slug, string $phpContent): void
    {
        $dir = $this->tmpRoot . DIRECTORY_SEPARATOR . $slug;
        mkdir($dir, 0777, true);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'Plugin.php', $phpContent);
    }

    private function removeTmpDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
