<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use App\Support\FieldValidationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that FieldValidationResolver builds correct per-field rules
 * and that those rules are enforced by the content entry FormRequests.
 */
class B5FieldValidationResolverTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- unit: resolver output

    public function test_resolver_returns_empty_for_type_with_no_field_groups(): void
    {
        $type  = $this->makeType();
        $rules = (new FieldValidationResolver())->resolveForContentType($type);

        $this->assertSame([], $rules);
    }

    public function test_optional_text_field_resolves_to_nullable_string(): void
    {
        $field = $this->makeField(['type' => 'text', 'key' => 'headline', 'is_required' => false]);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        // maxlength not set in settings → placeholder dropped, no max rule
        $this->assertSame(['nullable', 'string'], $rules['data.headline']);
    }

    public function test_optional_text_field_with_maxlength_setting_includes_max_rule(): void
    {
        $field = $this->makeField([
            'type'     => 'text',
            'key'      => 'headline',
            'settings' => ['maxlength' => 100],
        ]);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertSame(['nullable', 'string', 'max:100'], $rules['data.headline']);
    }

    public function test_required_field_replaces_nullable_with_required(): void
    {
        $field = $this->makeField([
            'type'        => 'text',
            'key'         => 'name',
            'is_required' => true,
        ]);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertContains('required', $rules['data.name']);
        $this->assertNotContains('nullable', $rules['data.name']);
    }

    public function test_email_field_includes_email_validation_rule(): void
    {
        $field = $this->makeField(['type' => 'email', 'key' => 'contact']);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertContains('email:rfc,dns', $rules['data.contact']);
    }

    public function test_number_field_without_settings_drops_unresolvable_placeholder_rules(): void
    {
        $field = $this->makeField(['type' => 'number', 'key' => 'price', 'settings' => null]);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        // min:{min} and max:{max} have no settings to resolve → dropped
        $this->assertSame(['nullable', 'numeric'], $rules['data.price']);
    }

    public function test_number_field_with_min_max_settings_resolves_range_rules(): void
    {
        $field = $this->makeField([
            'type'     => 'number',
            'key'      => 'stars',
            'settings' => ['min' => 1, 'max' => 5],
        ]);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertSame(['nullable', 'numeric', 'min:1', 'max:5'], $rules['data.stars']);
    }

    public function test_datetime_field_resolves_to_flexible_date_rule(): void
    {
        // The catalog has two conflicting date_format rules; resolver normalises to 'date'.
        $field = $this->makeField(['type' => 'datetime', 'key' => 'check_in']);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertSame(['nullable', 'date'], $rules['data.check_in']);
    }

    public function test_gallery_field_generates_parent_array_rule_and_item_integer_rule(): void
    {
        $field = $this->makeField(['type' => 'gallery', 'key' => 'photos']);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertArrayHasKey('data.photos', $rules);
        $this->assertArrayHasKey('data.photos.*', $rules);
        $this->assertContains('array', $rules['data.photos']);
        $this->assertContains('integer', $rules['data.photos.*']);
    }

    public function test_checkbox_field_generates_parent_array_rule_and_item_string_rule(): void
    {
        $field = $this->makeField(['type' => 'checkbox', 'key' => 'amenities']);
        $rules = (new FieldValidationResolver())->resolveForField($field);

        $this->assertArrayHasKey('data.amenities', $rules);
        $this->assertArrayHasKey('data.amenities.*', $rules);
        $this->assertContains('array', $rules['data.amenities']);
        $this->assertContains('string', $rules['data.amenities.*']);
    }

    // -------------------------------------------------------- integration: FormRequest enforcement

    public function test_form_request_rejects_invalid_email_for_email_field(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'email',
            'key'            => 'contact_email',
            'label'          => 'Contact Email',
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'status' => 'draft',
                'data'   => ['contact_email' => 'not-an-email'],
            ])
            ->assertSessionHasErrors('data.contact_email');
    }

    public function test_form_request_rejects_value_exceeding_text_maxlength(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'short_bio',
            'label'          => 'Short Bio',
            'settings'       => ['maxlength' => 10],
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'status' => 'draft',
                'data'   => ['short_bio' => str_repeat('x', 11)],
            ])
            ->assertSessionHasErrors('data.short_bio');
    }

    public function test_form_request_rejects_required_field_when_value_is_missing(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'headline',
            'label'          => 'Headline',
            'is_required'    => true,
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'status' => 'draft',
                'data'   => [],  // headline not submitted
            ])
            ->assertSessionHasErrors('data.headline');
    }

    public function test_form_request_allows_null_for_optional_field(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        Field::create([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'notes',
            'label'          => 'Notes',
            'is_required'    => false,
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.entries.create', $type))
            ->post(route('admin.content-types.entries.store', $type), [
                'status' => 'draft',
                'data'   => ['notes' => null],
            ])
            ->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeType(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'hotel',
            'label_singular' => 'Hotel',
            'label_plural'   => 'Hotels',
            'supports'       => ['title'],
        ], $attrs));
    }

    /**
     * @return array{0: ContentType, 1: FieldGroup}
     */
    private function makeTypeWithGroup(array $typeAttrs = []): array
    {
        $type  = $this->makeType($typeAttrs);
        $group = FieldGroup::create([
            'content_type_id' => $type->id,
            'label'           => 'Details',
            'key'             => 'details',
        ]);

        return [$type, $group];
    }

    private function makeField(array $attrs = []): Field
    {
        $type  = $this->makeType();
        $group = FieldGroup::create([
            'content_type_id' => $type->id,
            'label'           => 'Details',
            'key'             => 'details',
        ]);

        return Field::create(array_merge([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'test_field',
            'label'          => 'Test Field',
            'is_required'    => false,
            'settings'       => null,
        ], $attrs));
    }
}
