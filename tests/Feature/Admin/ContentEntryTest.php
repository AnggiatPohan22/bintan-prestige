<?php

namespace Tests\Feature\Admin;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\FieldGroup;
use App\Models\Field;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentEntryTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- auth guard

    public function test_entry_routes_require_admin(): void
    {
        $type = $this->type();

        $this->get(route('admin.content-types.entries.index', $type))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.content-types.entries.index', $type))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.index', $type))
            ->assertOk();
    }

    // -------------------------------------------------------- index

    public function test_index_shows_only_entries_for_the_content_type(): void
    {
        $typeA = $this->type(['slug' => 'hotel', 'label_plural' => 'Hotels']);
        $typeB = $this->type(['slug' => 'villa', 'label_plural' => 'Villas']);

        $this->entry($typeA, ['title' => 'Bintan Lagoon']);
        $this->entry($typeB, ['title' => 'Villa Pinang']);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.index', $typeA))
            ->assertOk()
            ->assertSee('Bintan Lagoon')
            ->assertDontSee('Villa Pinang');
    }

    // -------------------------------------------------------- create + store

    public function test_create_page_renders_field_groups(): void
    {
        $type  = $this->type();
        $group = $this->group($type, ['label' => 'Property Info']);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.create', $type))
            ->assertOk()
            ->assertSee('Property Info');
    }

    public function test_create_page_renders_when_group_has_fields(): void
    {
        // Regression: form.blade.php called $entry->fieldValue() unconditionally,
        // but $entry is undefined on the create page. A method call is not
        // suppressed by ??, so a group with at least one field crashed the page
        // with "Undefined variable $entry".
        $type  = $this->type();
        $group = $this->group($type, ['label' => 'Property Info']);
        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'hotel_name',
            'label'          => 'Hotel Name',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.create', $type))
            ->assertOk()
            ->assertSee('Hotel Name');
    }

    public function test_store_creates_entry_and_stores_data_json(): void
    {
        $type = $this->type(['supports' => ['title', 'slug']]);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'Bintan Lagoon Resort',
                'status' => 'draft',
                'data'   => ['star_rating' => '5', 'check_in' => '14:00'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('content_entries', [
            'content_type_id' => $type->id,
            'title'           => 'Bintan Lagoon Resort',
            'slug'            => 'bintan-lagoon-resort',  // auto-generated
            'status'          => 'draft',
        ]);

        $entry = ContentEntry::first();
        $this->assertSame('5', $entry->fieldValue('star_rating'));
        $this->assertSame('14:00', $entry->fieldValue('check_in'));
    }

    public function test_publishing_with_blank_date_goes_live_now(): void
    {
        $type = $this->type(['supports' => ['title', 'slug']]);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'        => 'Live Now',
                'status'       => 'published',
                'published_at' => '', // blank → live immediately
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry = ContentEntry::firstWhere('title', 'Live Now');
        $this->assertNotNull($entry->published_at);
        $this->assertTrue($entry->isPublished());
    }

    public function test_publishing_with_future_date_is_clamped_to_now(): void
    {
        $type = $this->type(['supports' => ['title', 'slug']]);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'        => 'Was Future',
                'status'       => 'published',
                'published_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Published (not Scheduled) → forced live now, so it is visible.
        $entry = ContentEntry::firstWhere('title', 'Was Future');
        $this->assertTrue($entry->isPublished());
        $this->assertFalse($entry->published_at->isFuture());
    }

    public function test_checkbox_empty_strings_are_cleaned_from_data(): void
    {
        $type = $this->type();

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'Test Entry',
                'status' => 'draft',
                // Simulates the hidden-input fallback for a checkbox field.
                'data'   => ['amenities' => ['', 'pool', 'wifi']],
            ])
            ->assertSessionHasNoErrors();

        $entry = ContentEntry::first();
        $this->assertSame(['pool', 'wifi'], $entry->fieldValue('amenities'));
    }

    public function test_slug_is_unique_within_content_type(): void
    {
        $type = $this->type(['supports' => ['title', 'slug']]);
        $this->entry($type, ['slug' => 'beach-villa']);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'Another Villa',
                'slug'   => 'beach-villa',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_same_slug_allowed_across_different_content_types(): void
    {
        $typeA = $this->type(['slug' => 'hotel', 'label_plural' => 'Hotels']);
        $typeB = $this->type(['slug' => 'villa', 'label_plural' => 'Villas']);
        $this->entry($typeA, ['slug' => 'bintan-resort']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $typeB), [
                'title'  => 'Bintan Resort Villa',
                'slug'   => 'bintan-resort',
                'status' => 'draft',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('content_entries', [
            'content_type_id' => $typeB->id,
            'slug'            => 'bintan-resort',
        ]);
    }

    public function test_scheduled_status_requires_published_at(): void
    {
        $type = $this->type(['supports' => ['scheduling']]);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'title'        => 'Future Post',
                'status'       => 'scheduled',
                'published_at' => '',
            ])
            ->assertSessionHasErrors('published_at');
    }

    // -------------------------------------------------------- update

    public function test_admin_can_update_entry_and_data_json(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type, ['title' => 'Old Title', 'data' => ['beds' => '2']]);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.entries.update', [$type, $entry]), [
                'title'  => 'New Title',
                'status' => 'published',
                'data'   => ['beds' => '4'],
            ])
            ->assertRedirect(route('admin.content-types.entries.edit', [$type, $entry]));

        $entry->refresh();
        $this->assertSame('New Title', $entry->title);
        $this->assertSame('4', $entry->fieldValue('beds'));
    }

    public function test_404_when_entry_belongs_to_different_content_type(): void
    {
        $typeA = $this->type(['slug' => 'hotel', 'label_plural' => 'Hotels']);
        $typeB = $this->type(['slug' => 'villa', 'label_plural' => 'Villas']);
        $entry = $this->entry($typeA);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.edit', [$typeB, $entry]))
            ->assertNotFound();
    }

    // -------------------------------------------------------- soft delete + restore + force delete

    public function test_destroy_soft_deletes_entry(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.entries.destroy', [$type, $entry]))
            ->assertRedirect(route('admin.content-types.entries.index', $type));

        $this->assertSoftDeleted('content_entries', ['id' => $entry->id]);
    }

    public function test_admin_can_restore_a_trashed_entry(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type);
        $entry->delete();

        $this->actingAs($this->admin())
            ->patch(route('admin.content-types.entries.restore', [$type, $entry->id]))
            ->assertRedirect(route('admin.content-types.entries.index', $type));

        $this->assertDatabaseHas('content_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }

    public function test_admin_can_force_delete_a_trashed_entry(): void
    {
        $type  = $this->type();
        $entry = $this->entry($type);
        $entry->delete();

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.entries.force-delete', [$type, $entry->id]))
            ->assertRedirect(route('admin.content-types.entries.index', $type));

        $this->assertDatabaseMissing('content_entries', ['id' => $entry->id]);
    }

    // -------------------------------------------------------- content type guard

    public function test_force_deleting_content_type_is_blocked_when_entries_exist(): void
    {
        $type = $this->type();
        $this->entry($type);
        $type->delete();  // soft-delete the type first

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.force-delete', $type->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Type and its entry must still exist.
        $this->assertSoftDeleted('content_types', ['id' => $type->id]);
        $this->assertDatabaseHas('content_entries', ['content_type_id' => $type->id]);
    }

    // -------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function type(array $overrides = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'hotel',
            'label_singular' => 'Hotel',
            'label_plural'   => 'Hotels',
            'supports'       => ['title', 'slug'],
        ], $overrides));
    }

    private function group(ContentType $type, array $overrides = []): FieldGroup
    {
        return FieldGroup::create(array_merge([
            'content_type_id' => $type->id,
            'label'           => 'Details',
            'key'             => 'details',
        ], $overrides));
    }

    private function entry(ContentType $type, array $overrides = []): ContentEntry
    {
        return ContentEntry::create(array_merge([
            'content_type_id' => $type->id,
            'title'           => 'Test Entry',
            'status'          => 'draft',
        ], $overrides));
    }
}
