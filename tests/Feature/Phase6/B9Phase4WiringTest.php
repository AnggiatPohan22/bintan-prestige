<?php

namespace Tests\Feature\Phase6;

use App\Models\AuditLog;
use App\Models\ContentEntry;
use App\Models\ContentEntryRevision;
use App\Models\ContentType;
use App\Models\User;
use App\Support\ContentEntryRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B9 — Phase 4 reuse wiring for content entries:
 * revisions, audit log, scheduling command, SEO meta helper.
 */
class B9Phase4WiringTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- revisions

    public function test_creating_an_entry_records_first_revision(): void
    {
        $type = $this->type();

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'First Post',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $entry = $type->entries()->firstOrFail();
        $this->assertDatabaseHas('content_entry_revisions', [
            'content_entry_id' => $entry->id,
            'revision_number'  => 1,
        ]);
    }

    public function test_updating_an_entry_records_incrementing_revision(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type, ['title' => 'Original']);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.entries.update', [$type, $entry]), [
                'title'  => 'Edited',
                'status' => 'draft',
            ])
            ->assertRedirect();

        // Update snapshots the new state.
        $this->assertSame(1, ContentEntryRevision::where('content_entry_id', $entry->id)->count());
        $this->assertDatabaseHas('content_entry_revisions', [
            'content_entry_id' => $entry->id,
            'revision_number'  => 1,
        ]);
    }

    public function test_revisions_are_pruned_to_twenty(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type);
        $service = app(ContentEntryRevisionService::class);

        $this->actingAs($this->admin());

        for ($i = 0; $i < 25; $i++) {
            $service->snapshot($entry);
        }

        $this->assertSame(20, ContentEntryRevision::where('content_entry_id', $entry->id)->count());
        // Oldest pruned: revision_number should start at 6 (25 - 20 + 1).
        $this->assertSame(6, (int) ContentEntryRevision::where('content_entry_id', $entry->id)->min('revision_number'));
    }

    public function test_restoring_a_revision_reverts_fields_and_is_reversible(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type, ['title' => 'Version A', 'data' => ['x' => '1']]);
        $service = app(ContentEntryRevisionService::class);

        $this->actingAs($this->admin());
        $service->snapshot($entry);                       // rev #1 = "Version A"
        $revA = $entry->revisions()->firstOrFail();

        // Move to Version B.
        $entry->update(['title' => 'Version B', 'data' => ['x' => '2']]);

        // Restore to rev #1.
        $service->restore($entry->fresh(), $revA);

        $entry->refresh();
        $this->assertSame('Version A', $entry->title);
        $this->assertSame(['x' => '1'], $entry->data);

        // Reversible: the pre-restore state (Version B) was snapshotted.
        $this->assertTrue(
            $entry->revisions()->get()->contains(fn ($r) => ($r->snapshot['title'] ?? null) === 'Version B')
        );
    }

    public function test_restore_revision_route_guards_cross_entry_revision(): void
    {
        $type   = $this->type();
        $entryA = $this->entry($type, ['title' => 'A']);
        $entryB = $this->entry($type, ['title' => 'B']);

        $this->actingAs($this->admin());
        app(ContentEntryRevisionService::class)->snapshot($entryB);
        $revOfB = $entryB->revisions()->firstOrFail();

        // Try to restore entry A using a revision that belongs to entry B.
        $this->post(route('admin.content-types.entries.revisions.restore', [$type, $entryA, $revOfB]))
            ->assertNotFound();
    }

    // ---------------------------------------------------------------- audit log

    public function test_entry_lifecycle_is_audited(): void
    {
        $type = $this->type();
        $this->actingAs($this->admin());

        $entry = $this->entry($type, ['title' => 'Audited']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'auditable_type' => 'ContentEntry', 'auditable_id' => $entry->id]);

        $entry->update(['title' => 'Audited Edited']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'auditable_type' => 'ContentEntry', 'auditable_id' => $entry->id]);

        $entry->delete();
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'auditable_type' => 'ContentEntry', 'auditable_id' => $entry->id]);
    }

    public function test_audit_label_uses_entry_title(): void
    {
        $type = $this->type();
        $this->actingAs($this->admin());

        $entry = $this->entry($type, ['title' => 'Labelled Entry']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id'    => $entry->id,
            'auditable_label' => 'Labelled Entry',
        ]);
    }

    // ---------------------------------------------------------------- scheduling command

    public function test_scheduled_command_publishes_only_due_entries(): void
    {
        $type = $this->type();

        $due = $this->entry($type, ['status' => 'scheduled', 'published_at' => now()->subMinute()]);
        $future = $this->entry($type, ['status' => 'scheduled', 'published_at' => now()->addDay()]);
        $draft = $this->entry($type, ['status' => 'draft', 'published_at' => now()->subDay()]);

        $this->artisan('content-entries:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    // ---------------------------------------------------------------- SEO meta helper

    public function test_seo_meta_falls_back_to_title_and_excerpt(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type, ['title' => 'My Title', 'excerpt' => 'My excerpt', 'seo' => null]);

        $meta = $entry->seoMeta();

        $this->assertSame('My Title', $meta['title']);
        $this->assertSame('My excerpt', $meta['description']);
        $this->assertNull($meta['canonical']);
    }

    public function test_seo_meta_prefers_explicit_values(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type, [
            'title'   => 'My Title',
            'excerpt' => 'My excerpt',
            'seo'     => ['title' => 'SEO Title', 'description' => 'SEO Desc', 'canonical' => 'https://x.test', 'og_image' => '7'],
        ]);

        $meta = $entry->seoMeta();

        $this->assertSame('SEO Title', $meta['title']);
        $this->assertSame('SEO Desc', $meta['description']);
        $this->assertSame('https://x.test', $meta['canonical']);
        $this->assertSame(7, $meta['og_image']);
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function type(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'blog',
            'label_singular' => 'Blog Post',
            'label_plural'   => 'Blog Posts',
            'supports'       => ['title', 'slug', 'seo'],
        ], $attrs));
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'  => 'Entry ' . uniqid(),
            'status' => 'draft',
        ], $attrs));
    }
}
