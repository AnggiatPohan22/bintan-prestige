<?php

namespace Tests\Feature\Phase3;

use App\Models\Page;
use App\Models\Theme;
use App\Models\User;
use App\Models\Widget;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ThemeService::class)->forget();
    }

    // -------------------------------------------------------------------------
    // Widget Manager — index
    // -------------------------------------------------------------------------

    public function test_admin_can_access_widget_manager_for_a_theme(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.widgets.index', $theme))
            ->assertOk()
            ->assertSee('Footer Column 1')
            ->assertSee('Footer Column 2')
            ->assertSee('Before Footer');
    }

    public function test_widget_manager_shows_empty_state_when_no_widget_areas_defined(): void
    {
        $theme = $this->bareTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.widgets.index', $theme))
            ->assertOk()
            ->assertSee('No widget areas defined');
    }

    public function test_widget_manager_index_requires_admin_auth(): void
    {
        $theme = $this->bareTheme();

        $this->get(route('admin.themes.widgets.index', $theme))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.themes.widgets.index', $theme))
            ->assertForbidden();
    }

    public function test_widget_manager_lists_widgets_grouped_by_area(): void
    {
        $theme = $this->luxuryTheme();
        $theme->widgets()->createMany([
            ['area' => 'footer-col-1', 'widget_type' => 'text',       'title' => 'About Widget',  'sort_order' => 0, 'is_visible' => true],
            ['area' => 'footer-col-2', 'widget_type' => 'navigation', 'title' => 'Links Widget',  'sort_order' => 0, 'is_visible' => true],
            ['area' => 'before-footer', 'widget_type' => 'html',      'title' => 'Banner Widget', 'sort_order' => 0, 'is_visible' => true],
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.widgets.index', $theme))
            ->assertOk()
            ->assertSee('About Widget')
            ->assertSee('Links Widget')
            ->assertSee('Banner Widget');
    }

    // -------------------------------------------------------------------------
    // Create widget
    // -------------------------------------------------------------------------

    public function test_admin_can_create_a_text_widget(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.store', $theme), [
                'area'        => 'footer-col-1',
                'widget_type' => 'text',
                'title'       => 'About Us',
                'sort_order'  => 1,
                'data'        => [
                    'heading' => 'About Bintan Prestige',
                    'content' => '<p>Welcome to paradise.</p>',
                ],
            ])
            ->assertRedirect(route('admin.themes.widgets.index', $theme))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('widgets', [
            'theme_id'    => $theme->id,
            'area'        => 'footer-col-1',
            'widget_type' => 'text',
            'title'       => 'About Us',
        ]);
    }

    public function test_admin_can_create_a_navigation_widget_with_links(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.store', $theme), [
                'area'        => 'footer-col-2',
                'widget_type' => 'navigation',
                'title'       => 'Quick Links',
                'sort_order'  => 0,
                'data'        => [
                    'heading' => 'Quick Links',
                    'links'   => [
                        ['label' => 'Home',    'url' => '/'],
                        ['label' => 'Contact', 'url' => '/pages/contact'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.themes.widgets.index', $theme));

        $widget = $theme->widgets()->where('widget_type', 'navigation')->first();
        $this->assertNotNull($widget);
        $this->assertCount(2, $widget->data['links']);
        $this->assertSame('Home', $widget->data['links'][0]['label']);
    }

    public function test_store_rejects_invalid_widget_type(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.store', $theme), [
                'area'        => 'footer-col-1',
                'widget_type' => 'invalid-type',
                'title'       => 'Bad Widget',
            ])
            ->assertSessionHasErrors('widget_type');
    }

    public function test_store_rejects_area_not_declared_in_schema(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.store', $theme), [
                'area'        => 'unknown-area',
                'widget_type' => 'text',
                'title'       => 'Orphan Widget',
            ])
            ->assertSessionHasErrors('area');
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function test_admin_can_update_a_widget(): void
    {
        $theme  = $this->luxuryTheme();
        $widget = $theme->widgets()->create([
            'area'        => 'footer-col-1',
            'widget_type' => 'text',
            'title'       => 'Old Title',
            'data'        => ['heading' => 'Old', 'content' => 'Old content'],
            'sort_order'  => 0,
            'is_visible'  => true,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.widgets.update', [$theme, $widget]), [
                'title'      => 'New Title',
                'sort_order' => 2,
                'data'       => ['heading' => 'New Heading', 'content' => '<p>New content.</p>'],
            ])
            ->assertRedirect(route('admin.themes.widgets.index', $theme))
            ->assertSessionHas('success');

        $widget->refresh();
        $this->assertSame('New Title', $widget->title);
        $this->assertSame('New Heading', $widget->data['heading']);
    }

    public function test_update_returns_404_when_widget_does_not_belong_to_theme(): void
    {
        $theme   = $this->luxuryTheme();
        $other   = $this->bareTheme('other-theme');
        $widget  = $theme->widgets()->create([
            'area' => 'footer-col-1', 'widget_type' => 'text', 'sort_order' => 0, 'is_visible' => true,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.widgets.update', [$other, $widget]), [
                'title' => 'Hacked',
            ])
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function test_admin_can_delete_a_widget(): void
    {
        $theme  = $this->luxuryTheme();
        $widget = $theme->widgets()->create([
            'area' => 'footer-col-1', 'widget_type' => 'text', 'sort_order' => 0, 'is_visible' => true,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.themes.widgets.destroy', [$theme, $widget]))
            ->assertRedirect(route('admin.themes.widgets.index', $theme));

        $this->assertDatabaseMissing('widgets', ['id' => $widget->id]);
    }

    // -------------------------------------------------------------------------
    // Toggle visible
    // -------------------------------------------------------------------------

    public function test_toggle_visible_flips_widget_visibility(): void
    {
        $theme  = $this->luxuryTheme();
        $widget = $theme->widgets()->create([
            'area' => 'footer-col-1', 'widget_type' => 'text', 'sort_order' => 0, 'is_visible' => true,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.toggle-visible', [$theme, $widget]));

        $this->assertFalse($widget->fresh()->is_visible);

        $this->actingAs($admin)
            ->post(route('admin.themes.widgets.toggle-visible', [$theme, $widget]));

        $this->assertTrue($widget->fresh()->is_visible);
    }

    // -------------------------------------------------------------------------
    // ThemeService::widgetsForArea
    // -------------------------------------------------------------------------

    public function test_widgets_for_area_returns_empty_collection_when_no_theme_is_active(): void
    {
        $result = app(ThemeService::class)->widgetsForArea('footer-col-1');

        $this->assertCount(0, $result);
    }

    public function test_widgets_for_area_returns_visible_widgets_for_the_active_theme(): void
    {
        $theme = $this->bareTheme('active-widget-theme', active: true);
        $theme->widgets()->createMany([
            ['area' => 'footer-col-1', 'widget_type' => 'text', 'title' => 'Visible', 'sort_order' => 0, 'is_visible' => true],
            ['area' => 'footer-col-1', 'widget_type' => 'text', 'title' => 'Hidden',  'sort_order' => 1, 'is_visible' => false],
            ['area' => 'footer-col-2', 'widget_type' => 'text', 'title' => 'Other',   'sort_order' => 0, 'is_visible' => true],
        ]);

        $col1 = app(ThemeService::class)->widgetsForArea('footer-col-1');
        $col2 = app(ThemeService::class)->widgetsForArea('footer-col-2');

        $this->assertCount(1, $col1);
        $this->assertSame('Visible', $col1->first()->title);
        $this->assertCount(1, $col2);
    }

    public function test_widgets_are_loaded_in_one_query_for_all_areas(): void
    {
        $theme = $this->bareTheme('perf-theme', active: true);
        $theme->widgets()->createMany([
            ['area' => 'footer-col-1', 'widget_type' => 'text', 'sort_order' => 0, 'is_visible' => true],
            ['area' => 'footer-col-2', 'widget_type' => 'html', 'sort_order' => 0, 'is_visible' => true],
        ]);

        $queries = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$queries): void {
            if (str_contains(strtolower($q->sql), 'from "widgets"')) {
                $queries++;
            }
        });

        app(ThemeService::class)->widgetsForArea('footer-col-1');
        app(ThemeService::class)->widgetsForArea('footer-col-2');

        $this->assertSame(1, $queries, 'Expected exactly one DB query for widgets across all area calls.');
    }

    // -------------------------------------------------------------------------
    // Frontend rendering
    // -------------------------------------------------------------------------

    public function test_widget_area_partial_renders_text_widget_on_frontend(): void
    {
        $theme = $this->bareTheme('render-theme', active: true);
        $theme->widgets()->create([
            'area'        => 'footer-col-1',
            'widget_type' => 'text',
            'title'       => 'Render Test',
            'data'        => ['heading' => 'Island Welcome', 'content' => '<p>Bintan awaits.</p>'],
            'sort_order'  => 0,
            'is_visible'  => true,
        ]);

        $page = Page::create([
            'title'  => 'Render Widget Page',
            'slug'   => 'render-widget-page',
            'status' => 'published',
        ]);

        $rendered = (string) $this->view(
            'frontend.partials.widget-area',
            ['area' => 'footer-col-1'],
        );

        $this->assertStringContainsString('Island Welcome', $rendered);
        $this->assertStringContainsString('Bintan awaits.', $rendered);
    }

    public function test_widget_area_partial_renders_empty_when_no_active_theme(): void
    {
        $rendered = (string) $this->view(
            'frontend.partials.widget-area',
            ['area' => 'footer-col-1'],
        );

        $this->assertSame('', trim($rendered));
    }

    public function test_widget_area_partial_skips_hidden_widgets(): void
    {
        $theme = $this->bareTheme('hidden-widget-theme', active: true);
        $theme->widgets()->create([
            'area'        => 'before-footer',
            'widget_type' => 'text',
            'title'       => 'Hidden Widget',
            'data'        => ['heading' => 'Should Not Show', 'content' => 'Hidden body'],
            'sort_order'  => 0,
            'is_visible'  => false,
        ]);

        $rendered = (string) $this->view(
            'frontend.partials.widget-area',
            ['area' => 'before-footer'],
        );

        $this->assertStringNotContainsString('Should Not Show', $rendered);
    }

    // -------------------------------------------------------------------------
    // Cache invalidation
    // -------------------------------------------------------------------------

    public function test_widget_cache_is_cleared_when_a_widget_is_saved(): void
    {
        $theme  = $this->bareTheme('cache-widget-theme', active: true);
        $widget = $theme->widgets()->create([
            'area' => 'footer-col-1', 'widget_type' => 'text', 'sort_order' => 0, 'is_visible' => true,
        ]);

        // Prime the in-memory cache.
        app(ThemeService::class)->widgetsForArea('footer-col-1');

        // Saving a widget should clear the cache via AppServiceProvider hook.
        $widget->touch();

        // After forget, re-fetching should pick up fresh DB state.
        // The in-memory array should be null again after the hook fired.
        // We can't inspect private properties, so instead verify the query count resets.
        $queries = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$queries): void {
            if (str_contains(strtolower($q->sql), 'from "widgets"')) {
                $queries++;
            }
        });

        app(ThemeService::class)->widgetsForArea('footer-col-1');

        $this->assertSame(1, $queries, 'Expected a fresh DB query after widget save cleared the cache.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function luxuryTheme(): Theme
    {
        return Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
    }

    private function bareTheme(string $slug = 'bare-theme', bool $active = false): Theme
    {
        return Theme::create([
            'name'      => ucwords(str_replace('-', ' ', $slug)),
            'slug'      => $slug,
            'directory' => "themes/{$slug}",
            'is_active' => $active,
        ]);
    }
}
