<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\FooterSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalFooterSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_footer_settings_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $legacyQuickLinks = [
            ['label' => 'Legacy Packages', 'url' => '/products', 'is_external' => false],
        ];
        $legacyUtilityLinks = [
            ['label' => 'Legacy Privacy', 'url' => '/privacy', 'is_external' => false],
        ];

        SiteSetting::create([
            'key' => FooterSettings::QUICK_LINKS_KEY,
            'label' => 'Legacy footer quick links',
            'value' => json_encode($legacyQuickLinks),
            'type' => 'json',
            'group' => FooterSettings::GROUP,
            'is_active' => true,
        ]);
        SiteSetting::create([
            'key' => FooterSettings::UTILITY_LINKS_KEY,
            'label' => 'Legacy footer utility links',
            'value' => json_encode($legacyUtilityLinks),
            'type' => 'json',
            'group' => FooterSettings::GROUP,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.footer-settings.update'), [
                'footer_settings' => [
                    'logo_source' => 'icon',
                    'bottom_note' => 'Built for Bintan guests.',
                    'show_cta' => '0',
                    'show_newsletter' => '0',
                    'show_social_links' => '1',
                    'show_contact_column' => '1',
                ],
                'footer_quick_links' => [
                    [
                        'label' => 'Packages',
                        'url' => '/products',
                    ],
                    [
                        'label' => 'Instagram',
                        'url' => 'https://instagram.com/bintanprestige',
                        'is_external' => '1',
                    ],
                ],
                'footer_utility_links' => [
                    [
                        'label' => 'Privacy Policy',
                        'url' => '/privacy-policy',
                    ],
                ],
                'footer_layout_blocks' => [
                    [
                        'type' => 'quick_links',
                        'title' => 'Quick Links',
                        'width' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'type' => 'maps',
                        'title' => 'Find Us',
                        'width' => '2',
                        'is_active' => '1',
                        'settings' => [
                            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=test',
                        ],
                    ],
                    [
                        'type' => 'utility_links',
                        'title' => 'Utility Pages',
                        'width' => '1',
                        'is_active' => '0',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'footer-settings']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'footer.logo_source',
            'value' => 'icon',
            'type' => 'select',
            'group' => 'footer_settings',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'footer.show_cta',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'footer_settings',
            'is_active' => true,
        ]);

        $quickLinks = json_decode(SiteSetting::query()
            ->where('key', FooterSettings::QUICK_LINKS_KEY)
            ->value('value'), true);

        $utilityLinks = json_decode(SiteSetting::query()
            ->where('key', FooterSettings::UTILITY_LINKS_KEY)
            ->value('value'), true);

        $this->assertSame($legacyQuickLinks, $quickLinks);
        $this->assertSame($legacyUtilityLinks, $utilityLinks);

        $layoutBlocks = json_decode(SiteSetting::query()
            ->where('key', FooterSettings::LAYOUT_BLOCKS_KEY)
            ->value('value'), true);

        $this->assertCount(3, $layoutBlocks);
        $this->assertSame('maps', $layoutBlocks[1]['type']);
        $this->assertSame('2', $layoutBlocks[1]['width']);
        $this->assertFalse($layoutBlocks[2]['is_active']);
    }

    public function test_footer_settings_tab_only_shows_footer_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'footer-settings']));

        $response->assertOk();
        $response->assertSee('Footer Settings');
        $response->assertSee('Footer Display');
        $response->assertSee('Footer logo source');
        $response->assertSee('Quick Links');
        $response->assertSee('Utility Links');
        $response->assertSee('Footer links are managed in the Menu Manager.');
        $response->assertSee(route('admin.menus.index'), false);
        $response->assertDontSee('data-add-footer-link', false);
        $response->assertDontSee('data-footer-links-list', false);
        $response->assertSee('Layout Blocks');
        $response->assertSee('Maps embed URL');
        $response->assertSee('Show social links');
        $response->assertDontSee('Header Settings');
        $response->assertDontSee('Upload favicon');
    }

    public function test_footer_layout_blocks_cannot_exceed_three_active_columns(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.settings.global-assets.edit', ['tab' => 'footer-settings']))
            ->put(route('admin.settings.global-assets.footer-settings.update'), [
                'footer_settings' => [
                    'logo_source' => 'light',
                    'bottom_note' => 'Designed for premium island travel.',
                    'show_cta' => '1',
                    'show_newsletter' => '1',
                    'show_social_links' => '1',
                    'show_contact_column' => '1',
                ],
                'footer_layout_blocks' => [
                    [
                        'type' => 'quick_links',
                        'title' => 'Quick Links',
                        'width' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'type' => 'contact_info',
                        'title' => 'Information',
                        'width' => '1',
                        'is_active' => '1',
                    ],
                    [
                        'type' => 'maps',
                        'title' => 'Find Us',
                        'width' => '2',
                        'is_active' => '1',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'footer-settings']));
        $response->assertSessionHasErrors('footer_layout_blocks');
    }

    public function test_frontend_footer_uses_footer_settings_without_duplicating_contact_or_social_data(): void
    {
        $footerSettings = [
            ...FooterSettings::valuesFromSettings(collect()),
            'logo_source' => 'none',
            'bottom_note' => 'Built for Bintan guests.',
            'show_cta' => false,
            'show_newsletter' => false,
            'show_social_links' => true,
            'show_contact_column' => true,
            'quick_links' => [
                [
                    'label' => 'Packages',
                    'url' => '/products',
                    'is_external' => false,
                ],
            ],
            'layout_blocks' => [
                [
                    'type' => 'quick_links',
                    'title' => 'Quick Links',
                    'width' => '1',
                    'is_active' => true,
                    'settings' => [],
                ],
                [
                    'type' => 'maps',
                    'title' => 'Find Us',
                    'width' => '2',
                    'is_active' => true,
                    'settings' => [
                        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=test',
                        'custom_body' => '',
                    ],
                ],
                [
                    'type' => 'utility_links',
                    'title' => 'Utility Pages',
                    'width' => '1',
                    'is_active' => false,
                    'settings' => [],
                ],
            ],
            'utility_links' => [
                [
                    'label' => 'Privacy Policy',
                    'url' => '/privacy-policy',
                    'is_external' => false,
                ],
            ],
        ];

        $response = $this->view('frontend.partials.footer', [
            'siteAssets' => collect(),
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Global business description.',
                'copyright_text' => 'All rights reserved.',
            ],
            'contactInformation' => [
                'email' => 'hello@bintanprestige.com',
                'phone' => '+62 812 3456 7890',
                'address' => 'Bintan Island',
                'opening_hours' => 'Open Daily',
            ],
            'contactWhatsappUrl' => 'https://wa.me/6281234567890',
            'socialMediaLinks' => [
                'instagram' => 'https://instagram.com/bintanprestige',
            ],
            'activeSocialMediaLinks' => [
                [
                    'label' => 'Instagram',
                    'abbr' => 'IG',
                    'url' => 'https://instagram.com/bintanprestige',
                ],
            ],
            'footerSettings' => $footerSettings,
        ]);

        $response->assertSee('Packages');
        $response->assertSee('href="http://localhost/products"', false);
        $response->assertDontSee('Privacy Policy');
        $response->assertSee('Find Us');
        $response->assertSee('bp-footer__column--span-2', false);
        $response->assertSee('https://www.google.com/maps/embed?pb=test', false);
        $response->assertSee('https://instagram.com/bintanprestige', false);
        $response->assertSee('Built for Bintan guests.');
        $response->assertDontSee('bp-footer-cta');
        $response->assertDontSee('footer-newsletter');
    }
}
