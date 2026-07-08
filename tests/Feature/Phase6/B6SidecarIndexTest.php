<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentEntryIndex;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\ContentEntryIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that ContentEntryIndexService correctly projects filterable field
 * values into the content_entry_index sidecar table.
 */
class B6SidecarIndexTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- string types

    public function test_filterable_text_field_is_indexed_in_value_string(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'headline', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['headline' => 'Bintan Resort']]);

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entry->id,
            'field_key'        => 'headline',
            'value_string'     => 'Bintan Resort',
            'value_number'     => null,
            'value_date'       => null,
        ]);
    }

    // ---------------------------------------------------------------- number type

    public function test_filterable_number_field_is_indexed_in_value_number(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'number', 'key' => 'stars', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['stars' => '4']]);

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entry->id,
            'field_key'        => 'stars',
            'value_string'     => null,
            'value_number'     => '4.000000',
            'value_date'       => null,
        ]);
    }

    // ---------------------------------------------------------------- date type

    public function test_filterable_date_field_is_indexed_in_value_date(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'date', 'key' => 'check_in', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['check_in' => '2026-08-15']]);

        $row = ContentEntryIndex::where('content_entry_id', $entry->id)
            ->where('field_key', 'check_in')
            ->first();

        $this->assertNotNull($row);
        $this->assertNull($row->value_string);
        $this->assertNull($row->value_number);
        // Cast to Carbon date — format-agnostic comparison.
        $this->assertSame('2026-08-15', $row->value_date->toDateString());
    }

    // ---------------------------------------------------------------- boolean type

    public function test_filterable_toggle_field_is_indexed_as_1_or_0(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'toggle', 'key' => 'featured', 'is_filterable' => true]);

        $entryOn  = $this->makeEntry($type, ['data' => ['featured' => '1']]);
        $entryOff = $this->makeEntry($type, ['data' => ['featured' => '0']]);

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entryOn->id,
            'field_key'        => 'featured',
            'value_string'     => '1',
        ]);

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entryOff->id,
            'field_key'        => 'featured',
            'value_string'     => '0',
        ]);
    }

    // ---------------------------------------------------------------- non-filterable

    public function test_non_filterable_field_produces_no_index_row(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'richtext', 'key' => 'body', 'is_filterable' => false]);

        $this->makeEntry($type, ['data' => ['body' => '<p>Hello</p>']]);

        $this->assertDatabaseMissing('content_entry_index', ['field_key' => 'body']);
    }

    // ---------------------------------------------------------------- null / empty

    public function test_null_field_value_does_not_produce_index_row(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'tagline', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['tagline' => null]]);

        $this->assertDatabaseMissing('content_entry_index', [
            'content_entry_id' => $entry->id,
            'field_key'        => 'tagline',
        ]);
    }

    // ---------------------------------------------------------------- update / stale cleanup

    public function test_index_value_is_updated_when_entry_data_changes(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'name', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['name' => 'Old Name']]);

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entry->id,
            'field_key'        => 'name',
            'value_string'     => 'Old Name',
        ]);

        $entry->setFieldValue('name', 'New Name');
        $entry->save();

        $this->assertDatabaseHas('content_entry_index', [
            'content_entry_id' => $entry->id,
            'field_key'        => 'name',
            'value_string'     => 'New Name',
        ]);

        // Only one row should exist.
        $this->assertSame(
            1,
            ContentEntryIndex::where('content_entry_id', $entry->id)->where('field_key', 'name')->count()
        );
    }

    public function test_stale_index_row_is_removed_when_value_becomes_null(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'promo', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['promo' => 'Summer Sale']]);
        $this->assertDatabaseHas('content_entry_index', ['content_entry_id' => $entry->id, 'field_key' => 'promo']);

        $entry->setFieldValue('promo', null);
        $entry->save();

        $this->assertDatabaseMissing('content_entry_index', ['content_entry_id' => $entry->id, 'field_key' => 'promo']);
    }

    // ---------------------------------------------------------------- multiple fields

    public function test_multiple_filterable_fields_all_generate_index_rows(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text',   'key' => 'name',   'is_filterable' => true]);
        $this->makeField($group, ['type' => 'number', 'key' => 'price',  'is_filterable' => true]);
        $this->makeField($group, ['type' => 'select', 'key' => 'region', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => [
            'name'   => 'Bintan Lagoon',
            'price'  => '2500000',
            'region' => 'north',
        ]]);

        $this->assertSame(3, ContentEntryIndex::where('content_entry_id', $entry->id)->count());
    }

    // ---------------------------------------------------------------- hard delete via cascade

    public function test_index_rows_are_removed_via_cascade_on_force_delete(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'title', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['title' => 'Test']]);
        $entryId = $entry->id;

        $this->assertDatabaseHas('content_entry_index', ['content_entry_id' => $entryId]);

        $entry->delete();    // soft delete
        $entry->forceDelete();

        $this->assertDatabaseMissing('content_entry_index', ['content_entry_id' => $entryId]);
    }

    // ---------------------------------------------------------------- restore

    public function test_index_is_re_synced_when_entry_is_restored(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'loc', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['loc' => 'Bintan']]);
        $this->assertDatabaseHas('content_entry_index', ['content_entry_id' => $entry->id, 'field_key' => 'loc']);

        $entry->delete();   // soft delete (index rows remain)
        $entry->restore();  // triggers 'restored' → sync() → re-projects

        $this->assertDatabaseHas('content_entry_index', ['content_entry_id' => $entry->id, 'field_key' => 'loc', 'value_string' => 'Bintan']);
    }

    // ---------------------------------------------------------------- no field groups

    public function test_no_index_rows_created_for_entry_with_no_field_groups(): void
    {
        $type  = $this->makeType();
        $entry = $this->makeEntry($type, ['data' => ['any_key' => 'any_value']]);

        $this->assertSame(0, ContentEntryIndex::where('content_entry_id', $entry->id)->count());
    }

    // ---------------------------------------------------------------- direct service: relationship (array) type

    public function test_relationship_array_is_indexed_as_csv_string(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'relationship', 'key' => 'related', 'is_filterable' => true]);

        $entry = $this->makeEntry($type, ['data' => ['related' => [10, 20, 30]]]);

        $row = ContentEntryIndex::where('content_entry_id', $entry->id)
            ->where('field_key', 'related')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('10,20,30', $row->value_string);
    }

    // ---------------------------------------------------------------- helpers

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

    private function makeField(FieldGroup $group, array $attrs = []): Field
    {
        return Field::create(array_merge([
            'field_group_id' => $group->id,
            'type'           => 'text',
            'key'            => 'field_' . uniqid(),
            'label'          => 'Field',
            'is_filterable'  => false,
        ], $attrs));
    }

    private function makeEntry(ContentType $type, array $attrs = []): ContentEntry
    {
        return ContentEntry::create(array_merge([
            'content_type_id' => $type->id,
            'title'           => 'Test Entry',
            'status'          => 'draft',
        ], $attrs));
    }
}
