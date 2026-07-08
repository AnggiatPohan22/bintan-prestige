<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\FieldTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the FieldInput Blade component renders correctly for every
 * catalog type without throwing an exception.
 */
class B3FieldRenderingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- component renders

    public function test_field_input_component_renders_for_all_catalog_types(): void
    {
        $group = $this->makeGroup();

        foreach (FieldTypeRegistry::keys() as $type) {
            $field = Field::create([
                'field_group_id' => $group->id,
                'type'           => $type,
                'key'            => $type,
                'label'          => ucfirst($type) . ' Field',
            ]);

            $html = $this->renderComponent($field);

            $this->assertNotEmpty($html, "Component rendered empty HTML for type: {$type}");
            $this->assertStringContainsString(ucfirst($type) . ' Field', $html, "Label missing for type: {$type}");
        }
    }

    public function test_required_asterisk_appears_when_field_is_required(): void
    {
        $field = $this->makeField(['type' => 'text', 'key' => 'title', 'is_required' => true]);
        $html  = $this->renderComponent($field);

        $this->assertStringContainsString('*', $html);
    }

    public function test_instructions_appear_below_label(): void
    {
        $field = $this->makeField([
            'type'         => 'text',
            'key'          => 'headline',
            'instructions' => 'Keep it under 80 characters.',
        ]);
        $html = $this->renderComponent($field);

        $this->assertStringContainsString('Keep it under 80 characters.', $html);
    }

    public function test_name_prefix_is_applied_to_input(): void
    {
        $field = $this->makeField(['type' => 'text', 'key' => 'star_rating']);
        $html  = $this->renderComponent($field, value: null, namePrefix: 'entry');

        $this->assertStringContainsString('name="entry[star_rating]"', $html);
    }

    // -------------------------------------------------------- specific types

    public function test_text_field_renders_input_element(): void
    {
        $field = $this->makeField(['type' => 'text', 'key' => 'name']);
        $html  = $this->renderComponent($field, value: 'Bintan Lagoon');

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('Bintan Lagoon', $html);
    }

    public function test_select_renders_options_from_settings(): void
    {
        $field = $this->makeField([
            'type'     => 'select',
            'key'      => 'stars',
            'settings' => ['options' => [
                ['label' => '3 Stars', 'value' => '3'],
                ['label' => '5 Stars', 'value' => '5'],
            ]],
        ]);
        $html = $this->renderComponent($field, value: '3');

        $this->assertStringContainsString('3 Stars', $html);
        $this->assertStringContainsString('5 Stars', $html);
    }

    public function test_select_with_no_options_shows_warning(): void
    {
        $field = $this->makeField(['type' => 'select', 'key' => 'empty_select']);
        $html  = $this->renderComponent($field);

        $this->assertStringContainsString('No options defined', $html);
    }

    public function test_toggle_renders_hidden_and_checkbox_pair(): void
    {
        $field = $this->makeField(['type' => 'toggle', 'key' => 'is_featured']);
        $html  = $this->renderComponent($field, value: true);

        // Must have both: hidden=0 fallback and checkbox=1
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function test_color_field_renders_color_input_and_text_input(): void
    {
        $field = $this->makeField(['type' => 'color', 'key' => 'brand_color']);
        $html  = $this->renderComponent($field, value: '#FF5733');

        $this->assertStringContainsString('type="color"', $html);
        $this->assertStringContainsString('#FF5733', $html);
    }

    public function test_datetime_field_normalises_value_to_datetime_local_format(): void
    {
        $field = $this->makeField(['type' => 'datetime', 'key' => 'check_in']);
        $html  = $this->renderComponent($field, value: '2026-07-01 09:00:00');

        $this->assertStringContainsString('type="datetime-local"', $html);
        $this->assertStringContainsString('2026-07-01T09:00', $html);
    }

    public function test_gallery_renders_add_images_button(): void
    {
        $field = $this->makeField(['type' => 'gallery', 'key' => 'photos']);
        $html  = $this->renderComponent($field, value: []);

        $this->assertStringContainsString('Add Images', $html);
        $this->assertStringContainsString('field-media-gallery', $html);
    }

    public function test_repeater_shows_no_rows_empty_state(): void
    {
        $field = $this->makeField([
            'type'     => 'repeater',
            'key'      => 'itinerary',
            'settings' => ['sub_fields' => [
                ['key' => 'day', 'label' => 'Day'],
                ['key' => 'activity', 'label' => 'Activity'],
            ]],
        ]);
        $html = $this->renderComponent($field, value: []);

        $this->assertStringContainsString('No rows yet', $html);
        $this->assertStringContainsString('Add Row', $html);
    }

    public function test_relationship_without_content_type_shows_warning(): void
    {
        $field = $this->makeField(['type' => 'relationship', 'key' => 'related']);
        $html  = $this->renderComponent($field);

        $this->assertStringContainsString('No content type configured', $html);
    }

    public function test_relationship_with_content_type_shows_slug(): void
    {
        $field = $this->makeField([
            'type'     => 'relationship',
            'key'      => 'hotel',
            'settings' => ['content_type' => 'hotel', 'multiple' => false],
        ]);
        $html = $this->renderComponent($field);

        $this->assertStringContainsString('hotel', $html);
    }

    // -------------------------------------------------------- helpers

    private function makeGroup(): FieldGroup
    {
        $type = ContentType::create([
            'slug'           => 'test-ct',
            'label_singular' => 'Test',
            'label_plural'   => 'Tests',
        ]);

        return FieldGroup::create([
            'content_type_id' => $type->id,
            'label'           => 'Test Group',
            'key'             => 'test_group',
        ]);
    }

    private function makeField(array $attrs = []): Field
    {
        $group = $this->makeGroup();

        return Field::create(array_merge([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'default_field',
            'label'          => 'Default Field',
        ], $attrs));
    }

    /**
     * Render the <x-admin.field-input> anonymous component.
     *
     * @param  mixed $value
     */
    private function renderComponent(Field $field, mixed $value = null, string $namePrefix = 'data'): string
    {
        return $this->blade(
            '<x-admin.field-input :field="$field" :value="$value" :name-prefix="$namePrefix" />',
            compact('field', 'value', 'namePrefix')
        )->__toString();
    }
}
