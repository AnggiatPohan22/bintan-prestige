<?php

namespace Tests\Feature\Phase7;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GlobalSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * B2 — Global chrome localized. SiteSetting values resolve per locale through the
 * B1 sidecar, and a dedicated admin panel edits the translations. Nothing about
 * the per-group forms or the default-locale output changes.
 */
class B2GlobalChromeLocalizedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // settings payload is cached per locale
    }

    private function ctaSetting(): SiteSetting
    {
        return SiteSetting::create([
            'key'       => 'navigation.header.cta_label',
            'label'     => 'Header CTA label',
            'value'     => 'Plan Trip',
            'type'      => 'text',
            'group'     => 'navigation_settings',
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------- read side

    public function test_settings_payload_resolves_per_locale(): void
    {
        $s = $this->ctaSetting();
        $s->setTranslation('value', 'id', 'Rencanakan Trip');

        app()->setLocale('id');
        $id = (new GlobalSettingsService())->viewData();
        $this->assertSame('Rencanakan Trip', $id['navigationSettings']['cta_label']);

        app()->setLocale('en');
        $en = (new GlobalSettingsService())->viewData();
        $this->assertSame('Plan Trip', $en['navigationSettings']['cta_label']);
    }

    public function test_missing_translation_falls_back_to_base_in_payload(): void
    {
        $this->ctaSetting(); // no 'id' translation

        app()->setLocale('id');
        $id = (new GlobalSettingsService())->viewData();

        $this->assertSame('Plan Trip', $id['navigationSettings']['cta_label']);
    }

    // ------------------------------------------------------------- frontend (one request per method — singleton-safe)

    public function test_frontend_header_shows_localized_cta(): void
    {
        $s = $this->ctaSetting();
        $s->setTranslation('value', 'id', 'Rencanakan Trip');
        Cache::flush();

        $this->get('/id')->assertOk()->assertSee('Rencanakan Trip', false);
    }

    public function test_frontend_header_shows_base_cta_on_default_locale(): void
    {
        $s = $this->ctaSetting();
        $s->setTranslation('value', 'id', 'Rencanakan Trip');
        Cache::flush();

        $this->get('/')->assertOk()->assertSee('Plan Trip', false);
    }

    // ------------------------------------------------------------- admin panel

    public function test_admin_can_view_translations_panel(): void
    {
        $this->ctaSetting();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.translations', ['locale' => 'id']))
            ->assertOk()
            ->assertSee('Global Text Translations')
            ->assertSee('Header CTA label')
            ->assertSee('Plan Trip'); // base value shown for reference
    }

    public function test_admin_can_save_translation(): void
    {
        $s = $this->ctaSetting();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.translations.update'), [
                'locale'       => 'id',
                'translations' => ['navigation.header.cta_label' => 'Rencanakan Trip'],
            ])
            ->assertRedirect(route('admin.settings.global-assets.translations', ['locale' => 'id']));

        $this->assertDatabaseHas('translations', [
            'translatable_type' => $s->getMorphClass(),
            'translatable_id'   => $s->id,
            'locale'            => 'id',
            'field'             => 'value',
            'value'             => 'Rencanakan Trip',
        ]);
    }

    public function test_saving_empty_value_clears_translation(): void
    {
        $s = $this->ctaSetting();
        $s->setTranslation('value', 'id', 'Rencanakan Trip');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.translations.update'), [
                'locale'       => 'id',
                'translations' => ['navigation.header.cta_label' => ''],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('translations', [
            'translatable_id' => $s->id,
            'locale'          => 'id',
            'field'           => 'value',
        ]);
    }

    public function test_non_allowlisted_key_is_ignored(): void
    {
        $this->ctaSetting();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.translations.update'), [
                'locale'       => 'id',
                'translations' => ['seo.default.canonical_base_url' => 'https://evil.example'],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('translations', [
            'field' => 'value',
            'value' => 'https://evil.example',
        ]);
    }

    public function test_update_rejects_default_locale(): void
    {
        $this->ctaSetting();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.translations.update'), [
                'locale'       => 'en', // default locale is not a translation target
                'translations' => ['navigation.header.cta_label' => 'X'],
            ])
            ->assertSessionHasErrors('locale');
    }
}
