<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\SocialMediaLinkSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSocialMediaLinksSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_social_media_links_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.social-media-links.update'), [
                'social_media_links' => [
                    'instagram' => 'https://instagram.com/bintanprestige',
                    'facebook' => 'https://facebook.com/bintanprestige',
                    'tiktok' => '',
                    'youtube' => 'https://youtube.com/@bintanprestige',
                    'linkedin' => '',
                    'tripadvisor' => '',
                    'google_review' => '',
                ],
                'custom_social_links' => [
                    [
                        'label' => 'Pinterest',
                        'abbr' => 'PI',
                        'url' => 'https://pinterest.com/bintanprestige',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'social-media-links']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'social.instagram.url',
            'value' => 'https://instagram.com/bintanprestige',
            'type' => 'url',
            'group' => 'social_media_links',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'social.youtube.url',
            'value' => 'https://youtube.com/@bintanprestige',
            'type' => 'url',
            'group' => 'social_media_links',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'social.custom_links',
            'type' => 'json',
            'group' => 'social_media_links',
            'is_active' => true,
        ]);
    }

    public function test_social_media_links_tab_only_shows_social_links_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'social-media-links']));

        $response->assertOk();
        $response->assertSee('Social Media Links');
        $response->assertSee('Instagram URL');
        $response->assertSee('Add Link');
        $response->assertSee('Custom Social Links');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_global_assets_tabs_use_horizontal_overflow(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit'));

        $response->assertOk();
        $response->assertSee('overflow-x-auto');
        $response->assertSee('whitespace-nowrap');
    }

    public function test_footer_renders_only_active_social_media_links(): void
    {
        $socialMediaLinks = [
            ...SocialMediaLinkSettings::valuesFromSettings(collect()),
            'instagram' => 'https://instagram.com/bintanprestige',
            'youtube' => 'https://youtube.com/@bintanprestige',
            'custom_links' => [
                [
                    'label' => 'Pinterest',
                    'abbr' => 'PI',
                    'url' => 'https://pinterest.com/bintanprestige',
                ],
            ],
        ];

        $response = $this->view('frontend.partials.footer', [
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Premium travel services.',
                'copyright_text' => 'All rights reserved.',
            ],
            'contactInformation' => [],
            'contactWhatsappUrl' => 'https://wa.me/?text=Hello',
            'socialMediaLinks' => $socialMediaLinks,
            'activeSocialMediaLinks' => SocialMediaLinkSettings::activeLinks($socialMediaLinks),
        ]);

        $response->assertSee('https://instagram.com/bintanprestige', false);
        $response->assertSee('https://youtube.com/@bintanprestige', false);
        $response->assertSee('https://pinterest.com/bintanprestige', false);
        $response->assertSee('aria-label="Pinterest"', false);
        $response->assertSee('aria-label="Instagram"', false);
        $response->assertDontSee('aria-label="Facebook"', false);
    }
}
