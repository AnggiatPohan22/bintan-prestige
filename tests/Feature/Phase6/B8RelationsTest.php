<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentEntryRelation;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B8 — Relationships
 *
 * Verifies that ContentEntryRelationService projects relationship-type field
 * values into the normalised content_entry_relations table, and that the
 * ContentEntry query relationships (forward + reverse) work.
 */
class B8RelationsTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- projection

    public function test_relationship_field_creates_relation_rows(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $targetA = $this->makeEntry($type);
        $targetB = $this->makeEntry($type);

        $source = $this->makeEntry($type, ['data' => ['related' => [$targetA->id, $targetB->id]]]);

        $this->assertDatabaseHas('content_entry_relations', [
            'source_entry_id' => $source->id,
            'target_entry_id' => $targetA->id,
            'field_key'       => 'related',
            'sort_order'      => 0,
        ]);
        $this->assertDatabaseHas('content_entry_relations', [
            'source_entry_id' => $source->id,
            'target_entry_id' => $targetB->id,
            'field_key'       => 'related',
            'sort_order'      => 1,
        ]);
    }

    public function test_sort_order_preserves_array_order(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $t1 = $this->makeEntry($type);
        $t2 = $this->makeEntry($type);
        $t3 = $this->makeEntry($type);

        // Deliberately out of numeric order.
        $source = $this->makeEntry($type, ['data' => ['related' => [$t3->id, $t1->id, $t2->id]]]);

        $ordered = ContentEntryRelation::where('source_entry_id', $source->id)
            ->orderBy('sort_order')
            ->pluck('target_entry_id')
            ->all();

        $this->assertSame([$t3->id, $t1->id, $t2->id], $ordered);
    }

    public function test_non_existent_target_id_is_filtered_out(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $valid = $this->makeEntry($type);

        // 999999 does not exist → must be dropped (would otherwise violate the FK).
        $source = $this->makeEntry($type, ['data' => ['related' => [$valid->id, 999999]]]);

        $this->assertDatabaseHas('content_entry_relations', [
            'source_entry_id' => $source->id,
            'target_entry_id' => $valid->id,
        ]);
        $this->assertDatabaseMissing('content_entry_relations', [
            'source_entry_id' => $source->id,
            'target_entry_id' => 999999,
        ]);
        $this->assertSame(1, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    public function test_entry_cannot_relate_to_itself(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $source = $this->makeEntry($type);
        $source->setFieldValue('related', [$source->id]);
        $source->save();

        $this->assertSame(0, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    // ---------------------------------------------------------------- update / cleanup

    public function test_updating_data_syncs_added_and_removed_targets(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $t1 = $this->makeEntry($type);
        $t2 = $this->makeEntry($type);

        $source = $this->makeEntry($type, ['data' => ['related' => [$t1->id]]]);
        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id, 'target_entry_id' => $t1->id]);

        // Replace t1 with t2.
        $source->setFieldValue('related', [$t2->id]);
        $source->save();

        $this->assertDatabaseMissing('content_entry_relations', ['source_entry_id' => $source->id, 'target_entry_id' => $t1->id]);
        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id, 'target_entry_id' => $t2->id]);
        $this->assertSame(1, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    public function test_clearing_relationship_value_removes_all_rows(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $target = $this->makeEntry($type);
        $source = $this->makeEntry($type, ['data' => ['related' => [$target->id]]]);
        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id]);

        $source->setFieldValue('related', []);
        $source->save();

        $this->assertDatabaseMissing('content_entry_relations', ['source_entry_id' => $source->id]);
    }

    public function test_non_relationship_fields_produce_no_relation_rows(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeField($group, ['type' => 'text', 'key' => 'headline']);

        $source = $this->makeEntry($type, ['data' => ['headline' => 'Hello']]);

        $this->assertSame(0, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    public function test_multiple_relationship_fields_are_keyed_separately(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'guides');
        $this->makeRelationField($group, 'hotels');

        $g = $this->makeEntry($type);
        $h = $this->makeEntry($type);

        $source = $this->makeEntry($type, ['data' => ['guides' => [$g->id], 'hotels' => [$h->id]]]);

        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id, 'field_key' => 'guides', 'target_entry_id' => $g->id]);
        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id, 'field_key' => 'hotels', 'target_entry_id' => $h->id]);
        $this->assertSame(2, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    // ---------------------------------------------------------------- cascade

    public function test_force_deleting_source_cascades_relations(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $target = $this->makeEntry($type);
        $source = $this->makeEntry($type, ['data' => ['related' => [$target->id]]]);
        $sourceId = $source->id;

        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $sourceId]);

        $source->forceDelete();

        $this->assertDatabaseMissing('content_entry_relations', ['source_entry_id' => $sourceId]);
    }

    public function test_force_deleting_target_cascades_relations(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $target = $this->makeEntry($type);
        $source = $this->makeEntry($type, ['data' => ['related' => [$target->id]]]);
        $targetId = $target->id;

        $this->assertDatabaseHas('content_entry_relations', ['target_entry_id' => $targetId]);

        $target->forceDelete();

        $this->assertDatabaseMissing('content_entry_relations', ['target_entry_id' => $targetId]);
    }

    // ---------------------------------------------------------------- restore re-sync

    public function test_restore_re_syncs_relations(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $target = $this->makeEntry($type);
        $source = $this->makeEntry($type, ['data' => ['related' => [$target->id]]]);

        $source->delete();  // soft delete (relation rows remain)
        $source->restore(); // triggers 'restored' → re-sync

        $this->assertDatabaseHas('content_entry_relations', ['source_entry_id' => $source->id, 'target_entry_id' => $target->id]);
    }

    // ---------------------------------------------------------------- query relationships

    public function test_related_entries_returns_linked_entries_in_order(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $t1 = $this->makeEntry($type, ['title' => 'First']);
        $t2 = $this->makeEntry($type, ['title' => 'Second']);

        $source = $this->makeEntry($type, ['data' => ['related' => [$t2->id, $t1->id]]]);

        $titles = $source->relatedEntries()->get()->pluck('title')->all();

        $this->assertSame(['Second', 'First'], $titles);
    }

    public function test_relating_entries_reverse_lookup(): void
    {
        [$type, $group] = $this->makeTypeWithGroup();
        $this->makeRelationField($group, 'related');

        $target = $this->makeEntry($type, ['title' => 'Target']);
        $a = $this->makeEntry($type, ['title' => 'A', 'data' => ['related' => [$target->id]]]);
        $b = $this->makeEntry($type, ['title' => 'B', 'data' => ['related' => [$target->id]]]);

        $sources = $target->relatingEntries()->get()->pluck('title')->sort()->values()->all();

        $this->assertSame(['A', 'B'], $sources);
    }

    // ---------------------------------------------------------------- no field groups

    public function test_entry_with_no_field_groups_produces_no_relations(): void
    {
        $type   = $this->makeType();
        $source = $this->makeEntry($type, ['data' => ['related' => [1, 2, 3]]]);

        $this->assertSame(0, ContentEntryRelation::where('source_entry_id', $source->id)->count());
    }

    // ---------------------------------------------------------------- helpers

    private function makeType(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'tour',
            'label_singular' => 'Tour',
            'label_plural'   => 'Tours',
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

    private function makeRelationField(FieldGroup $group, string $key): Field
    {
        return $this->makeField($group, ['type' => 'relationship', 'key' => $key]);
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
            'title'           => 'Entry ' . uniqid(),
            'status'          => 'draft',
        ], $attrs));
    }
}
