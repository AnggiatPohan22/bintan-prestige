<?php

namespace Tests\Feature\Admin;

use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalSocialShareImageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_default_social_share_image_from_the_media_library(): void
    {
        Storage::fake('public');
        // Simulate an image already in the Media Library (what the picker returns).
        Storage::disk('public')->put('media/content/2026/07/share.webp', 'x');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.social-share-image.update'), [
                'social_share_image' => 'media/content/2026/07/share.webp',
                'social_share_image_alt' => 'Bintan Prestige default social share image',
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.social_share.default_image',
            'label' => 'Default social share image',
            'path' => 'media/content/2026/07/share.webp',
            'alt' => 'Bintan Prestige default social share image',
            'is_active' => true,
        ]);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.social-share-image.destroy'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.social_share.default_image',
            'path' => null,
            'is_active' => false,
        ]);

        // Detaching the asset must NOT delete the Media Library file (library-owned).
        Storage::disk('public')->assertExists('media/content/2026/07/share.webp');
    }

    public function test_social_share_image_tab_only_shows_share_image_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'social-share-image']));

        $response->assertOk();
        $response->assertSee('Default Social Share Image');
        $response->assertSee('Recommended 1200 x 630 px');
        $response->assertSee('Media Library');
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
