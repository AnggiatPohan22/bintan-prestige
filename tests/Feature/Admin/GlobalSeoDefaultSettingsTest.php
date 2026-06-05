<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SeoDefaultSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSeoDefaultSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_seo_default_settings_from_global_assets(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.seo-default.update'), [
                'seo_default' => [
                    'meta_title' => 'Bintan Prestige Tours',
                    'meta_description' => 'Premium Bintan travel experiences.',
                    'keywords' => 'bintan,tour,travel',
                    'title_suffix' => 'Bintan Prestige',
                    'title_separator' => '|',
                    'site_name' => 'Bintan Prestige',
                    'canonical_base_url' => 'https://bintanprestige.test',
                    'robots' => 'index, follow',
                    'locale' => 'en_US',
                    'language' => 'en',
                    'og_title' => 'Bintan Prestige',
                    'og_description' => 'Explore Bintan with us.',
                    'og_image_alt' => 'Bintan Prestige social preview',
                    'twitter_card_type' => 'summary_large_image',
                    'enable_organization_schema' => '1',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'seo-default']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'seo.default.meta_title',
            'value' => 'Bintan Prestige Tours',
            'type' => 'text',
            'group' => SeoDefaultSettings::GROUP,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'seo.default.enable_organization_schema',
            'value' => '1',
            'type' => 'boolean',
            'group' => SeoDefaultSettings::GROUP,
            'is_active' => true,
        ]);
    }

    public function test_seo_default_tab_only_shows_seo_form(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'seo-default']));

        $response->assertOk();
        $response->assertSee('SEO Default');
        $response->assertSee('Default meta title');
        $response->assertSee('Canonical base URL');
        $response->assertSee('Default OG image');
        $response->assertDontSee('Footer Display');
        $response->assertDontSee('Header Settings');
    }

    public function test_product_specific_seo_overrides_seo_default_settings(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Lagoi Private Tour',
            'short_description' => 'Fallback product short description.',
            'meta_title' => 'Custom Product SEO Title',
            'meta_description' => 'Custom product SEO description.',
            'meta_keywords' => 'custom,product',
            'canonical_url' => 'https://example.com/custom-product',
        ]);

        $response = $this->view('frontend.products.show', [
            'product' => $product,
            'seoTitle' => $product->meta_title ?: $product->name,
            'seoDescription' => $product->meta_description ?: $product->short_description,
            'seoKeywords' => $product->meta_keywords,
            'canonicalUrl' => $product->canonical_url ?: route('products.show', $product),
            'seoImage' => $product->og_image_url ?: $product->thumbnail_url,
            'socialShareType' => 'product',
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Default business description.',
            ],
            'seoDefaultSettings' => [
                ...SeoDefaultSettings::valuesFromSettings(collect()),
                'meta_title' => 'Global SEO Title',
                'meta_description' => 'Global SEO description.',
                'keywords' => 'global,keywords',
                'title_suffix' => 'Bintan Prestige',
                'canonical_base_url' => 'https://bintanprestige.test',
            ],
        ]);

        $response->assertSee('Custom Product SEO Title | Bintan Prestige');
        $response->assertSee('Custom product SEO description.');
        $response->assertSee('custom,product');
        $response->assertSee('https://example.com/custom-product', false);
        $response->assertDontSee('Global SEO description.');
    }

    public function test_product_empty_seo_can_fallback_to_product_content_before_seo_default(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Bintan Family Package',
            'short_description' => 'Family package short description.',
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'canonical_url' => null,
        ]);

        $response = $this->view('frontend.products.show', [
            'product' => $product,
            'seoTitle' => $product->meta_title ?: $product->name,
            'seoDescription' => $product->meta_description ?: $product->short_description,
            'seoKeywords' => $product->meta_keywords,
            'canonicalUrl' => $product->canonical_url ?: route('products.show', $product),
            'seoImage' => $product->og_image_url ?: $product->thumbnail_url,
            'socialShareType' => 'product',
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Default business description.',
            ],
            'seoDefaultSettings' => [
                ...SeoDefaultSettings::valuesFromSettings(collect()),
                'meta_title' => 'Global SEO Title',
                'meta_description' => 'Global SEO description.',
                'keywords' => 'global,keywords',
                'title_suffix' => 'Bintan Prestige',
                'canonical_base_url' => 'https://bintanprestige.test',
            ],
        ]);

        $response->assertSee('Bintan Family Package | Bintan Prestige');
        $response->assertSee('Family package short description.');
        $response->assertSee('global,keywords');
        $response->assertSee(route('products.show', $product), false);
        $response->assertDontSee('Global SEO Title | Bintan Prestige');
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
