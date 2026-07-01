<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTypeTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------- auth guard

    public function test_content_type_routes_require_admin(): void
    {
        $this->get(route('admin.content-types.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.content-types.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.index'))
            ->assertOk();
    }

    // -------------------------------------------------------- index

    public function test_index_lists_active_and_archived_types(): void
    {
        $active   = $this->type(['label_plural' => 'Blog Posts', 'slug' => 'blog-post']);
        $archived = $this->type(['label_plural' => 'Hotels',    'slug' => 'hotel']);
        $archived->delete();

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.index'))
            ->assertOk()
            ->assertSee('Blog Posts')
            ->assertSee('Hotels');
    }

    // -------------------------------------------------------- create / store

    public function test_admin_can_create_a_content_type_with_auto_slug_and_route_base(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.content-types.store'), [
                'label_singular' => 'Hotel Review',
                'label_plural'   => 'Hotel Reviews',
                'icon'           => 'star',
                'is_public'      => '1',
                'has_archive'    => '1',
                'supports'       => ['title', 'slug', 'seo'],
                'menu_position'  => '5',
                'is_active'      => '1',
            ]);

        $this->assertDatabaseHas('content_types', [
            'slug'           => 'hotel-review',
            'label_singular' => 'Hotel Review',
            'label_plural'   => 'Hotel Reviews',
            'icon'           => 'star',
            'route_base'     => 'hotel-reviews',
            'is_public'      => true,
            'has_archive'    => true,
            'menu_position'  => 5,
        ]);

        $created = ContentType::where('slug', 'hotel-review')->firstOrFail();
        $response->assertRedirect(route('admin.content-types.edit', $created));
        $this->assertEquals(['title', 'slug', 'seo'], $created->supports);
    }

    public function test_slug_auto_generated_from_label_singular(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.store'), [
                'label_singular' => 'Case Study',
                'label_plural'   => 'Case Studies',
                'is_public'      => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('content_types', ['slug' => 'case-study']);
    }

    // -------------------------------------------------------- NOT-NULL default coercion

    public function test_blank_icon_falls_back_to_db_default_instead_of_null_error(): void
    {
        // Reproduces the reported bug: an empty icon field is turned into null by
        // ConvertEmptyStringsToNull, which then hit the NOT-NULL `icon` column.
        // prepareForValidation must coerce it back to the DB default.
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.store'), [
                'label_singular' => 'Blog',
                'label_plural'   => 'Blog Posts',
                'icon'           => '',   // blank → null via middleware
                'is_public'      => '1',
                'has_archive'    => '1',
                'supports'       => ['title', 'slug'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('content_types', [
            'slug' => 'blog',
            'icon' => 'file-lines',
        ]);
    }

    public function test_blank_menu_position_falls_back_to_zero(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.store'), [
                'label_singular' => 'Guide',
                'label_plural'   => 'Guides',
                'menu_position'  => '',   // blank → null via middleware
                'is_public'      => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('content_types', [
            'slug'          => 'guide',
            'menu_position' => 0,
        ]);
    }

    public function test_blank_icon_on_update_reverts_to_db_default(): void
    {
        $type = $this->type(['slug' => 'news', 'icon' => 'newspaper']);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.update', $type), [
                'slug'           => 'news',
                'label_singular' => $type->label_singular,
                'label_plural'   => $type->label_plural,
                'icon'           => '',
                'is_public'      => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('content_types', ['id' => $type->id, 'icon' => 'file-lines']);
    }

    // -------------------------------------------------------- validation

    public function test_validation_rejects_reserved_slugs(): void
    {
        $admin = $this->admin();

        foreach (['admin', 'products', 'pages', 'api'] as $reserved) {
            $this->actingAs($admin)
                ->from(route('admin.content-types.create'))
                ->post(route('admin.content-types.store'), [
                    'slug'           => $reserved,
                    'label_singular' => 'Test',
                    'label_plural'   => 'Tests',
                ])
                ->assertRedirect(route('admin.content-types.create'))
                ->assertSessionHasErrors('slug');
        }
    }

    public function test_validation_rejects_duplicate_slug(): void
    {
        $this->type(['slug' => 'my-type']);

        $this->actingAs($this->admin())
            ->from(route('admin.content-types.create'))
            ->post(route('admin.content-types.store'), [
                'slug'           => 'my-type',
                'label_singular' => 'Other',
                'label_plural'   => 'Others',
            ])
            ->assertRedirect(route('admin.content-types.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_route_base_auto_generated_from_label_plural_for_public_archive_type(): void
    {
        // When route_base is omitted but the type is public+has_archive, prepareForValidation
        // auto-fills it from label_plural so the user doesn't have to type it twice.
        $this->actingAs($this->admin())
            ->post(route('admin.content-types.store'), [
                'label_singular' => 'News Article',
                'label_plural'   => 'News Articles',
                'slug'           => 'news-article',
                'is_public'      => '1',
                'has_archive'    => '1',
                // route_base intentionally absent — must be auto-filled
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('content_types', [
            'slug'       => 'news-article',
            'route_base' => 'news-articles',
        ]);
    }

    public function test_validation_rejects_reserved_route_base(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.content-types.create'))
            ->post(route('admin.content-types.store'), [
                'slug'           => 'my-news',
                'label_singular' => 'News',
                'label_plural'   => 'News',
                'is_public'      => '1',
                'has_archive'    => '1',
                'route_base'     => 'admin',
            ])
            ->assertSessionHasErrors('route_base');
    }

    // -------------------------------------------------------- update

    public function test_admin_can_update_a_content_type(): void
    {
        $type = $this->type(['slug' => 'review', 'label_plural' => 'Reviews']);

        $this->actingAs($this->admin())
            ->put(route('admin.content-types.update', $type), [
                'slug'           => 'review',
                'label_singular' => 'Review',
                'label_plural'   => 'Guest Reviews',
                'is_public'      => '0',
                'supports'       => ['title', 'slug'],
            ])
            ->assertRedirect(route('admin.content-types.edit', $type));

        $this->assertDatabaseHas('content_types', [
            'id'           => $type->id,
            'label_plural' => 'Guest Reviews',
            'is_public'    => false,
        ]);
    }

    // -------------------------------------------------------- soft delete + restore

    public function test_admin_can_soft_delete_and_restore_a_content_type(): void
    {
        $type = $this->type(['slug' => 'event', 'label_plural' => 'Events']);

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.destroy', $type))
            ->assertRedirect(route('admin.content-types.index'));

        $this->assertSoftDeleted('content_types', ['id' => $type->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.content-types.restore', $type->id))
            ->assertRedirect(route('admin.content-types.index'));

        $this->assertDatabaseHas('content_types', ['id' => $type->id, 'deleted_at' => null]);
    }

    // -------------------------------------------------------- force delete

    public function test_admin_can_force_delete_an_archived_content_type(): void
    {
        $type = $this->type(['slug' => 'promo', 'label_plural' => 'Promos']);
        $type->delete();

        $this->actingAs($this->admin())
            ->delete(route('admin.content-types.force-delete', $type->id))
            ->assertRedirect(route('admin.content-types.index'));

        $this->assertDatabaseMissing('content_types', ['id' => $type->id]);
    }

    // -------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function type(array $overrides = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'default-type',
            'label_singular' => 'Default Type',
            'label_plural'   => 'Default Types',
            'supports'       => ['title', 'slug'],
        ], $overrides));
    }
}
