<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * C3 — Functional smoke test: verifies the Phase 6 wiring end to end
 * (schema present, block views exist, admin routes guarded, public routes work).
 */
class C3SmokeTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- schema

    public function test_all_phase6_tables_exist(): void
    {
        foreach ([
            'content_types', 'field_groups', 'fields', 'content_entries',
            'content_entry_index', 'taxonomies', 'terms', 'content_entry_term',
            'content_entry_relations', 'content_entry_revisions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing Phase 6 table: {$table}");
        }
    }

    public function test_page_blocks_morph_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('page_blocks', 'blockable_type'));
        $this->assertTrue(Schema::hasColumn('page_blocks', 'blockable_id'));
    }

    // ---------------------------------------------------------------- block registry ↔ views

    public function test_every_registered_block_has_a_render_view(): void
    {
        foreach (array_keys(config('blocks')) as $type) {
            $view = 'frontend.blocks.'.str_replace('_', '-', (string) $type);
            $this->assertTrue(View::exists($view), "Missing block view: {$view}");
        }
    }

    public function test_bridge_blocks_are_registered(): void
    {
        $blocks = config('blocks');
        $this->assertArrayHasKey('content_query', $blocks);
        $this->assertArrayHasKey('content_field', $blocks);
    }

    // ---------------------------------------------------------------- admin route guards

    public function test_admin_content_routes_require_admin(): void
    {
        $routes = [
            route('admin.content-types.index'),
            route('admin.taxonomies.index'),
        ];

        // Guests first (no actingAs carry-over), then non-admin, then admin.
        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $nonAdmin = User::factory()->create();
        foreach ($routes as $url) {
            $this->actingAs($nonAdmin)->get($url)->assertForbidden();
        }

        $admin = $this->admin();
        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    // ---------------------------------------------------------------- public routes

    public function test_public_archive_single_and_draft_guard(): void
    {
        $type = ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Blog', 'label_plural' => 'Blogs',
            'is_public' => true, 'is_active' => true, 'has_archive' => true, 'route_base' => 'blog',
            'supports' => ['title', 'slug'],
        ]);

        $type->entries()->create(['title' => 'Live', 'slug' => 'live', 'status' => 'published', 'published_at' => now()->subDay()]);
        $type->entries()->create(['title' => 'Hidden', 'slug' => 'hidden', 'status' => 'draft']);

        $this->get(url('/blog'))->assertOk()->assertSee('Live')->assertDontSee('Hidden');
        $this->get(url('/blog/live'))->assertOk()->assertSee('Live');
        $this->get(url('/blog/hidden'))->assertNotFound();   // draft
        $this->get(url('/blog/missing'))->assertNotFound();  // unknown slug
    }

    public function test_scheduler_command_runs(): void
    {
        $this->artisan('content-entries:publish-scheduled')->assertSuccessful();
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
