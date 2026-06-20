<?php

namespace Tests\Feature\Phase4;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    // L1 — Setting publish_at (future) + status=scheduled stores both fields.
    public function test_l1_page_can_be_created_with_scheduled_status_and_future_publish_at(): void
    {
        $futureDate = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($this->admin)
            ->post(route('admin.pages.store'), [
                'title'      => 'Scheduled Page',
                'slug'       => 'scheduled-page',
                'status'     => 'scheduled',
                'publish_at' => $futureDate,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'scheduled-page')->firstOrFail();
        $this->assertSame('scheduled', $page->status);
        $this->assertNotNull($page->publish_at);
    }

    // L2 — pages:publish-scheduled command publishes pages past their publish_at.
    public function test_l2_command_publishes_pages_whose_publish_at_has_passed(): void
    {
        $page = Page::factory()->scheduled(now()->subMinute())->create();

        $this->artisan('pages:publish-scheduled')->assertExitCode(0);

        $this->assertSame('published', $page->fresh()->status);
    }

    // L3 — Command does not publish pages whose publish_at is in the future.
    public function test_l3_command_does_not_publish_future_scheduled_pages(): void
    {
        $page = Page::factory()->scheduled(now()->addHour())->create();

        $this->artisan('pages:publish-scheduled')->assertExitCode(0);

        $this->assertSame('scheduled', $page->fresh()->status);
    }

    // L4 — Command sets publish_at = null after publishing.
    public function test_l4_command_clears_publish_at_after_publishing(): void
    {
        $page = Page::factory()->scheduled(now()->subMinute())->create();

        $this->artisan('pages:publish-scheduled');

        $this->assertNull($page->fresh()->publish_at);
    }

    // L5 — Command does not affect already-published pages.
    public function test_l5_command_does_not_touch_already_published_pages(): void
    {
        $page = Page::factory()->published()->create();

        $this->artisan('pages:publish-scheduled');

        $this->assertSame('published', $page->fresh()->status);
    }

    // L6 — publish_at nullable — existing create/update without publish_at still works.
    public function test_l6_create_page_without_publish_at_still_works(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pages.store'), [
                'title'      => 'Normal Draft Page',
                'slug'       => 'normal-draft',
                'status'     => 'draft',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'normal-draft')->firstOrFail();
        $this->assertSame('draft', $page->status);
        $this->assertNull($page->publish_at);
    }

    // L7 — Badge "Scheduled" appears in page index for scheduled pages.
    public function test_l7_page_index_shows_scheduled_badge(): void
    {
        Page::factory()->scheduled(now()->addHour())->create(['title' => 'My Scheduled Page']);

        $this->actingAs($this->admin)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Scheduled');
    }

    // L8 — Page edit view shows publish_at datetime picker.
    public function test_l8_page_edit_view_shows_publish_at_datetime_picker(): void
    {
        $page = Page::factory()->draft()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('publish_at')
            ->assertSee('datetime-local');
    }

    // L9 — Saving a page with a past publish_at date sets status to published immediately.
    public function test_l9_past_publish_at_immediately_publishes_page(): void
    {
        $page = Page::factory()->draft()->create();
        $pastDate = now()->subDay()->format('Y-m-d\TH:i');

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'      => $page->title,
                'slug'       => $page->slug,
                'status'     => 'draft',
                'publish_at' => $pastDate,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('published', $page->fresh()->status);
        $this->assertNull($page->fresh()->publish_at);
    }

    // L10 — Saving a page with a future publish_at sets status to scheduled.
    public function test_l10_future_publish_at_sets_status_to_scheduled(): void
    {
        $page = Page::factory()->draft()->create();
        $futureDate = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'      => $page->title,
                'slug'       => $page->slug,
                'status'     => 'draft',
                'publish_at' => $futureDate,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame('scheduled', $page->fresh()->status);
        $this->assertNotNull($page->fresh()->publish_at);
    }

    // L11 — scopePublished() does not return scheduled pages.
    public function test_l11_scope_published_excludes_scheduled_pages(): void
    {
        Page::factory()->published()->create(['title' => 'Live Page']);
        Page::factory()->scheduled(now()->addHour())->create(['title' => 'Future Page']);

        $published = Page::published()->get();

        $this->assertCount(1, $published);
        $this->assertSame('Live Page', $published->first()->title);
    }

    // L12 — publish_at is captured in the meta_snapshot when saveRevision() is called.
    public function test_l12_publish_at_is_snapshotted_in_page_revision(): void
    {
        $futureDate = now()->addDay();
        $page = Page::factory()->scheduled($futureDate)->create();

        // Trigger a revision by updating the page.
        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'      => $page->title . ' updated',
                'slug'       => $page->slug,
                'status'     => 'scheduled',
                'publish_at' => $futureDate->format('Y-m-d\TH:i'),
                'sort_order' => 0,
            ]);

        $revision = $page->revisions()->latest('revision_number')->first();
        $this->assertNotNull($revision);
        $this->assertArrayHasKey('publish_at', $revision->meta_snapshot);
    }
}
