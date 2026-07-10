<?php

namespace Tests\Feature\Phase7;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B4 — Pages become row-per-locale, linked by translation_group_id. The default
 * locale keeps its unprefixed URL; other locales live at /{locale}/pages/{slug}
 * and 404 when untranslated. Builder/blocks stay per-row.
 */
class B4PagesRowPerLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $attrs = []): Page
    {
        return Page::create(array_merge([
            'title'  => 'About Us',
            'slug'   => 'about',
            'status' => 'published',
        ], $attrs));
    }

    // ------------------------------------------------------------- model defaults

    public function test_new_page_gets_default_locale_and_group(): void
    {
        $page = $this->page();

        $this->assertSame('en', $page->locale);
        $this->assertSame(26, strlen((string) $page->translation_group_id));
    }

    // ------------------------------------------------------------- slug uniqueness

    public function test_same_slug_allowed_across_locales(): void
    {
        $en = $this->page();
        $id = Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->assertNotSame($en->id, $id->id);
        $this->assertDatabaseHas('pages', ['slug' => 'about', 'locale' => 'id']);
    }

    public function test_duplicate_slug_in_same_locale_is_rejected_by_db(): void
    {
        $this->page();
        $this->expectException(\Illuminate\Database\QueryException::class);
        Page::create(['title' => 'Dup', 'slug' => 'about', 'status' => 'draft']); // locale en again
    }

    // ------------------------------------------------------------- routing

    public function test_default_locale_page_served_unprefixed(): void
    {
        $this->page();
        $this->get('/pages/about')->assertOk()->assertSee('About Us');
    }

    public function test_localized_page_served_under_prefix(): void
    {
        $en = $this->page();
        Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/id/pages/about')->assertOk()->assertSee('Tentang Kami');
        $this->get('/pages/about')->assertOk()->assertSee('About Us'); // en unchanged
    }

    public function test_untranslated_locale_is_404(): void
    {
        $this->page(); // only en exists
        $this->get('/id/pages/about')->assertNotFound();
    }

    public function test_draft_localized_page_is_404(): void
    {
        $en = $this->page();
        Page::create([
            'title' => 'Draft ID', 'slug' => 'about', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/id/pages/about')->assertNotFound();
    }

    // ------------------------------------------------------------- switcher alternates

    public function test_switcher_offers_published_translation(): void
    {
        $en = $this->page();
        Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/pages/about')->assertOk()->assertSee(url('/id/pages/about'), false);
    }

    public function test_switcher_hides_unpublished_translation(): void
    {
        $en = $this->page();
        Page::create([
            'title' => 'Draft', 'slug' => 'about', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/pages/about')->assertOk()->assertDontSee(url('/id/pages/about'), false);
    }

    // ------------------------------------------------------------- translateTo service

    public function test_translate_to_copies_blocks_into_same_group_as_draft(): void
    {
        $en = $this->page();
        PageBlock::create([
            'page_id' => $en->id, 'block_type' => 'heading',
            'data' => ['text' => 'Hello'], 'sort_order' => 0, 'is_visible' => true,
        ]);

        $id = app(PageService::class)->translateTo($en, 'id');

        $this->assertSame($en->translation_group_id, $id->translation_group_id);
        $this->assertSame('id', $id->locale);
        $this->assertSame('draft', $id->status);
        $this->assertSame(1, $id->blocks()->count());
        // Idempotent: calling again returns the same row, not a second copy.
        $this->assertSame($id->id, app(PageService::class)->translateTo($en->fresh(), 'id')->id);
    }

    // ------------------------------------------------------------- admin action

    public function test_admin_translate_action_creates_draft_and_redirects(): void
    {
        $en = $this->page();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.pages.translate', $en), ['locale' => 'id'])
            ->assertRedirect();

        $id = $en->translationIn('id');
        $this->assertNotNull($id);
        $this->assertSame('draft', $id->status);
    }

    public function test_admin_translate_rejects_default_locale(): void
    {
        $en = $this->page();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.pages.translate', $en), ['locale' => 'en'])
            ->assertSessionHasErrors('locale');
    }

    // ------------------------------------------------------------- legacy guard

    public function test_existing_page_backfill_semantics_hold(): void
    {
        $page = $this->page();
        // A page's own translation URL helper respects its locale.
        $this->assertSame(url('/pages/about'), $page->publicUrl());

        $id = app(PageService::class)->translateTo($page, 'id');
        $this->assertSame(url('/id/pages/about'), $id->publicUrl());

        // No cross-locale N+1 surprise: querying the group is one query.
        DB::flushQueryLog();
        DB::enableQueryLog();
        $page->translationSiblings()->get();
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
    }
}
