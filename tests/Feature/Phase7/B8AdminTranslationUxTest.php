<?php

namespace Tests\Feature\Phase7;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B8 — Admin translation UX. Per-locale translation-status badges and a locale
 * filter on the Pages and Content Entries list views. Extend-only (no schema).
 */
class B8AdminTranslationUxTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ------------------------------------------------------------- Pages list

    public function test_pages_index_shows_translation_status_column_and_badges(): void
    {
        $en = Page::create(['title' => 'About Us', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Translations')                   // column header
            ->assertSee('bg-emerald-500', false)          // EN sibling published (green dot)
            ->assertSee('bg-amber-500', false);           // ID sibling draft (amber dot)
    }

    public function test_pages_index_shows_missing_state_when_no_sibling(): void
    {
        Page::create(['title' => 'Solo', 'slug' => 'solo', 'status' => 'published']);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('bg-slate-300', false);           // ID missing (grey dot)
    }

    public function test_pages_locale_filter_narrows_the_list(): void
    {
        $en = Page::create(['title' => 'English Page', 'slug' => 'english-page', 'status' => 'published']);
        Page::create([
            'title' => 'Halaman ID', 'slug' => 'halaman-id', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.index', ['locale' => 'id']))
            ->assertOk()
            ->assertSee('Halaman ID')
            ->assertDontSee('English Page');
    }

    public function test_pages_index_badges_do_not_n_plus_one(): void
    {
        // 5 pages, each with an ID sibling.
        for ($i = 1; $i <= 5; $i++) {
            $en = Page::create(['title' => "EN $i", 'slug' => "en-$i", 'status' => 'published']);
            Page::create([
                'title' => "ID $i", 'slug' => "en-$i", 'status' => 'published',
                'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
            ]);
        }

        $admin = $this->admin();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        // Count queries that touch translation_siblings (should be exactly ONE
        // eager-load, not per row).
        $siblingQueries = collect($log)
            ->filter(fn (array $q): bool =>
                str_contains($q['query'], 'from "pages"')
                && str_contains($q['query'], 'translation_group_id')
                && str_contains($q['query'], 'in ('))
            ->count();

        $this->assertSame(1, $siblingQueries);
    }

    // ------------------------------------------------------------- Content Entries list

    public function test_entries_index_shows_translation_status_column(): void
    {
        $type = ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug'],
        ]);
        $en = ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Hello', 'slug' => 'hello',
            'status' => 'published',
        ]);
        ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Halo', 'slug' => 'hello',
            'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.index', $type))
            ->assertOk()
            ->assertSee('Translations')
            ->assertSee('bg-emerald-500', false)
            ->assertSee('bg-amber-500', false);
    }

    public function test_entries_locale_filter_narrows_the_list(): void
    {
        $type = ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug'],
        ]);
        $en = ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'English Post', 'slug' => 'p',
            'status' => 'published',
        ]);
        ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Postingan ID', 'slug' => 'p',
            'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.index', ['content_type' => $type, 'locale' => 'id']))
            ->assertOk()
            ->assertSee('Postingan ID')
            ->assertDontSee('English Post');
    }
}
