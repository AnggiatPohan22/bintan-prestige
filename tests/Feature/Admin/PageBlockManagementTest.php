<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Services\PageBlockService;
use App\Support\InlineContentSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageBlockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_routes_require_an_authenticated_admin(): void
    {
        $page = $this->page();

        $this->post(route('admin.page-blocks.store', $page), [
            'block_type' => 'text',
        ])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.page-blocks.store', $page), ['block_type' => 'text'])
            ->assertForbidden();
    }

    public function test_admin_can_add_supported_blocks_with_defaults_and_next_sort_order(): void
    {
        $page = $this->page();
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'divider',
            'label' => 'Existing divider',
            'data' => ['style' => 'line'],
            'sort_order' => 4,
        ]);

        $this->actingAs($this->admin())->post(route('admin.page-blocks.store', $page), [
            'block_type' => 'hero',
            'label' => 'Opening Hero',
        ])->assertRedirect(route('admin.pages.edit', $page));

        $block = PageBlock::query()->where('label', 'Opening Hero')->firstOrFail();

        $this->assertSame($page->id, $block->page_id);
        $this->assertSame('hero', $block->block_type);
        $this->assertSame(5, $block->sort_order);
        $this->assertTrue($block->is_visible);
        $this->assertSame('', $block->data['title']);
        $this->assertSame('cover', $block->data['background']['size']);
    }

    public function test_block_store_rejects_an_unknown_block_type(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.page-blocks.store', $page), [
                'block_type' => 'unknown_block',
            ])->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('block_type');

        $this->assertDatabaseCount('page_blocks', 0);
    }

    public function test_registry_metadata_and_defaults_are_complete_for_every_block_type(): void
    {
        $service = app(PageBlockService::class);

        foreach (config('blocks') as $type => $definition) {
            $this->assertIsString($definition['label'] ?? null, "{$type} is missing a label.");
            $this->assertIsString($definition['icon'] ?? null, "{$type} is missing an icon.");
            $this->assertContains($definition['category'] ?? null, ['content', 'media', 'conversion', 'travel', 'layout']);
            $this->assertIsString($definition['description'] ?? null, "{$type} is missing a description.");
            $this->assertIsArray($definition['keywords'] ?? null, "{$type} is missing keywords.");
            $this->assertSame(
                ['background', 'spacing', 'alignment', 'children'],
                array_keys($definition['supports'] ?? []),
                "{$type} has an incomplete supports contract."
            );

            $defaults = $service->defaultDataFor($type);
            $this->assertArrayHasKey('background', $defaults, "{$type} is missing background defaults.");
            $this->assertSame($defaults, $service->validateAndSanitizeData($type, $defaults));
        }
    }

    public function test_a3_layout_schema_and_model_relationships_support_nested_blocks(): void
    {
        $this->assertTrue(Schema::hasColumn('page_blocks', 'parent_block_id'));

        $page = $this->page();
        $group = $this->block($page, 'group', 0);
        $heading = $this->block($page, 'heading', 0);
        $heading->update(['parent_block_id' => $group->id]);

        $this->assertTrue($heading->fresh()->parent->is($group));
        $this->assertTrue($group->children->first()->is($heading));
        $this->assertTrue($page->rootBlocks->first()->is($group));
    }

    public function test_admin_can_build_nested_columns_and_invalid_or_cyclic_parenting_is_rejected(): void
    {
        $page = $this->page();
        $columns = $this->block($page, 'columns', 0);
        $group = $this->block($page, 'group', 1);
        $text = $this->block($page, 'text', 2);
        $foreignGroup = $this->block($this->page('Foreign', 'foreign'), 'group', 0);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $group]), [
            'parent_block_id' => $columns->id,
            'data' => ['width' => 'contained', 'spacing' => 'md'],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $text]), [
            'parent_block_id' => $group->id,
            'data' => ['heading' => 'Nested text'],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame($columns->id, $group->fresh()->parent_block_id);
        $this->assertSame($group->id, $text->fresh()->parent_block_id);

        foreach ([
            [$text, $columns->id, 'Columns can contain Group blocks only.'],
            [$text, $foreignGroup->id, 'Choose a Group or Columns block from this page.'],
            [$columns, $group->id, 'A block cannot be moved inside its own descendants.'],
        ] as [$block, $parentId, $message]) {
            $this->actingAs($admin)
                ->from(route('admin.pages.edit', $page))
                ->put(route('admin.page-blocks.update', [$page, $block]), [
                    'parent_block_id' => $parentId,
                    'data' => $block->data ?? [],
                ])
                ->assertRedirect(route('admin.pages.edit', $page))
                ->assertSessionHasErrors(['parent_block_id' => $message]);
        }
    }

    public function test_nested_reorder_is_scoped_to_siblings_and_deleting_container_promotes_children(): void
    {
        $page = $this->page();
        $group = $this->block($page, 'group', 0);
        $first = $this->block($page, 'heading', 0);
        $second = $this->block($page, 'text', 1);
        $root = $this->block($page, 'divider', 1);
        $first->update(['parent_block_id' => $group->id]);
        $second->update(['parent_block_id' => $group->id]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.page-blocks.reorder', $page), [
            'ids' => [$second->id, $first->id],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.page-blocks.reorder', $page), ['ids' => [$first->id, $root->id]])
            ->assertSessionHasErrors('ids');

        $this->actingAs($admin)->delete(route('admin.page-blocks.destroy', [$page, $group]));
        $this->assertNull($first->fresh()->parent_block_id);
        $this->assertNull($second->fresh()->parent_block_id);
    }

    public function test_new_a3_blocks_store_validated_data_and_canonicalize_video_urls(): void
    {
        $page = $this->page();
        $admin = $this->admin();

        $heading = $this->block($page, 'heading', 0);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $heading]), [
            'data' => ['text' => 'Explore Bintan', 'level' => 'h3', 'alignment' => 'center'],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame('h3', $heading->fresh()->data['level']);

        $buttons = $this->block($page, 'button_group', 1);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $buttons]), [
            'data' => [
                'alignment' => 'right',
                'buttons' => [
                    ['text' => 'View Tours', 'url' => '/products', 'style' => 'primary'],
                    ['text' => 'Call Us', 'url' => 'tel:+62123456789', 'style' => 'link'],
                ],
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertCount(2, $buttons->fresh()->data['buttons']);

        $video = $this->block($page, 'video_embed', 2);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $video]), [
            'data' => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Bintan travel film',
                'aspect_ratio' => '16-9',
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->fresh()->data['url']);
    }

    public function test_a3_travel_blocks_store_bounded_structured_content(): void
    {
        $page = $this->page();
        $admin = $this->admin();

        $stats = $this->block($page, 'stats', 0);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $stats]), [
            'data' => [
                'heading' => 'Bintan by the numbers',
                'alignment' => 'center',
                'items' => [['value' => '10+', 'label' => 'Years', 'description' => 'Local expertise']],
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame('10+', $stats->fresh()->data['items'][0]['value']);

        $itinerary = $this->block($page, 'tour_itinerary', 1);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $itinerary]), [
            'data' => [
                'heading' => 'Two-day escape',
                'intro' => 'A relaxed island itinerary.',
                'items' => [['marker' => 'Day 1', 'title' => 'Lagoi arrival', 'description' => 'Transfer and check-in.']],
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertSame('Lagoi arrival', $itinerary->fresh()->data['items'][0]['title']);

        $pricing = $this->block($page, 'pricing_table', 2);
        $this->actingAs($admin)->put(route('admin.page-blocks.update', [$page, $pricing]), [
            'data' => [
                'heading' => 'Packages',
                'plans' => [[
                    'name' => 'Island Explorer',
                    'price' => '1,500,000',
                    'currency' => 'IDR',
                    'period' => 'per person',
                    'features' => ['Private transfer', 'Lunch included'],
                    'button_text' => 'Book now',
                    'button_url' => '/contact',
                    'featured' => true,
                ]],
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));
        $this->assertTrue($pricing->fresh()->data['plans'][0]['featured']);
        $this->assertCount(2, $pricing->fresh()->data['plans'][0]['features']);
    }

    public function test_text_block_update_preserves_allowed_markup_and_removes_disallowed_tags(): void
    {
        $page = $this->page();
        $block = $this->block($page, 'text');

        $this->actingAs($this->admin())->put(route('admin.page-blocks.update', [$page, $block]), [
            'label' => 'Story block',
            'data' => [
                'heading' => 'Our Story',
                'body_html' => '<p onclick="alert(1)">Hello <strong>World</strong><script>alert(1)</script> '
                    .'<a href="javascript:alert(2)" style="color:red">Unsafe</a> '
                    .'<a href="https://example.com" target="_blank" onclick="alert(3)">Safe</a></p>',
                'unexpected' => 'must not be stored',
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $block->refresh();

        $this->assertSame('Story block', $block->label);
        $this->assertSame('Our Story', $block->data['heading']);
        $this->assertSame(
            '<p>Hello <strong>World</strong> <a>Unsafe</a> '
                .'<a href="https://example.com" target="_blank" rel="noopener noreferrer">Safe</a></p>',
            $block->data['body_html']
        );
        $this->assertArrayNotHasKey('unexpected', $block->data);
    }

    public function test_b4_inline_content_sanitizer_neutralizes_xss_vectors(): void
    {
        $this->assertSame(
            '<p>Normal <strong>bold</strong></p>',
            InlineContentSanitizer::richtext('<p>Normal <strong>bold</strong></p>')
        );
        $this->assertSame('', InlineContentSanitizer::richtext('<script>alert(1)</script>'));
        $this->assertSame('<p>Text</p>', InlineContentSanitizer::richtext('<p onclick="alert(1)">Text</p>'));
        $this->assertSame('<a>Link</a>', InlineContentSanitizer::richtext('<a href="javascript:alert(1)">Link</a>'));
        $this->assertSame('<a>Encoded</a>', InlineContentSanitizer::richtext('<a href="java&#x73;cript:alert(1)">Encoded</a>'));
        $this->assertSame('', InlineContentSanitizer::richtext('<img src=x onerror=alert(1)>'));
        $this->assertSame('', InlineContentSanitizer::richtext('<style>body{display:none}</style>'));
        $this->assertSame('', InlineContentSanitizer::richtext('<iframe src="evil.com"></iframe>'));
        $this->assertSame('Hello world', InlineContentSanitizer::plaintext('Hello <b>world</b>'));
    }

    public function test_builder_save_tree_sanitizes_all_configured_inline_fields(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->postJson(route('admin.page-blocks.save-tree', $page), [
                'blocks' => [
                    [
                        'block_type' => 'hero',
                        'label' => 'Hero',
                        'data' => [
                            'title' => 'Welcome <script>alert(1)</script>',
                            'subtitle' => '<b>Island escapes</b>',
                            'cta_text' => '<img src=x onerror=alert(1)>Explore',
                        ],
                        'children' => [],
                    ],
                    [
                        'block_type' => 'text',
                        'label' => 'Story',
                        'data' => [
                            'heading' => '<em>Our story</em>',
                            'body_html' => '<p onclick="alert(1)">Safe <strong>copy</strong></p><script>alert(2)</script>',
                        ],
                        'children' => [],
                    ],
                    [
                        'block_type' => 'heading',
                        'label' => 'Heading',
                        'data' => ['text' => '<u>Section title</u>'],
                        'children' => [],
                    ],
                    [
                        'block_type' => 'cta',
                        'label' => 'CTA',
                        'data' => [
                            'title' => '<strong>Book today</strong>',
                            'description' => '<script>bad()</script>Plan your escape',
                            'button_text' => '<em>Contact us</em>',
                        ],
                        'children' => [],
                    ],
                ],
            ])->assertOk()->assertJsonPath('success', true);

        $hero = $page->blocks()->where('block_type', 'hero')->firstOrFail();
        $text = $page->blocks()->where('block_type', 'text')->firstOrFail();
        $heading = $page->blocks()->where('block_type', 'heading')->firstOrFail();
        $cta = $page->blocks()->where('block_type', 'cta')->firstOrFail();

        $this->assertSame('Welcome alert(1)', $hero->data['title']);
        $this->assertSame('Island escapes', $hero->data['subtitle']);
        $this->assertSame('Explore', $hero->data['cta_text']);
        $this->assertSame('Our story', $text->data['heading']);
        $this->assertSame('<p>Safe <strong>copy</strong></p>', $text->data['body_html']);
        $this->assertSame('Section title', $heading->data['text']);
        $this->assertSame('Book today', $cta->data['title']);
        $this->assertSame('bad()Plan your escape', $cta->data['description']);
        $this->assertSame('Contact us', $cta->data['button_text']);
    }

    public function test_builder_preview_marks_inline_fields_without_changing_public_routes(): void
    {
        $page = $this->page();
        $blocks = collect(['heading', 'text', 'hero', 'cta'])
            ->map(fn (string $type): array => [
                'block_type' => $type,
                'label' => ucfirst($type),
                'data' => [],
                'children' => [],
            ])->all();
        $blocks[1]['data'] = [
            'heading' => str_repeat('x', 1001), // forces transient validation fallback
            'body_html' => '<p>Preview copy</p><script>alert("preview-xss")</script>',
        ];

        $response = $this->actingAs($this->admin())
            ->post(route('admin.pages.preview-payload', $page), ['blocks' => $blocks]);

        $response->assertOk()
            ->assertSee('data-builder-block-order="1"', false)
            ->assertSee('data-inline-field="text"', false)
            ->assertSee('data-inline-field="body_html"', false)
            ->assertSee('data-inline-field="title"', false)
            ->assertSee('data-inline-field="button_text"', false)
            ->assertSee('contenteditable="true"', false)
            ->assertDontSee('preview-xss', false);
    }

    public function test_admin_can_reorder_toggle_and_delete_blocks_for_a_page(): void
    {
        $page = $this->page();
        $first = $this->block($page, 'text', 0);
        $second = $this->block($page, 'cta', 1);
        $otherPage = $this->page('Other Page', 'other-page');
        $foreign = $this->block($otherPage, 'divider', 8);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.page-blocks.reorder', $page), [
            'ids' => [$second->id, $first->id],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(8, $foreign->fresh()->sort_order);

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.page-blocks.reorder', $page), [
                'ids' => [$first->id, $second->id, $foreign->id],
                '_editor_context' => 'blocks',
            ])->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('ids');

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(8, $foreign->fresh()->sort_order);

        $this->actingAs($admin)->post(route('admin.page-blocks.toggle-visible', [$page, $first]))
            ->assertRedirect(route('admin.pages.edit', $page));
        $this->assertFalse($first->fresh()->is_visible);

        $this->actingAs($admin)->delete(route('admin.page-blocks.destroy', [$page, $second]))
            ->assertRedirect(route('admin.pages.edit', $page));
        $this->assertDatabaseMissing('page_blocks', ['id' => $second->id]);
    }

    public function test_reorder_requires_the_complete_unique_block_set_and_preserves_order_on_failure(): void
    {
        $page = $this->page();
        $first = $this->block($page, 'text', 0);
        $second = $this->block($page, 'cta', 1);
        $admin = $this->admin();

        foreach ([[$second->id], [$second->id, $second->id]] as $ids) {
            $this->actingAs($admin)
                ->from(route('admin.pages.edit', $page))
                ->post(route('admin.page-blocks.reorder', $page), [
                    'ids' => $ids,
                    '_editor_context' => 'blocks',
                ])->assertRedirect(route('admin.pages.edit', $page))
                ->assertSessionHasErrors('ids');

            $this->assertSame(0, $first->fresh()->sort_order);
            $this->assertSame(1, $second->fresh()->sort_order);
        }
    }

    public function test_block_editor_exposes_phase_two_controls_media_preview_and_accessible_save_delete_states(): void
    {
        $page = $this->page();
        $this->block($page, 'text', 0);
        $block = $this->block($page, 'image', 1);
        $block->update([
            'label' => 'About image',
            'data' => ['src' => 'media/about.webp', 'alt' => 'About Bintan'],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('src="http://localhost/storage/media/about.webp"', false)
            ->assertSee('aria-label="Move About image up"', false)
            ->assertSee('aria-controls="block-editor-panel-'.$block->id.'"', false)
            ->assertSee('Saving&hellip;', false)
            ->assertSee('This permanently removes its content and cannot be undone.');
    }

    public function test_block_validation_reopens_the_failed_editor_with_feedback(): void
    {
        $page = $this->page();
        $block = $this->block($page, 'map');

        $this->actingAs($this->admin())
            ->from(route('admin.pages.edit', $page))
            ->followingRedirects()
            ->put(route('admin.page-blocks.update', [$page, $block]), [
                '_editor_context' => 'blocks',
                '_block_id' => $block->id,
                'label' => 'Map block',
                'data' => ['embed_url' => 'javascript:alert(1)', 'zoom' => 30],
            ])->assertOk()
            ->assertSee('This block could not be saved.')
            ->assertSee('Your changes were not saved.')
            ->assertSee('x-data="{ open: true, submitting: false }"', false);
    }

    public function test_empty_block_editor_guides_the_admin_to_the_first_block_control(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.pages.edit', $this->page()))
            ->assertOk()
            ->assertSee('Build this page one block at a time.')
            ->assertSee('href="#block_type"', false)
            ->assertDontSee('x-sort', false);
    }

    public function test_nested_block_update_must_not_mutate_a_block_owned_by_another_page(): void
    {
        $routePage = $this->page('Route Page', 'route-page');
        $ownerPage = $this->page('Owner Page', 'owner-page');
        $foreignBlock = $this->block($ownerPage, 'text');

        $this->actingAs($this->admin())
            ->put(route('admin.page-blocks.update', [$routePage, $foreignBlock]), [
                'label' => 'Cross-page mutation',
                'data' => ['heading' => 'Must not be saved'],
            ])->assertNotFound();

        $foreignBlock->refresh();
        $this->assertNotSame('Cross-page mutation', $foreignBlock->label);
        $this->assertNotSame('Must not be saved', $foreignBlock->data['heading'] ?? null);
    }

    public function test_nested_block_delete_and_toggle_must_not_mutate_a_block_owned_by_another_page(): void
    {
        $routePage = $this->page('Route Page', 'route-page');
        $ownerPage = $this->page('Owner Page', 'owner-page');
        $foreignBlock = $this->block($ownerPage, 'text');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.page-blocks.toggle-visible', [$routePage, $foreignBlock]))
            ->assertNotFound();
        $this->assertTrue($foreignBlock->fresh()->is_visible);

        $this->actingAs($admin)
            ->delete(route('admin.page-blocks.destroy', [$routePage, $foreignBlock]))
            ->assertNotFound();
        $this->assertDatabaseHas('page_blocks', ['id' => $foreignBlock->id]);
    }

    public function test_block_schema_rejects_unsafe_urls_paths_ranges_relations_and_enums(): void
    {
        $page = $this->page();
        $admin = $this->admin();
        $cases = [
            ['hero', ['cta_url' => 'javascript:alert(1)'], 'cta_url'],
            ['heading', ['level' => 'h1'], 'level'],
            ['image', ['src' => '../../.env'], 'src'],
            ['gallery', ['columns' => 9], 'columns'],
            ['video_embed', ['url' => 'https://example.com/video'], 'url'],
            ['button_group', ['buttons' => [['text' => 'Unsafe', 'url' => 'javascript:alert(1)', 'style' => 'primary']]], 'buttons.0.url'],
            ['stats', ['alignment' => 'justify'], 'alignment'],
            ['tour_itinerary', ['items' => [['marker' => 'Day 1', 'title' => 'Trip', 'description' => str_repeat('x', 3001)]]], 'items.0.description'],
            ['pricing_table', ['plans' => [['button_url' => 'javascript:alert(1)']]], 'plans.0.button_url'],
            ['cta', ['style' => 'neon'], 'style'],
            ['products_grid', ['category_id' => 999999, 'limit' => 99], 'category_id'],
            ['faq', ['faq_ids' => [999999]], 'faq_ids.0'],
            ['testimonials', ['items' => [['rating' => 6]]], 'items.0.rating'],
            ['map', ['embed_url' => 'javascript:alert(1)', 'zoom' => 30], 'embed_url'],
            ['divider', ['style' => 'script'], 'style'],
        ];

        foreach ($cases as [$type, $data, $errorKey]) {
            $block = $this->block($page, $type);

            $this->actingAs($admin)
                ->from(route('admin.pages.edit', $page))
                ->put(route('admin.page-blocks.update', [$page, $block]), ['data' => $data])
                ->assertRedirect(route('admin.pages.edit', $page))
                ->assertSessionHasErrors($errorKey);
        }
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function page(string $title = 'Builder Page', string $slug = 'builder-page'): Page
    {
        return Page::create([
            'title' => $title,
            'slug' => $slug,
            'status' => 'draft',
        ]);
    }

    private function block(Page $page, string $type, int $sortOrder = 0): PageBlock
    {
        return PageBlock::create([
            'page_id' => $page->id,
            'block_type' => $type,
            'label' => ucfirst($type).' block',
            'data' => $type === 'text' ? ['heading' => 'Original heading'] : [],
            'sort_order' => $sortOrder,
            'is_visible' => true,
        ]);
    }
}
