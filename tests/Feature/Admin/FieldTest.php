<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- auth

    public function test_field_routes_require_admin(): void
    {
        [$type, $group] = $this->typeAndGroup();

        $this->get(route('admin.content-types.field-groups.fields.create', [$type, $group]))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.content-types.field-groups.fields.create', [$type, $group]))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.field-groups.fields.create', [$type, $group]))
            ->assertOk();
    }

    // -------------------------------------------------------- store

    public function test_admin_can_add_a_field_with_auto_key(): void
    {
        [$type, $group] = $this->typeAndGroup();

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $group]), [
                'type'  => 'text',
                'label' => 'Star Rating',
            ])
            ->assertRedirect(route('admin.content-types.field-groups.edit', [$type, $group]));

        $this->assertDatabaseHas('fields', [
            'field_group_id' => $group->id,
            'type'           => 'text',
            'label'          => 'Star Rating',
            'key'            => 'star_rating',
        ]);
    }

    public function test_is_filterable_inherits_from_catalog_default(): void
    {
        [$type, $group] = $this->typeAndGroup();

        // 'text' has is_filterable=true in catalog
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $group]), [
                'type'  => 'text',
                'label' => 'Title',
            ]);

        $this->assertDatabaseHas('fields', [
            'field_group_id' => $group->id,
            'key'            => 'title',
            'is_filterable'  => true,
        ]);

        // 'richtext' has is_filterable=false in catalog
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $group]), [
                'type'  => 'richtext',
                'label' => 'Body',
            ]);

        $this->assertDatabaseHas('fields', [
            'field_group_id' => $group->id,
            'key'            => 'body',
            'is_filterable'  => false,
        ]);
    }

    public function test_validation_rejects_invalid_field_type(): void
    {
        [$type, $group] = $this->typeAndGroup();

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.field-groups.fields.create', [$type, $group]))
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $group]), [
                'type'  => 'does_not_exist',
                'label' => 'Bad Field',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_validation_rejects_duplicate_key_within_group(): void
    {
        [$type, $group] = $this->typeAndGroup();
        $this->field($group, ['type' => 'text', 'key' => 'name']);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.field-groups.fields.create', [$type, $group]))
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $group]), [
                'type'  => 'number',
                'label' => 'Name',
                'key'   => 'name',
            ])
            ->assertSessionHasErrors('key');
    }

    public function test_duplicate_key_allowed_across_different_groups(): void
    {
        $type  = $this->type();
        $groupA = $this->group($type, ['key' => 'group_a']);
        $groupB = $this->group($type, ['key' => 'group_b']);
        $this->field($groupA, ['key' => 'title']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.field-groups.fields.store', [$type, $groupB]), [
                'type'  => 'text',
                'label' => 'Title',
                'key'   => 'title',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fields', ['field_group_id' => $groupB->id, 'key' => 'title']);
    }

    public function test_404_when_field_belongs_to_different_group(): void
    {
        $type   = $this->type();
        $groupA = $this->group($type, ['key' => 'ga']);
        $groupB = $this->group($type, ['key' => 'gb']);
        $field  = $this->field($groupA, ['key' => 'price']);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.field-groups.fields.edit', [$type, $groupB, $field]))
            ->assertNotFound();
    }

    // -------------------------------------------------------- update

    public function test_admin_can_update_a_field(): void
    {
        [$type, $group] = $this->typeAndGroup();
        $field = $this->field($group, ['type' => 'text', 'key' => 'headline', 'label' => 'Headline']);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.field-groups.fields.update', [$type, $group, $field]), [
                'type'         => 'text',
                'key'          => 'headline',
                'label'        => 'Main Headline',
                'instructions' => 'Used in hero section.',
                'is_required'  => '1',
            ])
            ->assertRedirect(route('admin.content-types.field-groups.edit', [$type, $group]));

        $this->assertDatabaseHas('fields', [
            'id'           => $field->id,
            'label'        => 'Main Headline',
            'instructions' => 'Used in hero section.',
            'is_required'  => true,
        ]);
    }

    // -------------------------------------------------------- destroy

    public function test_admin_can_delete_a_field(): void
    {
        [$type, $group] = $this->typeAndGroup();
        $field = $this->field($group, ['key' => 'price']);

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.field-groups.fields.destroy', [$type, $group, $field]))
            ->assertRedirect(route('admin.content-types.field-groups.edit', [$type, $group]));

        $this->assertDatabaseMissing('fields', ['id' => $field->id]);
    }

    // -------------------------------------------------------- reorder

    public function test_reorder_updates_field_sort_order(): void
    {
        [$type, $group] = $this->typeAndGroup();
        $f1 = $this->field($group, ['key' => 'aaa', 'sort_order' => 0]);
        $f2 = $this->field($group, ['key' => 'bbb', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.field-groups.fields.reorder', [$type, $group]), [
                'ids' => [$f2->id, $f1->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('fields', ['id' => $f2->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('fields', ['id' => $f1->id, 'sort_order' => 1]);
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
        ], $overrides));
    }

    private function field(FieldGroup $group, array $overrides = []): Field
    {
        return Field::create(array_merge([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'name',
            'label'          => 'Name',
        ], $overrides));
    }

    /** @return array{ContentType, FieldGroup} */
    private function typeAndGroup(): array
    {
        $type  = $this->type();
        $group = $this->group($type);
        return [$type, $group];
    }
}
