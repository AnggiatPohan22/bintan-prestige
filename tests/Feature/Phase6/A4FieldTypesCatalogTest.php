<?php

namespace Tests\Feature\Phase6;

use App\Support\FieldTypeRegistry;
use Tests\TestCase;

/**
 * Phase 6 — A4: config/field-types.php catalog + FieldTypeRegistry.
 *
 * No DB: the catalog is a pure config file, read via FieldTypeRegistry.
 */
class A4FieldTypesCatalogTest extends TestCase
{
    /** All 18 type keys the catalog must ship with. */
    private const EXPECTED_TYPES = [
        // basic
        'text', 'textarea', 'richtext', 'number', 'email', 'url',
        // choice
        'toggle', 'select', 'radio', 'checkbox',
        // date_time
        'date', 'datetime',
        // media
        'image', 'gallery', 'file',
        // relational
        'relationship',
        // advanced
        'color', 'repeater',
    ];

    /** Types the handoff §5 marks as filterable (→ content_entry_index). */
    private const FILTERABLE_TYPES = [
        'text', 'textarea', 'number', 'toggle', 'select', 'radio',
        'date', 'datetime', 'email', 'url', 'relationship',
    ];

    /** Required keys every catalog entry must declare. */
    private const REQUIRED_KEYS = [
        'label', 'icon', 'category', 'description',
        'cast', 'is_filterable', 'sanitizer',
        'settings_schema', 'validation_rules',
        'admin_partial', 'render_partial',
    ];

    public function test_all_required_types_are_present(): void
    {
        $keys = FieldTypeRegistry::keys();

        foreach (self::EXPECTED_TYPES as $type) {
            $this->assertContains(
                $type,
                $keys,
                "Field type '{$type}' is missing from config/field-types.php."
            );
        }

        $this->assertCount(
            count(self::EXPECTED_TYPES),
            $keys,
            'Catalog type count does not match the expected 18 types.'
        );
    }

    public function test_each_type_has_all_required_keys(): void
    {
        foreach (FieldTypeRegistry::all() as $type => $def) {
            foreach (self::REQUIRED_KEYS as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $def,
                    "Field type '{$type}' is missing required key '{$key}'."
                );
            }

            $this->assertIsString($def['label'],    "'{$type}'.label must be a string.");
            $this->assertIsString($def['icon'],     "'{$type}'.icon must be a string.");
            $this->assertIsString($def['category'], "'{$type}'.category must be a string.");
            $this->assertIsBool($def['is_filterable'], "'{$type}'.is_filterable must be a bool.");
            $this->assertIsArray($def['settings_schema'],  "'{$type}'.settings_schema must be an array.");
            $this->assertIsArray($def['validation_rules'], "'{$type}'.validation_rules must be an array.");
        }
    }

    public function test_filterable_types_match_specification(): void
    {
        $filterableKeys = FieldTypeRegistry::filterableKeys();

        foreach (self::FILTERABLE_TYPES as $type) {
            $this->assertContains(
                $type,
                $filterableKeys,
                "'{$type}' should be filterable per handoff §5 but is not."
            );
        }

        // Types that are NOT filterable should not appear in the filterable set.
        $nonFilterable = array_diff(self::EXPECTED_TYPES, self::FILTERABLE_TYPES);
        foreach ($nonFilterable as $type) {
            $this->assertNotContains(
                $type,
                $filterableKeys,
                "'{$type}' should NOT be filterable per handoff §5 but is."
            );
        }
    }

    public function test_registry_helpers_work_correctly(): void
    {
        $this->assertTrue(FieldTypeRegistry::exists('text'));
        $this->assertFalse(FieldTypeRegistry::exists('nonexistent_type'));

        $def = FieldTypeRegistry::get('text');
        $this->assertNotNull($def);
        $this->assertSame('Text', $def['label']);
        $this->assertSame('string', $def['cast']);
        $this->assertTrue($def['is_filterable']);

        $this->assertNull(FieldTypeRegistry::get('nonexistent_type'));

        $allKeys = FieldTypeRegistry::keys();
        $this->assertSame(array_keys(FieldTypeRegistry::all()), $allKeys);
    }
}
