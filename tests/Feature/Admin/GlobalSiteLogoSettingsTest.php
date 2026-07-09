<?php

namespace Tests\Feature\Admin;

use App\Models\SiteAsset;
use App\Models\User;
use App\Support\HomepageSectionMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalSiteLogoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_a_logo_variant_from_the_media_library(): void
    {
        Storage::fake('public');
        // Simulate an image already in the Media Library (what the picker returns).
        Storage::disk('public')->put('media/logo/2026/07/main.webp', 'x');

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.site-logo.update'), [
                'logos' => ['main' => 'media/logo/2026/07/main.webp'],
                'logo_alts' => ['main' => 'Bintan Prestige logo'],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $this->assertDatabaseHas('site_assets', [
            'key' => HomepageSectionMedia::SITE_LOGO_KEY,
            'path' => 'media/logo/2026/07/main.webp',
            'alt' => 'Bintan Prestige logo',
            'is_active' => true,
        ]);

        // Deleting the variant detaches it but leaves the library file intact.
        $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.site-logo.destroy', 'main'))
            ->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $this->assertDatabaseHas('site_assets', [
            'key' => HomepageSectionMedia::SITE_LOGO_KEY,
            'path' => null,
            'is_active' => false,
        ]);
        Storage::disk('public')->assertExists('media/logo/2026/07/main.webp');
    }

    public function test_replacing_a_legacy_uploaded_logo_removes_the_old_local_file(): void
    {
        Storage::fake('public');
        // Legacy state: a previously uploaded logo living under site-assets/.
        Storage::disk('public')->put('site-assets/site-logo/old.webp', 'x');
        SiteAsset::create([
            'key' => HomepageSectionMedia::SITE_LOGO_KEY,
            'label' => 'Main website logo',
            'path' => 'site-assets/site-logo/old.webp',
            'alt' => 'Old logo',
            'is_active' => true,
        ]);
        Storage::disk('public')->put('media/logo/2026/07/new.webp', 'x');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.site-logo.update'), [
                'logos' => ['main' => 'media/logo/2026/07/new.webp'],
                'logo_alts' => ['main' => 'New logo'],
            ])->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $this->assertDatabaseHas('site_assets', [
            'key' => HomepageSectionMedia::SITE_LOGO_KEY,
            'path' => 'media/logo/2026/07/new.webp',
        ]);
        // Old module-owned upload is cleaned up; the new library file remains.
        Storage::disk('public')->assertMissing('site-assets/site-logo/old.webp');
        Storage::disk('public')->assertExists('media/logo/2026/07/new.webp');
    }

    public function test_site_logo_tab_renders_the_media_library_picker(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']))
            ->assertOk()
            ->assertSee('Main website logo')
            ->assertSee('Media Library')
            ->assertDontSee('Upload favicon');
    }
}
