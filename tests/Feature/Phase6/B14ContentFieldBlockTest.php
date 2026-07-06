<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\ContentFieldResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B14 — Builder bridge: content_field block.
 *
 * Displays a single field value from the current entry (on entry bodies) or a
 * specific entry by ID.
 */
class B14ContentFieldBlockTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- registry

    public function test_content_field_block_is_registered(): void
    {
        $blocks = config('blocks');
        $this->assertArrayHasKey('content_field', $blocks);
        $this->assertNotNull(collect($blocks['content_field']['fields'])->firstWhere('key', 'field_key'));
    }

    // ---------------------------------------------------------------- resolver (unit)

    public function test_resolver_reads_current_entry_field(): void
    {
        [$type, $group] = $this->typeWithGroup();
        $this->field($group, 'tagline', 'text', 'Tagline');
        $entry = $this->entry($type, ['data' => ['tagline' => 'Island vibes']]);

        $result = (new ContentFieldResolver())->resolve(['field_key' => 'tagline'], $entry);

        $this->assertNotNull($result);
        $this->assertSame('Island vibes', $result['text']);
        $this->assertSame('Tagline', $result['label']);
    }

    public function test_resolver_returns_null_for_blank_key_or_missing_value(): void
    {
        [$type] = $this->typeWithGroup();
        $entry = $this->entry($type, ['data' => ['tagline' => 'x']]);

        $this->assertNull((new ContentFieldResolver())->resolve(['field_key' => ''], $entry));
        $this->assertNull((new ContentFieldResolver())->resolve(['field_key' => 'nonexistent'], $entry));
        $this->assertNull((new ContentFieldResolver())->resolve(['field_key' => 'tagline'], null));
    }

    public function test_resolver_formats_toggle_as_yes_no(): void
    {
        [$type, $group] = $this->typeWithGroup();
        $this->field($group, 'featured', 'toggle', 'Featured');
        $entry = $this->entry($type, ['data' => ['featured' => '1']]);

        $result = (new ContentFieldResolver())->resolve(['field_key' => 'featured'], $entry);
        $this->assertSame('Yes', $result['text']);
    }

    public function test_resolver_provides_safe_href_for_valid_url_only(): void
    {
        [$type, $group] = $this->typeWithGroup();
        $this->field($group, 'website', 'url', 'Website');

        $safe = $this->entry($type, ['data' => ['website' => 'https://bintan.test']]);
        $result = (new ContentFieldResolver())->resolve(['field_key' => 'website'], $safe);
        $this->assertSame('https://bintan.test', $result['href']);

        // Unsafe scheme → no href, renders as plain text (no javascript: link).
        $unsafe = $this->entry($type, ['data' => ['website' => 'javascript:alert(1)']]);
        $result = (new ContentFieldResolver())->resolve(['field_key' => 'website'], $unsafe);
        $this->assertSame('', $result['href']);
        $this->assertSame('javascript:alert(1)', $result['text']);
    }

    public function test_resolver_custom_label_overrides_field_label(): void
    {
        [$type, $group] = $this->typeWithGroup();
        $this->field($group, 'tagline', 'text', 'Tagline');
        $entry = $this->entry($type, ['data' => ['tagline' => 'x']]);

        $result = (new ContentFieldResolver())->resolve(['field_key' => 'tagline', 'label' => 'Motto'], $entry);
        $this->assertSame('Motto', $result['label']);
    }

    public function test_resolver_specific_entry_id_ignores_current(): void
    {
        [$type, $group] = $this->typeWithGroup();
        $this->field($group, 'tagline', 'text', 'Tagline');
        $target  = $this->entry($type, ['data' => ['tagline' => 'From target'], 'status' => 'published']);
        $current = $this->entry($type, ['data' => ['tagline' => 'From current'], 'status' => 'published']);

        $result = (new ContentFieldResolver())->resolve(['field_key' => 'tagline', 'entry_id' => $target->id], $current);
        $this->assertSame('From target', $result['text']);
    }

    public function test_resolver_specific_entry_must_be_published_and_public(): void
    {
        [$type, $group] = $this->typeWithGroup(['is_public' => false]);
        $this->field($group, 'tagline', 'text', 'Tagline');
        $hidden = $this->entry($type, ['data' => ['tagline' => 'secret'], 'status' => 'published']);

        $this->assertNull((new ContentFieldResolver())->resolve(['field_key' => 'tagline', 'entry_id' => $hidden->id], null));
    }

    // ---------------------------------------------------------------- render on entry body

    public function test_content_field_renders_current_entry_value_on_body(): void
    {
        [$type, $group] = $this->typeWithGroup(['route_base' => 'hotel', 'supports' => ['title', 'slug', 'editor']]);
        $this->field($group, 'tagline', 'text', 'Tagline');
        $entry = $this->entry($type, [
            'title' => 'Bintan Lagoon', 'slug' => 'bintan-lagoon',
            'status' => 'published', 'data' => ['tagline' => 'Beachfront paradise'],
        ]);
        $entry->blocks()->create([
            'block_type' => 'content_field',
            'data'       => ['field_key' => 'tagline', 'show_label' => true],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->get(url('/hotel/bintan-lagoon'))
            ->assertOk()
            ->assertSee('Beachfront paradise')
            ->assertSee('Tagline');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array{0: ContentType, 1: FieldGroup}
     */
    private function typeWithGroup(array $attrs = []): array
    {
        $type = ContentType::create(array_merge([
            'slug'           => 'hotel-' . uniqid(),
            'label_singular' => 'Hotel',
            'label_plural'   => 'Hotels',
            'is_public'      => true,
            'is_active'      => true,
            'has_archive'    => true,
            'route_base'     => 'hotel-' . uniqid(),
            'supports'       => ['title', 'slug'],
        ], $attrs));

        $group = FieldGroup::create([
            'content_type_id' => $type->id,
            'label'           => 'Details',
            'key'             => 'details',
        ]);

        return [$type, $group];
    }

    private function field(FieldGroup $group, string $key, string $type, string $label): Field
    {
        return Field::create([
            'field_group_id' => $group->id,
            'type'           => $type,
            'key'            => $key,
            'label'          => $label,
        ]);
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'        => 'Entry ' . uniqid(),
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $attrs));
    }
}
