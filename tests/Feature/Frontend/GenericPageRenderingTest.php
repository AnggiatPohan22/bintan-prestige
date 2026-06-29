<?php

namespace Tests\Feature\Frontend;

use App\Models\Faq;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTemplate;
use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SeoDefaultSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenericPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_renders_visible_blocks_in_sort_order(): void
    {
        $page = $this->page('published');
        $this->textBlock($page, 'Second section', 'Second body', 20);
        $this->textBlock($page, 'First section', 'First body', 10);
        $this->textBlock($page, 'Hidden section', 'Hidden body', 0, false);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSeeInOrder(['First section', 'Second section'])
            ->assertSee('First body')
            ->assertSee('Second body')
            ->assertDontSee('Hidden section')
            ->assertDontSee('Hidden body');
    }

    public function test_draft_page_is_not_publicly_accessible(): void
    {
        $page = $this->page('draft');

        $this->get(route('pages.show', $page->slug))
            ->assertNotFound();
    }

    public function test_admin_can_preview_a_draft_page_and_see_preview_ribbon(): void
    {
        $page = $this->page('draft');
        $this->textBlock($page, 'Draft heading', 'Draft body', 0);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pages.preview', $page))
            ->assertOk()
            ->assertSee('Preview')
            ->assertSee('<strong class="uppercase">draft</strong>', false)
            ->assertSee('Draft heading')
            ->assertSee('Draft body')
            ->assertSee('Back to editor')
            ->assertSee('href="'.route('admin.pages.edit', $page).'"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<link rel="canonical" href="'.route('pages.show', $page->slug).'">', false);
    }

    public function test_page_preview_requires_an_authenticated_admin(): void
    {
        $page = $this->page('draft');

        $this->get(route('admin.pages.preview', $page))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.pages.preview', $page))
            ->assertForbidden();
    }

    public function test_slug_change_activates_the_new_url_and_leaves_the_old_url_not_found(): void
    {
        $page = Page::create([
            'title' => 'Original Slug Page',
            'slug' => 'original-slug-page',
            'status' => 'published',
        ]);
        $oldUrl = route('pages.show', $page->slug);

        $this->get($oldUrl)->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.pages.update', $page), [
                'title' => 'Updated Slug Page',
                'slug' => 'updated-slug-page',
                'status' => 'published',
                'sort_order' => 0,
            ])->assertRedirect(route('admin.pages.edit', $page));

        $newUrl = route('pages.show', 'updated-slug-page');

        $this->get($oldUrl)->assertNotFound();
        $this->get($newUrl)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$newUrl.'">', false)
            ->assertSee('Updated Slug Page');
    }

    public function test_missing_template_view_falls_back_to_default_template(): void
    {
        $template = PageTemplate::create([
            'name' => 'Unavailable Template',
            'slug' => 'unavailable-template',
            'blade_file' => 'missing-template-view',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $page = $this->page('published', $template->id);
        $this->textBlock($page, 'Fallback template heading', 'Fallback body', 0);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Fallback template heading')
            ->assertSee('Fallback body');
    }

    public function test_page_meta_fields_are_exposed_by_the_frontend_layout(): void
    {
        $page = Page::create([
            'title' => 'Public CMS Page',
            'slug' => 'public-cms-page',
            'status' => 'published',
            'meta_title' => 'Custom CMS Meta Title',
            'meta_description' => 'Custom CMS meta description.',
            'og_image' => 'pages/public-og.webp',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Custom CMS Meta Title')
            ->assertSee('<meta name="description" content="Custom CMS meta description.">', false)
            ->assertSee('<link rel="canonical" href="'.route('pages.show', $page->slug).'">', false)
            ->assertSee('<meta property="og:title" content="Custom CMS Meta Title">', false)
            ->assertSee('storage/pages/public-og.webp', false);
    }

    public function test_page_uses_global_seo_canonical_and_open_graph_fallbacks(): void
    {
        foreach ([
            'seo.default.meta_description' => 'Global page fallback description.',
            'seo.default.og_description' => 'Global social fallback description.',
            'seo.default.canonical_base_url' => 'https://www.bintanprestige.test',
            'seo.default.site_name' => 'Bintan Prestige',
        ] as $key => $value) {
            SiteSetting::create([
                'key' => $key,
                'label' => $key,
                'value' => $value,
                'type' => 'text',
                'group' => SeoDefaultSettings::GROUP,
                'is_active' => true,
            ]);
        }

        SiteAsset::create([
            'key' => SeoDefaultSettings::OG_IMAGE_KEY,
            'label' => 'Default SEO image',
            'path' => 'site-assets/seo/default-page.webp',
            'alt' => 'Bintan coastline',
            'is_active' => true,
        ]);

        $page = Page::create([
            'title' => 'Fallback CMS Page',
            'slug' => 'fallback-cms-page',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('<title>', false)
            ->assertSee('Fallback CMS Page')
            ->assertSee('<meta name="description" content="Global page fallback description.">', false)
            ->assertSee('<link rel="canonical" href="https://www.bintanprestige.test/pages/fallback-cms-page">', false)
            ->assertSee('<meta property="og:title" content="Fallback CMS Page">', false)
            ->assertSee('<meta property="og:description" content="Global social fallback description.">', false)
            ->assertSee('site-assets/seo/default-page.webp', false);
    }

    public function test_allowlisted_templates_produce_page_breadcrumb_and_faq_schema(): void
    {
        $template = PageTemplate::create([
            'name' => 'Contained Article',
            'slug' => 'contained-article',
            'blade_file' => 'contained',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $page = $this->page('published', $template->id);
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'faq',
            'label' => 'Travel FAQ',
            'data' => [
                'source' => 'inline',
                'items' => [[
                    'question' => 'Is the page accessible?',
                    'answer' => 'Yes, the answer is server rendered.',
                ]],
            ],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $response = $this->get(route('pages.show', $page->slug))->assertOk();
        $graph = $this->structuredDataGraph($response->getContent());

        $this->assertNotNull($graph->firstWhere('@type', 'Article'));
        $this->assertNotNull($graph->firstWhere('@type', 'BreadcrumbList'));
        $this->assertNotNull($graph->firstWhere('@type', 'FAQPage'));
        $response->assertSee('aria-controls="faq-answer-', false)
            ->assertSee('role="region"', false);
    }

    public function test_gallery_cta_and_map_render_responsive_accessible_markup(): void
    {
        $page = $this->page('published');

        foreach ([
            ['gallery', ['images' => [['src' => 'media/gallery.webp', 'alt' => 'Bintan beach']], 'lightbox_enabled' => true]],
            ['cta', ['title' => 'Plan your trip', 'button_text' => 'Contact the team', 'button_url' => '/contact']],
            ['map', ['embed_url' => 'https://maps.example.test/embed', 'address' => 'Bintan Island']],
        ] as $sortOrder => [$type, $data]) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => $type,
                'label' => ucfirst($type),
                'data' => $data,
                'sort_order' => $sortOrder,
                'is_visible' => true,
            ]);
        }

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('role="button"', false)
            ->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('@keydown.tab="trapFocus($event)"', false)
            ->assertSee('focus:ring-2', false)
            ->assertSee('aspect-[4/3]', false)
            ->assertSee('loading="lazy"', false);
    }

    public function test_a3_heading_button_group_and_video_blocks_render_accessible_markup(): void
    {
        $page = $this->page('published');

        foreach ([
            ['heading', ['text' => 'Plan Your Bintan Escape', 'level' => 'h3', 'alignment' => 'center']],
            ['button_group', ['buttons' => [['text' => 'Explore Tours', 'url' => '/products', 'style' => 'primary']], 'alignment' => 'center']],
            ['video_embed', ['url' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'title' => 'Bintan travel film', 'caption' => 'Discover the island', 'aspect_ratio' => '16-9']],
        ] as $sortOrder => [$type, $data]) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => $type,
                'label' => ucfirst(str_replace('_', ' ', $type)),
                'data' => $data,
                'sort_order' => $sortOrder,
                'is_visible' => true,
            ]);
        }

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('<h3', false)
            ->assertSee('Plan Your Bintan Escape')
            ->assertSee('aria-label="Action links"', false)
            ->assertSee('href="/products"', false)
            ->assertSee('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', false)
            ->assertSee('title="Bintan travel film"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Discover the island');
    }

    public function test_a3_travel_blocks_render_structured_responsive_markup(): void
    {
        $page = $this->page('published');

        foreach ([
            ['stats', ['heading' => 'Bintan by the numbers', 'items' => [['value' => '10+', 'label' => 'Years', 'description' => 'Local expertise']]]],
            ['tour_itinerary', ['heading' => 'Two-day escape', 'items' => [['marker' => 'Day 1', 'title' => 'Lagoi arrival', 'description' => 'Transfer and check-in.']]]],
            ['pricing_table', ['heading' => 'Packages', 'plans' => [[
                'name' => 'Island Explorer', 'currency' => 'IDR', 'price' => '1,500,000', 'period' => 'per person',
                'features' => ['Private transfer'], 'button_text' => 'Book now', 'button_url' => '/contact', 'featured' => true,
            ]]]],
        ] as $sortOrder => [$type, $data]) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => $type,
                'label' => ucfirst(str_replace('_', ' ', $type)),
                'data' => $data,
                'sort_order' => $sortOrder,
                'is_visible' => true,
            ]);
        }

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('<dl', false)
            ->assertSee('Bintan by the numbers')
            ->assertSee('<ol', false)
            ->assertSee('Lagoi arrival')
            ->assertSee('<article', false)
            ->assertSee('Island Explorer')
            ->assertSee('href="/contact"', false)
            ->assertSee('Featured');
    }

    public function test_nested_group_and_columns_render_children_once_in_tree_order(): void
    {
        $page = $this->page('published');
        $columns = PageBlock::create([
            'page_id' => $page->id, 'block_type' => 'columns', 'label' => 'Two columns',
            'data' => ['columns' => 2, 'gap' => 'md', 'stack_mobile' => true], 'sort_order' => 0, 'is_visible' => true,
        ]);
        $group = PageBlock::create([
            'page_id' => $page->id, 'parent_block_id' => $columns->id, 'block_type' => 'group', 'label' => 'First column',
            'data' => ['width' => 'full', 'spacing' => 'none'], 'sort_order' => 0, 'is_visible' => true,
        ]);
        PageBlock::create([
            'page_id' => $page->id, 'parent_block_id' => $group->id, 'block_type' => 'heading', 'label' => 'Nested heading',
            'data' => ['text' => 'Nested Bintan Content', 'level' => 'h3'], 'sort_order' => 0, 'is_visible' => true,
        ]);

        $response = $this->get(route('pages.show', $page->slug))->assertOk();
        $response->assertSee('aria-label="Two columns"', false)
            ->assertSee('aria-label="First column"', false)
            ->assertSee('<h3', false)
            ->assertSee('Nested Bintan Content');
        $this->assertSame(1, substr_count($response->getContent(), 'Nested Bintan Content'));
    }

    public function test_preview_payload_accepts_a_sanitized_nested_block_tree(): void
    {
        $page = $this->page('draft');

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.pages.preview-payload', $page), [
                'blocks' => [[
                    'block_type' => 'group', 'label' => 'Payload group', 'data' => ['width' => 'contained'],
                    'children' => [[
                        'block_type' => 'heading', 'label' => 'Payload heading',
                        'data' => ['text' => 'Unsaved Nested Preview', 'level' => 'h2'],
                    ]],
                ]],
            ])
            ->assertOk()
            ->assertSee('Unsaved Nested Preview')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_preview_payload_rejects_invalid_container_children(): void
    {
        $page = $this->page('draft');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.pages.preview-payload', $page), [
                'blocks' => [[
                    'block_type' => 'columns',
                    'children' => [['block_type' => 'heading', 'data' => ['text' => 'Invalid direct child']]],
                ]],
            ])
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('blocks');

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.pages.preview-payload', $page), [
                'blocks' => [[
                    'block_type' => 'heading',
                    'data' => ['text' => 'Leaf'],
                    'children' => [['block_type' => 'text', 'data' => ['heading' => 'Invalid nested child']]],
                ]],
            ])
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('blocks');
    }

    public function test_empty_content_blocks_render_no_public_wrapper(): void
    {
        foreach (['group', 'columns', 'hero', 'heading', 'text', 'image', 'gallery', 'video_embed', 'button_group', 'stats', 'tour_itinerary', 'pricing_table', 'cta', 'products_grid', 'faq', 'testimonials', 'map'] as $type) {
            $block = new PageBlock([
                'block_type' => $type,
                'data' => [],
                'is_visible' => true,
            ]);
            $block->id = 900;
            $block->resolvedFaqItems = [];
            $block->resolvedProducts = collect();

            $rendered = (string) $this->view('frontend.blocks.'.str_replace('_', '-', $type), [
                'block' => $block,
            ]);

            $this->assertSame('', trim($rendered), "{$type} emitted markup without content.");
        }
    }

    public function test_faq_id_blocks_render_active_faqs_in_configured_order_with_one_query(): void
    {
        $first = Faq::create([
            'question' => 'First configured question?',
            'answer' => 'First configured answer.',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $second = Faq::create([
            'question' => 'Second configured question?',
            'answer' => 'Second configured answer.',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $inactive = Faq::create([
            'question' => 'Inactive question?',
            'answer' => 'Inactive answer.',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        $page = $this->page('published');

        foreach ([[$second->id], [$first->id, $inactive->id]] as $index => $ids) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => 'faq',
                'label' => 'FAQ block '.$index,
                'data' => ['source' => 'ids', 'faq_ids' => $ids],
                'sort_order' => $index,
                'is_visible' => true,
            ]);
        }

        $faqQueries = 0;
        DB::listen(function ($query) use (&$faqQueries): void {
            if (str_contains(strtolower($query->sql), 'from "faqs"')) {
                $faqQueries++;
            }
        });

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSeeInOrder(['Second configured question?', 'First configured question?'])
            ->assertDontSee('Inactive question?');

        $this->assertSame(1, $faqQueries);
    }

    public function test_frontend_block_blades_do_not_query_models_directly(): void
    {
        foreach (glob(resource_path('views/frontend/blocks/*.blade.php')) as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString('::query(', $contents, $file);
            $this->assertStringNotContainsString('->where(', $contents, $file);
            $this->assertStringNotContainsString('use App\\Models\\', $contents, $file);
            $this->assertStringNotContainsString('::where', $contents, $file);
            $this->assertStringNotContainsString('::find(', $contents, $file);
            $this->assertStringNotContainsString('\\App\\Models\\', $contents, $file);
        }
    }

    private function page(string $status, ?int $templateId = null): Page
    {
        return Page::create([
            'title' => ucfirst($status).' Generic Page',
            'slug' => $status.'-generic-page-'.Page::query()->count(),
            'status' => $status,
            'template_id' => $templateId,
        ]);
    }

    private function textBlock(
        Page $page,
        string $heading,
        string $body,
        int $sortOrder,
        bool $visible = true,
    ): PageBlock {
        return PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'text',
            'label' => $heading,
            'data' => [
                'heading' => $heading,
                'body_html' => '<p>'.$body.'</p>',
            ],
            'sort_order' => $sortOrder,
            'is_visible' => $visible,
        ]);
    }

    private function structuredDataGraph(string $html)
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $decoded = json_decode($matches[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        return collect($decoded['@graph'] ?? []);
    }
}
