<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\User;
use App\Support\SeoDefaultSettings;
use App\Support\StructuredDataSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalStructuredDataSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_structured_data_settings_from_global_assets(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.structured-data.update'), [
                'structured_data' => [
                    'enabled' => '1',
                    'business_enabled' => '1',
                    'business_type' => 'TravelAgency',
                    'business_name_override' => 'Bintan Prestige Travel',
                    'legal_name_override' => 'PT Bintan Prestige',
                    'description_override' => 'Private Bintan tours and transfers.',
                    'price_range' => '$$$',
                    'currencies' => 'IDR, SGD',
                    'area_served' => 'Bintan Island',
                    'service_type' => 'Private island tours',
                    'opening_hours_source' => 'custom',
                    'custom_opening_hours' => 'Mo-Su 08:00-22:00',
                    'website_enabled' => '1',
                    'breadcrumbs_enabled' => '1',
                    'product_enabled' => '1',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'structured-data']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'structured_data.business.type',
            'value' => 'TravelAgency',
            'type' => 'select',
            'group' => StructuredDataSettings::GROUP,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'structured_data.product.enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => StructuredDataSettings::GROUP,
            'is_active' => true,
        ]);
    }

    public function test_structured_data_tab_only_shows_structured_data_form(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'structured-data']));

        $response->assertOk();
        $response->assertSee('Structured Data / Business Schema');
        $response->assertSee('Business schema type');
        $response->assertSee('Website Schema');
        $response->assertSee('Product / Tour Schema');
        $response->assertSee('older SEO settings stay compatible');
        $response->assertDontSee('Default Media / Placeholder Assets');
        $response->assertDontSee('GA4 measurement ID');
    }

    public function test_frontend_renders_single_structured_data_graph_without_duplicate_organization_schema(): void
    {
        $response = $this->view('layouts.frontend', [
            'slot' => '',
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'legal_name' => 'PT Bintan Prestige',
                'short_description' => 'Premium Bintan travel experiences.',
            ],
            'contactInformation' => [
                'email' => 'hello@bintanprestige.com',
                'phone' => '+62 823 8635 7012',
                'address' => 'Bintan Island, Indonesia',
                'opening_hours' => 'Open Daily',
            ],
            'seoDefaultSettings' => [
                ...SeoDefaultSettings::valuesFromSettings(collect()),
                'site_name' => 'Bintan Prestige',
                'canonical_base_url' => 'https://bintanprestige.test',
                'enable_organization_schema' => true,
            ],
            'structuredDataSettings' => StructuredDataSettings::valuesFromSettings(collect()),
            'activeSocialMediaLinks' => collect([
                ['url' => 'https://instagram.com/bintanprestige'],
            ]),
            'siteAssets' => collect(),
        ]);

        $html = (string) $response;

        $this->assertSame(1, substr_count($html, 'application/ld+json'));
        $this->assertStringContainsString('"@graph"', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
    }

    public function test_product_detail_renders_product_and_breadcrumb_schema_when_enabled(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Lagoi Private Tour',
            'short_description' => 'Private Lagoi tour package.',
            'meta_title' => null,
            'meta_description' => null,
            'canonical_url' => null,
        ]);

        $response = $this->view('frontend.products.show', [
            'product' => $product,
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => [],
            'seoDefaultSettings' => [
                ...SeoDefaultSettings::valuesFromSettings(collect()),
                'site_name' => 'Bintan Prestige',
                'canonical_base_url' => 'https://bintanprestige.test',
                'enable_organization_schema' => true,
            ],
            'structuredDataSettings' => StructuredDataSettings::valuesFromSettings(collect()),
            'siteAssets' => collect(),
            'activeSocialMediaLinks' => collect(),
        ]);

        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('Lagoi Private Tour');
        $response->assertSee(route('products.show', $product), false);
    }

    public function test_business_schema_respects_seo_default_organization_toggle(): void
    {
        $response = $this->view('partials.site-structured-data', [
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => [],
            'seoDefaultSettings' => [
                ...SeoDefaultSettings::valuesFromSettings(collect()),
                'enable_organization_schema' => false,
            ],
            'structuredDataSettings' => [
                ...StructuredDataSettings::valuesFromSettings(collect()),
                'website_enabled' => false,
                'breadcrumbs_enabled' => false,
                'product_enabled' => false,
            ],
            'siteAssets' => collect(),
            'activeSocialMediaLinks' => collect(),
        ]);

        $response->assertDontSee('application/ld+json', false);
    }

    public function test_structured_data_can_be_disabled(): void
    {
        $response = $this->view('partials.site-structured-data', [
            'structuredDataSettings' => [
                ...StructuredDataSettings::valuesFromSettings(collect()),
                'enabled' => false,
            ],
        ]);

        $response->assertDontSee('application/ld+json', false);
    }

    private function publishedProduct(array $attributes = []): Product
    {
        Category::factory()->create();
        Destination::factory()->create();

        return Product::factory()
            ->create([
                ...$attributes,
                'status' => 'published',
            ]);
    }
}
