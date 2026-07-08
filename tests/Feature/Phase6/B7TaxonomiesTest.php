<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B7 — Taxonomies & Terms
 *
 * Covers: taxonomy CRUD, term CRUD, soft-delete / restore,
 * MySQL cascade on force-delete, and term attachment to content entries.
 */
class B7TaxonomiesTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- taxonomy CRUD

    public function test_admin_can_create_taxonomy(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.store'), [
                'slug'           => 'region',
                'label_singular' => 'Region',
                'label_plural'   => 'Regions',
                'is_hierarchical' => false,
                'sort_order'     => 0,
            ])
            ->assertRedirect(route('admin.taxonomies.index'));

        $this->assertDatabaseHas('taxonomies', [
            'slug'           => 'region',
            'label_singular' => 'Region',
        ]);
    }

    public function test_taxonomy_slug_is_unique(): void
    {
        Taxonomy::create(['slug' => 'tag', 'label_singular' => 'Tag', 'label_plural' => 'Tags']);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.store'), [
                'slug'           => 'tag',
                'label_singular' => 'Another Tag',
                'label_plural'   => 'Another Tags',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_taxonomy(): void
    {
        $taxonomy = Taxonomy::create([
            'slug'           => 'cat',
            'label_singular' => 'Category',
            'label_plural'   => 'Categories',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.taxonomies.update', $taxonomy), [
                'slug'           => 'cat',
                'label_singular' => 'Category',
                'label_plural'   => 'Blog Categories',
            ])
            ->assertRedirect(route('admin.taxonomies.index'));

        $this->assertDatabaseHas('taxonomies', ['id' => $taxonomy->id, 'label_plural' => 'Blog Categories']);
    }

    public function test_taxonomy_soft_delete_and_restore(): void
    {
        $taxonomy = Taxonomy::create([
            'slug'           => 'genre',
            'label_singular' => 'Genre',
            'label_plural'   => 'Genres',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.taxonomies.destroy', $taxonomy));

        $this->assertSoftDeleted('taxonomies', ['id' => $taxonomy->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.taxonomies.restore', $taxonomy->id));

        $this->assertDatabaseHas('taxonomies', ['id' => $taxonomy->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------- term CRUD

    public function test_admin_can_create_term(): void
    {
        $taxonomy = $this->makeTaxonomy();

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.terms.store', $taxonomy), [
                'name'       => 'North Bintan',
                'slug'       => 'north-bintan',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.taxonomies.terms.index', $taxonomy));

        $this->assertDatabaseHas('terms', [
            'taxonomy_id' => $taxonomy->id,
            'slug'        => 'north-bintan',
            'name'        => 'North Bintan',
        ]);
    }

    public function test_term_slug_is_unique_within_taxonomy(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $taxonomy->terms()->create(['name' => 'North', 'slug' => 'north']);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.terms.store', $taxonomy), [
                'name' => 'North Duplicate',
                'slug' => 'north',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_same_slug_allowed_in_different_taxonomies(): void
    {
        $t1 = $this->makeTaxonomy('region');
        $t2 = $this->makeTaxonomy('genre');

        $t1->terms()->create(['name' => 'North', 'slug' => 'north']);

        // Same slug in a different taxonomy — should pass
        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.terms.store', $t2), [
                'name' => 'North',
                'slug' => 'north',
            ])
            ->assertRedirect(route('admin.taxonomies.terms.index', $t2));

        $this->assertDatabaseHas('terms', ['taxonomy_id' => $t2->id, 'slug' => 'north']);
    }

    public function test_term_soft_delete_and_restore(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $term     = $taxonomy->terms()->create(['name' => 'South', 'slug' => 'south']);

        $this->actingAs($this->admin())
            ->delete(route('admin.taxonomies.terms.destroy', [$taxonomy, $term]));

        $this->assertSoftDeleted('terms', ['id' => $term->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.taxonomies.terms.restore', [$taxonomy, $term->id]));

        $this->assertDatabaseHas('terms', ['id' => $term->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------- cascade on force-delete

    public function test_force_deleting_taxonomy_cascades_to_terms(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $term     = $taxonomy->terms()->create(['name' => 'East', 'slug' => 'east']);
        $taxonomy->delete();

        $this->actingAs($this->admin())
            ->delete(route('admin.taxonomies.force-delete', $taxonomy->id));

        $this->assertDatabaseMissing('taxonomies', ['id' => $taxonomy->id]);
        $this->assertDatabaseMissing('terms', ['id' => $term->id]);
    }

    public function test_force_deleting_term_cascades_to_pivot(): void
    {
        [$type, $entry] = $this->makeTypeAndEntry();
        $taxonomy       = $this->makeTaxonomy();
        $term           = $taxonomy->terms()->create(['name' => 'West', 'slug' => 'west']);
        $entry->terms()->attach($term->id);

        $this->assertDatabaseHas('content_entry_term', ['term_id' => $term->id]);

        $term->delete();

        $this->actingAs($this->admin())
            ->delete(route('admin.taxonomies.terms.force-delete', [$taxonomy, $term->id]));

        $this->assertDatabaseMissing('terms', ['id' => $term->id]);
        $this->assertDatabaseMissing('content_entry_term', ['term_id' => $term->id]);
    }

    // ---------------------------------------------------------------- term attachment on entry

    public function test_terms_are_attached_when_entry_is_created(): void
    {
        [$type, ] = $this->makeTypeAndEntry(false);
        $taxonomy = $this->makeTaxonomy();
        $term     = $taxonomy->terms()->create(['name' => 'North', 'slug' => 'north']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'Test Entry',
                'status' => 'draft',
                'terms'  => [$term->id],
            ])
            ->assertRedirect();

        $entry = $type->entries()->first();
        $this->assertNotNull($entry);
        $this->assertDatabaseHas('content_entry_term', [
            'content_entry_id' => $entry->id,
            'term_id'          => $term->id,
        ]);
    }

    public function test_terms_are_synced_when_entry_is_updated(): void
    {
        [$type, $entry] = $this->makeTypeAndEntry();
        $taxonomy       = $this->makeTaxonomy();
        $t1             = $taxonomy->terms()->create(['name' => 'A', 'slug' => 'a']);
        $t2             = $taxonomy->terms()->create(['name' => 'B', 'slug' => 'b']);

        $entry->terms()->attach($t1->id);
        $this->assertDatabaseHas('content_entry_term', ['content_entry_id' => $entry->id, 'term_id' => $t1->id]);

        // Update to only $t2
        $this->actingAs($this->admin())
            ->put(route('admin.content-types.entries.update', [$type, $entry]), [
                'title'  => $entry->title,
                'status' => $entry->status,
                'terms'  => [$t2->id],
            ]);

        $this->assertDatabaseMissing('content_entry_term', ['content_entry_id' => $entry->id, 'term_id' => $t1->id]);
        $this->assertDatabaseHas('content_entry_term', ['content_entry_id' => $entry->id, 'term_id' => $t2->id]);
    }

    public function test_terms_are_detached_when_entry_is_updated_with_no_terms(): void
    {
        [$type, $entry] = $this->makeTypeAndEntry();
        $taxonomy       = $this->makeTaxonomy();
        $term           = $taxonomy->terms()->create(['name' => 'X', 'slug' => 'x']);
        $entry->terms()->attach($term->id);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.entries.update', [$type, $entry]), [
                'title'  => $entry->title,
                'status' => $entry->status,
                // no 'terms' key → sync with []
            ]);

        $this->assertDatabaseMissing('content_entry_term', ['content_entry_id' => $entry->id]);
    }

    public function test_invalid_term_id_fails_validation(): void
    {
        [$type, ] = $this->makeTypeAndEntry(false);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.store', $type), [
                'title'  => 'Entry',
                'status' => 'draft',
                'terms'  => [99999], // non-existent term
            ])
            ->assertSessionHasErrors('terms.0');
    }

    // ---------------------------------------------------------------- hierarchical terms

    public function test_term_can_have_a_parent(): void
    {
        $taxonomy = $this->makeTaxonomy(is_hierarchical: true);
        $parent   = $taxonomy->terms()->create(['name' => 'Asia', 'slug' => 'asia']);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomies.terms.store', $taxonomy), [
                'name'      => 'Indonesia',
                'slug'      => 'indonesia',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect(route('admin.taxonomies.terms.index', $taxonomy));

        $this->assertDatabaseHas('terms', [
            'taxonomy_id' => $taxonomy->id,
            'slug'        => 'indonesia',
            'parent_id'   => $parent->id,
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeTaxonomy(string $slug = 'region', bool $is_hierarchical = false): Taxonomy
    {
        return Taxonomy::create([
            'slug'             => $slug,
            'label_singular'   => ucfirst($slug),
            'label_plural'     => ucfirst($slug).'s',
            'is_hierarchical'  => $is_hierarchical,
            'content_type_ids' => null, // global
        ]);
    }

    /**
     * @return array{0: ContentType, 1: ContentEntry}|array{0: ContentType, 1: null}
     */
    private function makeTypeAndEntry(bool $withEntry = true): array
    {
        $type = ContentType::create([
            'slug'           => 'blog',
            'label_singular' => 'Blog Post',
            'label_plural'   => 'Blog Posts',
            'supports'       => ['title'],
        ]);

        if (! $withEntry) {
            /** @var array{0: ContentType, 1: null} */
            return [$type, null];
        }

        $entry = $type->entries()->create([
            'title'  => 'Test Post',
            'status' => 'draft',
        ]);

        /** @var array{0: ContentType, 1: ContentEntry} */
        return [$type, $entry];
    }
}
