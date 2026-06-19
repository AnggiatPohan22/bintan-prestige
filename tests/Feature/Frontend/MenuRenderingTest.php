<?php

namespace Tests\Feature\Frontend;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\FooterSettings;
use App\Support\NavigationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_and_mobile_render_the_same_managed_menu_tree(): void
    {
        $menu = $this->menu('Header Menu', 'header');
        $parent = $this->item($menu, 'Manager Packages', '/manager-packages', 0);
        $this->item($menu, 'Manager Private Trip', '/manager-private-trip', 0, $parent->id);

        $response = $this->view('frontend.partials.header', $this->headerData([
            ['label' => 'Legacy Header Link', 'url' => '/legacy-header', 'is_external' => false, 'children' => []],
        ]));
        $html = (string) $response;

        $response->assertSee('Manager Packages')
            ->assertSee('Manager Private Trip')
            ->assertDontSee('Legacy Header Link');
        $this->assertSame(2, substr_count($html, 'href="http://localhost/manager-packages"'));
        $this->assertSame(2, substr_count($html, 'href="http://localhost/manager-private-trip"'));
    }

    public function test_managed_empty_header_does_not_resurrect_legacy_links(): void
    {
        $this->menu('Header Menu', 'header');

        $this->view('frontend.partials.header', $this->headerData([
            ['label' => 'Legacy Header Link', 'url' => '/legacy-header', 'is_external' => false, 'children' => []],
        ]))->assertDontSee('Legacy Header Link');
    }

    public function test_missing_menu_manager_location_still_uses_legacy_header_fallback(): void
    {
        $this->view('frontend.partials.header', $this->headerData([
            ['label' => 'Legacy Header Link', 'url' => '/legacy-header', 'is_external' => false, 'children' => []],
        ]))->assertSee('Legacy Header Link');
    }

    public function test_footer_uses_managed_quick_links_and_respects_empty_managed_utility_menu(): void
    {
        $quickMenu = $this->menu('Footer Quick', 'footer_quick');
        $this->item($quickMenu, 'Manager Footer Link', '/manager-footer', 0);
        $this->menu('Footer Utility', 'footer_utility');

        $footerSettings = [
            ...FooterSettings::valuesFromSettings(collect()),
            'show_cta' => false,
            'show_newsletter' => false,
            'show_social_links' => false,
            'show_contact_column' => false,
            'quick_links' => [
                ['label' => 'Legacy Quick Link', 'url' => '/legacy-quick', 'is_external' => false],
            ],
            'utility_links' => [
                ['label' => 'Legacy Utility Link', 'url' => '/legacy-utility', 'is_external' => false],
            ],
            'layout_blocks' => [
                ['type' => 'quick_links', 'title' => 'Quick Links', 'width' => '1', 'is_active' => true, 'settings' => []],
                ['type' => 'utility_links', 'title' => 'Utility Links', 'width' => '1', 'is_active' => true, 'settings' => []],
            ],
        ];

        $this->view('frontend.partials.footer', [
            'siteAssets' => collect(),
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Managed footer test.',
                'copyright_text' => 'All rights reserved.',
            ],
            'contactInformation' => [],
            'contactWhatsappUrl' => 'https://wa.me/620000000000',
            'activeSocialMediaLinks' => [],
            'footerSettings' => $footerSettings,
        ])->assertSee('Manager Footer Link')
            ->assertDontSee('Legacy Quick Link')
            ->assertDontSee('Legacy Utility Link');
    }

    private function headerData(array $legacyItems): array
    {
        return [
            'siteAssets' => collect(),
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'navigationSettings' => [
                ...NavigationSettings::valuesFromSettings(collect()),
                'items' => $legacyItems,
            ],
        ];
    }

    private function menu(string $name, string $location): Menu
    {
        return Menu::create([
            'name' => $name,
            'location' => $location,
            'is_active' => true,
        ]);
    }

    private function item(
        Menu $menu,
        string $label,
        string $url,
        int $sortOrder,
        ?int $parentId = null,
    ): MenuItem {
        return MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'label' => $label,
            'link_type' => 'url',
            'url' => $url,
            'target' => '_self',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
