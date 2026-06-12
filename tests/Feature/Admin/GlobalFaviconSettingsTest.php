<?php

namespace Tests\Feature\Admin;

use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalFaviconSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_assets_page_uses_query_tabs_for_settings_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $siteLogoResponse = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $siteLogoResponse->assertOk();
        $siteLogoResponse->assertSee('Site Logo Variants');
        $siteLogoResponse->assertSee('Browser Favicon');
        $siteLogoResponse->assertDontSee('Upload favicon');

        $faviconResponse = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'favicon']));

        $faviconResponse->assertOk();
        $faviconResponse->assertSee('Browser Favicon');
        $faviconResponse->assertSee('Upload favicon');
        $faviconResponse->assertDontSee('Site Logo Variants');
    }

    public function test_admin_can_manage_browser_favicon_from_global_assets_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.favicon.update'), [
                'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
                'favicon_alt' => 'Bintan Prestige favicon',
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'favicon']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.favicon',
            'label' => 'Browser favicon',
            'alt' => 'Bintan Prestige favicon',
            'is_active' => true,
        ]);

        $favicon = SiteAsset::where('key', 'site.favicon')->firstOrFail();
        Storage::disk('public')->assertExists($favicon->path);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.favicon.destroy'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'favicon']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.favicon',
            'path' => null,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertMissing($favicon->path);
    }

    public function test_frontend_layout_uses_global_favicon_asset(): void
    {
        SiteAsset::create([
            'key' => 'site.favicon',
            'label' => 'Browser favicon',
            'path' => 'site-assets/site-favicon/favicon.png',
            'alt' => 'Bintan Prestige favicon',
            'is_active' => true,
        ]);

        $response = $this->view('layouts.frontend', [
            'siteAssets' => SiteAsset::where('is_active', true)->get()->keyBy('key'),
        ]);

        $response->assertSee('rel="icon"', false);
        $response->assertSee('site-assets/site-favicon/favicon.png', false);
    }
}
