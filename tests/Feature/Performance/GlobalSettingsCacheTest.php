<?php

namespace Tests\Feature\Performance;

use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GlobalSettingsService;
use App\Support\BrandColorSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalSettingsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_global_settings_service_builds_public_settings_and_assets(): void
    {
        SiteSetting::create([
            'key' => 'brand.palette.primary',
            'label' => 'Primary',
            'value' => '#123456',
            'type' => 'color',
            'group' => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        SiteAsset::create([
            'key' => 'site.logo',
            'label' => 'Main website logo',
            'path' => 'site-assets/site-logo/logo.webp',
            'alt' => 'Bintan Prestige logo',
            'is_active' => true,
        ]);

        $data = app(GlobalSettingsService::class)->viewData();

        $this->assertSame('#123456', $data['brandColors']['palette_primary']);
        $this->assertSame('Bintan Prestige', $data['businessIdentity']['brand_name']);
        $this->assertTrue($data['siteAssets']->has('site.logo'));
    }

    public function test_cache_hit_returns_same_output_without_settings_or_assets_queries(): void
    {
        SiteSetting::create([
            'key' => 'brand.palette.primary',
            'label' => 'Primary',
            'value' => '#654321',
            'type' => 'color',
            'group' => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        SiteAsset::create([
            'key' => 'site.logo.dark',
            'label' => 'Dark logo',
            'path' => 'site-assets/site-logo-dark/logo.webp',
            'alt' => 'Dark logo',
            'is_active' => true,
        ]);

        $first = app(GlobalSettingsService::class)->viewData();

        app()->forgetInstance(GlobalSettingsService::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $second = app(GlobalSettingsService::class)->viewData();

        $this->assertSame($first['brandColors']['palette_primary'], $second['brandColors']['palette_primary']);
        $this->assertTrue($second['siteAssets']->has('site.logo.dark'));
        $this->assertIsArray(Cache::get(GlobalSettingsService::SETTINGS_CACHE_KEY));
        $this->assertIsArray(Cache::get(GlobalSettingsService::ASSETS_CACHE_KEY));
        $this->assertSame(0, $this->countSettingsAndAssetQueries());
    }

    public function test_stale_invalid_settings_cache_payload_does_not_break_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        SiteSetting::create([
            'key' => 'brand.palette.primary',
            'label' => 'Primary',
            'value' => '#111111',
            'type' => 'color',
            'group' => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        Cache::put(GlobalSettingsService::SETTINGS_CACHE_KEY, (object) ['legacy' => true], 300);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $this->assertIsArray(Cache::get(GlobalSettingsService::SETTINGS_CACHE_KEY));
    }

    public function test_stale_invalid_assets_cache_payload_is_rebuilt(): void
    {
        SiteAsset::create([
            'key' => 'site.logo',
            'label' => 'Main website logo',
            'path' => 'site-assets/site-logo/logo.webp',
            'alt' => 'Bintan Prestige logo',
            'is_active' => true,
        ]);

        Cache::put(GlobalSettingsService::ASSETS_CACHE_KEY, (object) ['legacy' => true], 300);

        $assets = app(GlobalSettingsService::class)->siteAssets();

        $this->assertTrue($assets->has('site.logo'));
        $this->assertSame('Bintan Prestige logo', $assets['site.logo']->alt);
        $this->assertIsArray(Cache::get(GlobalSettingsService::ASSETS_CACHE_KEY));
    }

    public function test_site_setting_save_clears_settings_cache_without_clearing_assets_cache(): void
    {
        Cache::put(GlobalSettingsService::SETTINGS_CACHE_KEY, ['cached' => true], 300);
        Cache::put(GlobalSettingsService::ASSETS_CACHE_KEY, ['cached' => true], 300);

        SiteSetting::create([
            'key' => 'brand.palette.secondary',
            'label' => 'Secondary',
            'value' => '#d4af37',
            'type' => 'color',
            'group' => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        $this->assertFalse(Cache::has(GlobalSettingsService::SETTINGS_CACHE_KEY));
        $this->assertTrue(Cache::has(GlobalSettingsService::ASSETS_CACHE_KEY));
    }

    public function test_site_asset_save_clears_assets_cache_without_clearing_settings_cache(): void
    {
        Cache::put(GlobalSettingsService::SETTINGS_CACHE_KEY, ['cached' => true], 300);
        Cache::put(GlobalSettingsService::ASSETS_CACHE_KEY, ['cached' => true], 300);

        SiteAsset::create([
            'key' => 'site.favicon',
            'label' => 'Browser favicon',
            'path' => 'site-assets/favicon/favicon.ico',
            'alt' => 'Favicon',
            'is_active' => true,
        ]);

        $this->assertTrue(Cache::has(GlobalSettingsService::SETTINGS_CACHE_KEY));
        $this->assertFalse(Cache::has(GlobalSettingsService::ASSETS_CACHE_KEY));
    }

    public function test_admin_settings_update_clears_settings_cache(): void
    {
        Cache::put(GlobalSettingsService::SETTINGS_CACHE_KEY, ['cached' => true], 300);
        Cache::put(GlobalSettingsService::ASSETS_CACHE_KEY, ['cached' => true], 300);

        $admin = User::factory()->admin()->create();
        $colors = collect(BrandColorSettings::fields())
            ->mapWithKeys(fn (array $field) => [$field['slug'] => $field['default']])
            ->all();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.brand-colors.update'), [
                'brand_colors' => $colors,
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'brand-colors']));
        $this->assertFalse(Cache::has(GlobalSettingsService::SETTINGS_CACHE_KEY));
        $this->assertTrue(Cache::has(GlobalSettingsService::ASSETS_CACHE_KEY));
    }

    public function test_public_frontend_renders_with_cached_global_settings_service(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Bintan Prestige');
    }

    private function countSettingsAndAssetQueries(): int
    {
        return collect(DB::getQueryLog())
            ->filter(function (array $query) {
                $sql = $query['query'];

                return str_contains($sql, 'site_settings')
                    || str_contains($sql, 'site_assets');
            })
            ->count();
    }
}
