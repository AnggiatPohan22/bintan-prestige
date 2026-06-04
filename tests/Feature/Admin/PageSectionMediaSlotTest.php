<?php

namespace Tests\Feature\Admin;

use App\Models\PageSection;
use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageSectionMediaSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_homepage_media_slots_on_section_edit_screen(): void
    {
        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_tour',
            'label' => 'Most Popular Tour',
            'title' => 'Popular Tour',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.edit', $section));

        $response->assertOk();
        $response->assertSee('Logo website global');
        $response->assertSee('Frame kiri atas');
        $response->assertSee('Frame kanan bawah');
        $response->assertDontSee('Section gallery images');
    }

    public function test_admin_can_upload_section_frame_slot_without_updating_global_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_tour',
            'label' => 'Most Popular Tour',
            'title' => 'Popular Tour',
            'description' => 'Section description',
            'button_text' => 'Take a tour',
            'button_url' => '/products',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $section), [
                'label' => 'Most Popular Tour',
                'title' => 'Popular Tour',
                'description' => 'Section description',
                'button_text' => 'Take a tour',
                'button_url' => '/products',
                'is_active' => '1',
                'sort_order' => '10',
                'slot_uploads' => [
                    'frame' => [
                        'left_wide' => UploadedFile::fake()->image('left-wide.jpg', 800, 550),
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.page-sections.edit', $section));

        $this->assertDatabaseEmpty('site_assets');

        $this->assertDatabaseHas('page_section_media', [
            'page_section_id' => $section->id,
            'role' => 'frame',
            'slot_key' => 'left_wide',
            'label' => 'Frame kiri atas',
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists(
            $section->fresh()->mediaSlot('frame', 'left_wide')->path
        );
    }

    public function test_admin_can_manage_global_site_logo_from_global_assets_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.site-logo.update'), [
                'logos' => [
                    'main' => UploadedFile::fake()->image('logo.jpg', 400, 220),
                    'light' => UploadedFile::fake()->image('logo-light.png', 400, 220),
                ],
                'logo_alts' => [
                    'main' => 'Bintan Prestige logo',
                    'light' => 'Bintan Prestige light logo',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit'));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo',
            'label' => 'Main website logo',
            'alt' => 'Bintan Prestige logo',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo.light',
            'label' => 'Light logo',
            'alt' => 'Bintan Prestige light logo',
            'is_active' => true,
        ]);

        $siteLogo = SiteAsset::where('key', 'site.logo')->firstOrFail();
        $lightLogo = SiteAsset::where('key', 'site.logo.light')->firstOrFail();
        Storage::disk('public')->assertExists($siteLogo->path);
        Storage::disk('public')->assertExists($lightLogo->path);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.site-logo.destroy', 'light'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit'));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo.light',
            'path' => null,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertMissing($lightLogo->path);
        Storage::disk('public')->assertExists($siteLogo->path);
    }
}
