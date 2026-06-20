<?php

namespace Tests\Feature\Phase4;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\User;
use App\Services\Plugin\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginAdminInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        app(PluginRegistry::class)->forget();
    }

    // =========================================================================
    // J1–J2 — index
    // =========================================================================

    /** @test */
    public function test_index_returns_200_for_authenticated_admin(): void // J1
    {
        $this->actingAs($this->admin)
            ->get(route('admin.plugins.index'))
            ->assertOk();
    }

    /** @test */
    public function test_index_lists_all_plugins(): void // J2
    {
        Plugin::factory()->active()->create(['name' => 'SEO Manager', 'slug' => 'seo-manager']);
        Plugin::factory()->inactive()->create(['name' => 'Contact Form', 'slug' => 'contact-form']);

        $this->actingAs($this->admin)
            ->get(route('admin.plugins.index'))
            ->assertOk()
            ->assertSee('SEO Manager')
            ->assertSee('Contact Form');
    }

    // =========================================================================
    // J3 — scan
    // =========================================================================

    /** @test */
    public function test_scan_redirects_to_index_after_sync(): void // J3
    {
        $this->actingAs($this->admin)
            ->post(route('admin.plugins.scan'))
            ->assertRedirect(route('admin.plugins.index'));
    }

    // =========================================================================
    // J4 — show
    // =========================================================================

    /** @test */
    public function test_show_returns_200_for_a_known_plugin(): void // J4
    {
        $plugin = Plugin::factory()->inactive()->create([
            'name'        => 'Analytics',
            'slug'        => 'analytics',
            'description' => 'Page view tracking.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.plugins.show', $plugin))
            ->assertOk()
            ->assertSee('Analytics')
            ->assertSee('Page view tracking.');
    }

    // =========================================================================
    // J5–J7 — activate
    // =========================================================================

    /** @test */
    public function test_activate_sets_plugin_active(): void // J5
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.activate', $plugin));

        $this->assertTrue($plugin->fresh()->is_active);
    }

    /** @test */
    public function test_activate_redirects_with_success_flash(): void // J6
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin', 'name' => 'Test Plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.activate', $plugin))
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('success');
    }

    /** @test */
    public function test_activate_records_audit_log(): void // J7
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin', 'name' => 'Test Plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.activate', $plugin));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $this->admin->id,
            'action'         => 'plugin.activated',
            'auditable_type' => 'Plugin',
            'auditable_id'   => $plugin->id,
        ]);
    }

    // =========================================================================
    // J8–J10 — deactivate
    // =========================================================================

    /** @test */
    public function test_deactivate_sets_plugin_inactive(): void // J8
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.deactivate', $plugin));

        $this->assertFalse($plugin->fresh()->is_active);
    }

    /** @test */
    public function test_deactivate_redirects_with_success_flash(): void // J9
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin', 'name' => 'Test Plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.deactivate', $plugin))
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('success');
    }

    /** @test */
    public function test_deactivate_records_audit_log(): void // J10
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin', 'name' => 'Test Plugin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.plugins.deactivate', $plugin));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $this->admin->id,
            'action'         => 'plugin.deactivated',
            'auditable_type' => 'Plugin',
            'auditable_id'   => $plugin->id,
        ]);
    }

    // =========================================================================
    // J11–J12 — destroy
    // =========================================================================

    /** @test */
    public function test_destroy_deletes_inactive_plugin_from_db(): void // J11
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'removable-plugin']);

        $this->actingAs($this->admin)
            ->delete(route('admin.plugins.destroy', $plugin));

        $this->assertDatabaseMissing('plugins', ['slug' => 'removable-plugin']);
    }

    /** @test */
    public function test_destroy_redirects_with_error_when_plugin_is_still_active(): void // J12
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'active-plugin']);

        $this->actingAs($this->admin)
            ->delete(route('admin.plugins.destroy', $plugin))
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('plugins', ['slug' => 'active-plugin']);
    }
}
