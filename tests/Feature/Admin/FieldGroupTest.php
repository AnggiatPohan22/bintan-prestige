<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldGroupTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- auth

    public function test_field_group_routes_require_admin(): void
    {
        $type = $this->type();

        $this->get(route('admin.content-types.field-groups.index', $type))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.content-types.field-groups.index', $type))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.field-groups.index', $type))
            ->assertOk();
    }

    // -------------------------------------------------------- index

    public function test_index_lists_groups_for_content_type(): void
    {
        $type  = $this->type();
        $other = $this->type(['slug' => 'restaurant', 'label_plural' => 'Restaurants']);

        $group  = $this->group($type, ['label' => 'Basic Info', 'key' => 'basic_info']);
        $hidden = $this->group($other, ['label' => 'Location', 'key' => 'location']);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.field-groups.index', $type))
            ->assertOk()
            ->assertSee('Basic Info')
            ->assertDontSee('Location');
    }

    // -------------------------------------------------------- create + store

    public function test_admin_can_create_a_field_group_with_auto_key(): void
    {
        $type = $this->type();

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.store', $type), [
                'label'       => 'Property Details',
                'description' => 'Core property fields.',
                'sort_order'  => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('field_groups', [
            'content_type_id' => $type->id,
            'label'           => 'Property Details',
            'key'             => 'property_details',
        ]);
    }

    public function test_validation_rejects_duplicate_key_within_type(): void
    {
        $type = $this->type();
        $this->group($type, ['key' => 'details']);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.field-groups.create', $type))
            ->post(route('admin.content-types.field-groups.store', $type), [
                'label' => 'Other Details',
                'key'   => 'details',
            ])
            ->assertSessionHasErrors('key');
    }

    public function test_duplicate_key_is_allowed_across_different_content_types(): void
    {
        $typeA = $this->type(['slug' => 'hotel',  'label_plural' => 'Hotels']);
        $typeB = $this->type(['slug' => 'villa',  'label_plural' => 'Villas']);
        $this->group($typeA, ['key' => 'pricing']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.store', $typeB), [
                'label' => 'Pricing',
                'key'   => 'pricing',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('field_groups', ['content_type_id' => $typeB->id, 'key' => 'pricing']);
    }

    public function test_validation_rejects_invalid_key_format(): void
    {
        $type = $this->type();

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.field-groups.create', $type))
            ->post(route('admin.content-types.field-groups.store', $type), [
                'label' => 'Bad Key',
                'key'   => '123-invalid',
            ])
            ->assertSessionHasErrors('key');
    }

    // -------------------------------------------------------- edit + update

    public function test_admin_can_update_field_group_label(): void
    {
        $type  = $this->type();
        $group = $this->group($type, ['label' => 'Old Name', 'key' => 'old_name']);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.field-groups.update', [$type, $group]), [
                'label'      => 'New Name',
                'key'        => 'old_name',
                'sort_order' => '0',
            ])
            ->assertRedirect(route('admin.content-types.field-groups.edit', [$type, $group]));

        $this->assertDatabaseHas('field_groups', ['id' => $group->id, 'label' => 'New Name']);
    }

    public function test_404_when_group_belongs_to_different_content_type(): void
    {
        $typeA = $this->type(['slug' => 'hotel', 'label_plural' => 'Hotels']);
        $typeB = $this->type(['slug' => 'villa', 'label_plural' => 'Villas']);
        $group = $this->group($typeA, ['key' => 'details']);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.field-groups.edit', [$typeB, $group]))
            ->assertNotFound();
    }

    // -------------------------------------------------------- destroy

    public function test_admin_can_delete_a_field_group_and_cascade_fields(): void
    {
        $type  = $this->type();
        $group = $this->group($type, ['key' => 'media']);

        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'image',
            'key'            => 'hero_image',
            'label'          => 'Hero Image',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.field-groups.destroy', [$type, $group]))
            ->assertRedirect(route('admin.content-types.field-groups.index', $type));

        $this->assertDatabaseMissing('field_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('fields', ['field_group_id' => $group->id]);
    }

    // -------------------------------------------------------- reorder

    public function test_reorder_updates_sort_order(): void
    {
        $type = $this->type();
        $g1   = $this->group($type, ['key' => 'aaa', 'sort_order' => 0]);
        $g2   = $this->group($type, ['key' => 'bbb', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.field-groups.reorder', $type), [
                'ids' => [$g2->id, $g1->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('field_groups', ['id' => $g2->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('field_groups', ['id' => $g1->id, 'sort_order' => 1]);
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
        ], $overrides));
    }

    private function group(ContentType $type, array $overrides = []): FieldGroup
    {
        return FieldGroup::create(array_merge([
            'content_type_id' => $type->id,
            'label'           => 'Details',
            'key'             => 'details',
            'sort_order'      => 0,
        ], $overrides));
    }
}
