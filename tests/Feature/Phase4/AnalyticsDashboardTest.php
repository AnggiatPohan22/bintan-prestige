<?php

namespace Tests\Feature\Phase4;

use App\Console\Commands\AggregatePageViewStats;
use App\Http\Middleware\TrackPageView;
use App\Models\Page;
use App\Models\PageView;
use App\Models\PageViewDailyStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function publishedPage(array $attrs = []): Page
    {
        return Page::factory()->published()->create($attrs);
    }

    // N1 — Visiting a published page as a guest records a PageView.
    public function test_n1_visiting_published_page_records_page_view(): void
    {
        $page = $this->publishedPage(['slug' => 'test-page']);

        $this->get(route('pages.show', $page->slug));

        $this->assertDatabaseHas('page_views', ['page_id' => $page->id]);
    }

    // N2 — visitor_hash is SHA256 of IP concatenated with User-Agent.
    public function test_n2_visitor_hash_is_sha256_of_ip_and_user_agent(): void
    {
        $page = $this->publishedPage(['slug' => 'hash-page']);

        $this->get(route('pages.show', $page->slug), ['User-Agent' => 'TestAgent/1.0']);

        $view = PageView::where('page_id', $page->id)->first();
        $this->assertNotNull($view);

        $expected = hash('sha256', '127.0.0.1' . 'TestAgent/1.0');
        $this->assertSame($expected, $view->visitor_hash);
    }

    // N3 — Authenticated admin visiting a page does NOT create a PageView.
    public function test_n3_admin_visit_does_not_create_page_view(): void
    {
        $page = $this->publishedPage(['slug' => 'admin-page']);

        $this->actingAs($this->admin)
             ->get(route('pages.show', $page->slug));

        $this->assertDatabaseMissing('page_views', ['page_id' => $page->id]);
    }

    // N4 — Admin can view analytics dashboard.
    public function test_n4_admin_can_view_analytics_dashboard(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.analytics.index'))
             ->assertOk()
             ->assertSee('Analytics');
    }

    // N5 — Dashboard view passes chart data for the last 30 days.
    public function test_n5_dashboard_contains_chart_data(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.analytics.index'))
             ->assertOk()
             ->assertSee('analytics-chart');
    }

    // N6 — Dashboard shows top pages section.
    public function test_n6_dashboard_shows_top_pages_section(): void
    {
        $page = $this->publishedPage();
        PageViewDailyStat::create([
            'page_id'         => $page->id,
            'stat_date'       => now()->toDateString(),
            'view_count'      => 42,
            'unique_visitors' => 10,
        ]);

        $this->actingAs($this->admin)
             ->get(route('admin.analytics.index'))
             ->assertOk()
             ->assertSee($page->title);
    }

    // N7 — analytics:aggregate-daily command creates daily stats records.
    public function test_n7_aggregate_command_creates_daily_stats(): void
    {
        $page = $this->publishedPage();
        $date = '2026-01-15';

        PageView::create(['page_id' => $page->id, 'visitor_hash' => hash('sha256', 'a'), 'viewed_date' => $date]);
        PageView::create(['page_id' => $page->id, 'visitor_hash' => hash('sha256', 'b'), 'viewed_date' => $date]);

        // Call aggregate() directly to stay within the test's database transaction.
        $cmd = $this->app->make(\App\Console\Commands\AggregatePageViewStats::class);
        $count = $cmd->aggregate($date);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('page_view_daily_stats', [
            'page_id'         => $page->id,
            'stat_date'       => $date,
            'view_count'      => 2,
            'unique_visitors' => 2,
        ]);
    }

    // N8 — Aggregate command is idempotent: re-running updates, not duplicates.
    public function test_n8_aggregate_command_is_idempotent(): void
    {
        $page = $this->publishedPage();
        $date = '2026-01-15';

        PageView::create(['page_id' => $page->id, 'visitor_hash' => hash('sha256', 'x'), 'viewed_date' => $date]);

        $cmd = $this->app->make(\App\Console\Commands\AggregatePageViewStats::class);
        $cmd->aggregate($date);
        $cmd->aggregate($date);

        $this->assertSame(1, PageViewDailyStat::where('page_id', $page->id)->count());
    }

    // N9 — CSV export returns a streaming response with csv content-type.
    public function test_n9_csv_export_returns_csv_file(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.analytics.export'))
             ->assertOk()
             ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // N10 — CSV export contains expected column headers.
    public function test_n10_csv_export_contains_correct_headers(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.analytics.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Page Title', $content);
        $this->assertStringContainsString('Views', $content);
        $this->assertStringContainsString('Unique Visitors', $content);
    }

    // N11 — PageView is cascade-deleted when its Page is deleted.
    public function test_n11_page_view_cascades_on_page_delete(): void
    {
        $page = $this->publishedPage();

        PageView::create([
            'page_id'      => $page->id,
            'visitor_hash' => hash('sha256', 'cascade-test'),
            'viewed_date'  => now()->toDateString(),
        ]);

        $pageId = $page->id;
        $page->delete();

        $this->assertDatabaseMissing('page_views', ['page_id' => $pageId]);
    }

    // N12 — Visiting a non-page route (home) does NOT create a PageView.
    public function test_n12_non_page_route_does_not_create_page_view(): void
    {
        $this->get(route('home'));

        $this->assertDatabaseCount('page_views', 0);
    }
}
