<?php

namespace Tests\Feature\Admin;

use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalSocialShareImageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_default_social_share_image_from_global_assets_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.social-share-image.update'), [
                'social_share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
                'social_share_image_alt' => 'Bintan Prestige default social share image',
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.social_share.default_image',
            'label' => 'Default social share image',
            'alt' => 'Bintan Prestige default social share image',
            'is_active' => true,
        ]);

        $shareImage = SiteAsset::where('key', 'site.social_share.default_image')->firstOrFail();
        Storage::disk('public')->assertExists($shareImage->path);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.social-share-image.destroy'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.social_share.default_image',
            'path' => null,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertMissing($shareImage->path);
    }

    public function test_social_share_image_tab_only_shows_share_image_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $response->assertOk();
        $response->assertSee('Default Social Share Image');
        $response->assertSee('Recommended size: 1200 x 630 px');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_frontend_layout_uses_default_social_share_image_meta(): void
    {
        SiteAsset::create([
            'key' => 'site.social_share.default_image',
            'label' => 'Default social share image',
            'path' => 'site-assets/site-social-share-default-image/share.jpg',
            'alt' => 'Bintan Prestige default social share image',
            'is_active' => true,
        ]);

        $response = $this->view('layouts.frontend', [
            'title' => 'Bintan Prestige',
            'socialShareDescription' => 'Premium Bintan travel experiences.',
            'siteAssets' => SiteAsset::where('is_active', true)->get()->keyBy('key'),
        ]);

        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:image"', false);
        $response->assertSee('site-assets/site-social-share-default-image/share.jpg', false);
        $response->assertSee('summary_large_image', false);
    }
}
