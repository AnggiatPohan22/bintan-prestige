<?php

namespace Tests\Feature\Phase7;

use App\Models\PageSection;
use App\Models\User;
use App\Support\HomepageSectionData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B3 — Page Sections localized. The protected PageSection model translates its
 * copy columns (label/title/subtitle/description/button_text) via the B1 sidecar;
 * media slots, button_url, layout and extra_data stay shared. Base columns keep
 * the default-locale value, so the module is unchanged when Phase 7 is reverted.
 */
class B3PageSectionsLocalizedTest extends TestCase
{
    use RefreshDatabase;

    private function heroSection(array $attrs = []): PageSection
    {
        return PageSection::create(array_merge([
            'page_key'    => 'home',
            'section_key' => 'home.hero',
            'title'       => 'Welcome to Bintan',
            'subtitle'    => 'Island escapes',
            'is_active'   => true,
            'sort_order'  => 0,
        ], $attrs));
    }

    // ------------------------------------------------------------- model accessor

    public function test_accessor_returns_base_for_default_locale(): void
    {
        $s = $this->heroSection();
        $s->setTranslation('title', 'id', 'Selamat Datang di Bintan');

        app()->setLocale('en');
        $this->assertSame('Welcome to Bintan', $s->fresh()->title);
    }

    public function test_accessor_returns_translation_for_non_default_locale(): void
    {
        $s = $this->heroSection();
        $s->setTranslation('title', 'id', 'Selamat Datang di Bintan');

        app()->setLocale('id');
        $this->assertSame('Selamat Datang di Bintan', $s->fresh()->title);
    }

    public function test_accessor_falls_back_to_base_when_untranslated(): void
    {
        $s = $this->heroSection();

        app()->setLocale('id');
        $this->assertSame('Island escapes', $s->fresh()->subtitle); // no id translation
    }

    // ------------------------------------------------------------- render pipeline

    public function test_homepage_section_data_localizes_title(): void
    {
        $s = $this->heroSection();
        $s->setTranslation('title', 'id', 'Selamat Datang di Bintan');
        $sections = collect(['home.hero' => $s->fresh()]);

        app()->setLocale('id');
        $dataId = HomepageSectionData::fromSections($sections);
        $this->assertSame('Selamat Datang di Bintan', $dataId['home.hero']['title']);

        app()->setLocale('en');
        $dataEn = HomepageSectionData::fromSections($sections);
        $this->assertSame('Welcome to Bintan', $dataEn['home.hero']['title']);
    }

    public function test_default_locale_reads_no_translation_queries(): void
    {
        $s = $this->heroSection();
        $s->setTranslation('title', 'id', 'Selamat Datang di Bintan');

        app()->setLocale('en');
        $fresh = PageSection::query()->withTranslations()->find($s->id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        // Default locale short-circuits to the base column — touches no relation.
        $title = $fresh->title;
        $this->assertCount(0, DB::getQueryLog());
        $this->assertSame('Welcome to Bintan', $title);
        DB::disableQueryLog();
    }

    // ------------------------------------------------------------- admin write

    public function test_admin_update_persists_translations_without_touching_base(): void
    {
        $s = $this->heroSection();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $s), [
                'title'        => 'Welcome to Bintan',
                'subtitle'     => 'Island escapes',
                'is_active'    => 1,
                'sort_order'   => 0,
                'translations' => [
                    'id' => [
                        'title'    => 'Selamat Datang di Bintan',
                        'subtitle' => 'Pelarian pulau',
                    ],
                ],
            ])
            ->assertRedirect();

        $s->refresh();

        // Base columns unchanged (default locale).
        $this->assertSame('Welcome to Bintan', $s->getRawOriginal('title'));
        // Sidecar rows created for id.
        $this->assertSame('Selamat Datang di Bintan', $s->rawTranslation('title', 'id'));
        $this->assertSame('Pelarian pulau', $s->rawTranslation('subtitle', 'id'));
    }

    public function test_admin_update_clears_translation_when_blank(): void
    {
        $s = $this->heroSection();
        $s->setTranslation('title', 'id', 'Selamat Datang di Bintan');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $s), [
                'title'        => 'Welcome to Bintan',
                'is_active'    => 1,
                'sort_order'   => 0,
                'translations' => ['id' => ['title' => '']],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('translations', [
            'translatable_id'   => $s->id,
            'translatable_type' => $s->getMorphClass(),
            'locale'            => 'id',
            'field'             => 'title',
        ]);
    }
}
