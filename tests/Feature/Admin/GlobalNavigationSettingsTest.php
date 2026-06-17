<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\NavigationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalNavigationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_header_navigation_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.navigation-settings.update'), [
                'navigation_settings' => [
                    'cta_label' => 'Book Now',
                    'cta_url' => '/#booking',
                    'is_sticky' => '0',
                    'menu_text_color' => '#F8FAFC',
                    'menu_hover_color' => '#D4AF37',
                    'scrolled_menu_text_color' => '#111827',
                    'scrolled_menu_hover_color' => '#0F172A',
                    'dropdown_text_color' => '#1F2937',
                    'dropdown_hover_background' => '#FDE68A',
                ],
                'navigation_items' => [
                    [
                        'label' => 'Packages',
                        'url' => '/products',
                        'children' => [
                            [
                                'label' => 'Private Trip',
                                'url' => '/products/private-trip',
                            ],
                            [
                                'label' => 'External Deal',
                                'url' => 'https://example.com/deal',
                                'is_external' => '1',
                            ],
                        ],
                    ],
                    [
                        'label' => 'Home',
                        'url' => '/',
                    ],
                    [
                        'label' => 'Instagram',
                        'url' => 'https://instagram.com/bintanprestige',
                        'is_external' => '1',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'navigation-settings']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'navigation.header.cta_label',
            'value' => 'Book Now',
            'type' => 'text',
            'group' => 'navigation_settings',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'navigation.header.is_sticky',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'navigation_settings',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'navigation.header.menu_text_color',
            'value' => '#F8FAFC',
            'type' => 'color',
            'group' => 'navigation_settings',
            'is_active' => true,
        ]);

        $items = json_decode(SiteSetting::query()
            ->where('key', NavigationSettings::ITEMS_KEY)
            ->value('value'), true);

        $this->assertCount(3, $items);
        $this->assertSame('Packages', $items[0]['label']);
        $this->assertCount(2, $items[0]['children']);
        $this->assertSame('Private Trip', $items[0]['children'][0]['label']);
        $this->assertTrue($items[0]['children'][1]['is_external']);
        $this->assertSame('Instagram', $items[2]['label']);
        $this->assertTrue($items[2]['is_external']);
    }

    public function test_navigation_settings_tab_only_shows_navigation_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'navigation-settings']));

        $response->assertOk();
        $response->assertSee('Header Navigation');
        $response->assertSee('data-navigation-accordion', false);
        $response->assertSee('Header Settings');
        $response->assertSee('Header CTA label');
        $response->assertSee('Menu Colors');
        $response->assertSee('Menu hover / active');
        $response->assertSee('Dropdown hover background');
        $response->assertSee('Add Menu');
        $response->assertSee('Add Dropdown');
        $response->assertSee('Move Up');
        $response->assertSee('Sticky header');
        $response->assertDontSee('Active route');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_frontend_header_uses_global_navigation_settings(): void
    {
        $navigationSettings = [
            ...NavigationSettings::valuesFromSettings(collect()),
            'cta_label' => 'Reserve Trip',
            'cta_url' => '/#reserve',
            'is_sticky' => false,
            'menu_text_color' => '#F8FAFC',
            'menu_hover_color' => '#D4AF37',
            'scrolled_menu_text_color' => '#111827',
            'scrolled_menu_hover_color' => '#0F172A',
            'dropdown_text_color' => '#1F2937',
            'dropdown_hover_background' => '#FDE68A',
            'items' => [
                [
                    'label' => 'Experiences',
                    'url' => '/experiences',
                    'is_external' => false,
                    'children' => [
                        [
                            'label' => 'Private Trip',
                            'url' => '/experiences/private-trip',
                            'is_external' => false,
                        ],
                    ],
                ],
                [
                    'label' => 'Instagram',
                    'url' => 'https://instagram.com/bintanprestige',
                    'is_external' => true,
                    'children' => [],
                ],
            ],
        ];

        $response = $this->view('frontend.partials.header', [
            'siteAssets' => collect(),
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
            ],
            'navigationSettings' => $navigationSettings,
        ]);

        $response->assertSee('Experiences');
        $response->assertSee('Private Trip');
        $response->assertSee('frontend-nav__dropdown');
        $response->assertSee('data-mobile-nav', false);
        $response->assertSee('data-mobile-nav-toggle', false);
        $response->assertSee('aria-controls="frontend-mobile-menu"', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('id="frontend-mobile-menu"', false);
        $response->assertSee('data-mobile-nav-panel', false);
        $response->assertSee('frontend-mobile-nav__children');
        $response->assertSee('href="http://localhost/experiences"', false);
        $response->assertSee('Reserve Trip');
        $response->assertSee('href="http://localhost/#reserve"', false);
        $response->assertSee('--header-nav-color: #F8FAFC', false);
        $response->assertSee('--header-nav-hover-color: #D4AF37', false);
        $response->assertSee('--header-dropdown-hover-background: #FDE68A', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('frontend-header--inline');
        $response->assertDontSee('data-frontend-header', false);
        $response->assertDontSee('Plan Trip');
    }
}
