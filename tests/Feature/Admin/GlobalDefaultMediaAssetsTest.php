<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\DefaultMediaAssets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalDefaultMediaAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_default_media_placeholder_assets(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.default-media.update'), [
                'default_media' => [
                    'product' => UploadedFile::fake()->image('product.jpg', 1200, 900),
                    'hero' => UploadedFile::fake()->image('hero.jpg', 1600, 900),
                ],
                'default_media_alts' => [
                    'product' => 'Default product placeholder',
                    'hero' => 'Default hero placeholder',
                ],
                'default_media_fits' => [
                    'product' => 'contain',
                    'hero' => 'cover',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'default-media']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'default_media.product',
            'label' => 'Product placeholder image',
            'alt' => 'Default product placeholder',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'default_media.product.fit',
            'value' => 'contain',
            'type' => 'select',
            'group' => DefaultMediaAssets::SETTINGS_GROUP,
            'is_active' => true,
        ]);

        $productAsset = SiteAsset::where('key', 'default_media.product')->firstOrFail();
        Storage::disk('public')->assertExists($productAsset->path);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.default-media.destroy', 'product'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'default-media']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'default_media.product',
            'path' => null,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertMissing($productAsset->path);
    }

    public function test_default_media_tab_only_shows_default_media_form(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'default-media']));

        $response->assertOk();
        $response->assertSee('Default Media / Placeholder Assets');
        $response->assertSee('Product placeholder image');
        $response->assertSee('Mobile hero placeholder image');
        $response->assertSee('Avatar placeholder image');
        $response->assertSee('Image fit');
        $response->assertSee('Cover');
        $response->assertSee('Contain');
        $response->assertSee('No image uploaded');
        $response->assertSee('Clear selected image');
        $response->assertDontSee('Default meta title');
        $response->assertDontSee('GA4 measurement ID');
    }

    public function test_default_media_tab_shows_saved_preview_and_reset_action(): void
    {
        $admin = User::factory()->create();

        $asset = SiteAsset::create([
            'key' => 'default_media.product',
            'label' => 'Product placeholder image',
            'path' => 'site-assets/default_media-product/product.jpg',
            'alt' => 'Saved product placeholder',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'default-media']));

        $response->assertOk();
        $response->assertSee($asset->url, false);
        $response->assertSee('Saved image active');
        $response->assertSee('Delete / Reset to system fallback');
    }

    public function test_product_views_use_product_placeholder_when_thumbnail_is_missing(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Lagoi Private Tour',
            'thumbnail' => null,
        ]);
        $siteAssets = $this->createPlaceholderAssets();

        $card = $this->view('frontend.components.product-card', [
            'product' => $product,
            'siteAssets' => $siteAssets,
            'defaultMediaSettings' => [
                'product' => ['fit' => 'contain'],
            ],
        ]);
        $detail = $this->view('frontend.products.show', [
            'product' => $product,
            'siteAssets' => $siteAssets,
            'defaultMediaSettings' => [
                'product' => ['fit' => 'contain'],
            ],
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => [],
        ]);

        $card->assertSee('site-assets/default_media-product/product.jpg', false);
        $card->assertSee('style="object-fit: contain"', false);
        $card->assertDontSee('No Image');
        $detail->assertSee('site-assets/default_media-product/product.jpg', false);
    }

    public function test_home_sections_use_default_media_placeholders(): void
    {
        $siteAssets = $this->createPlaceholderAssets();

        $home = $this->view('frontend.home', [
            'sections' => collect(),
            'heroBackgroundUrl' => null,
            'categories' => collect(),
            'destinations' => collect(),
            'featuredProducts' => collect(),
            'popularProducts' => collect(),
            'homeProducts' => collect(),
            'homeProductCategories' => collect(),
            'faqs' => collect(),
            'siteAssets' => $siteAssets,
            'defaultMediaSettings' => [
                'hero' => ['fit' => 'contain'],
                'hero_mobile' => ['fit' => 'scale-down'],
                'section' => ['fit' => 'contain'],
            ],
        ]);

        $home->assertSee('data-hero-background="' . asset('storage/site-assets/default_media-hero/hero.jpg') . '"', false);
        $home->assertSee('data-hero-mobile-background="' . asset('storage/site-assets/default_media-hero_mobile/hero_mobile.jpg') . '"', false);
        $home->assertSee('data-hero-background-fit="contain"', false);
        $home->assertSee('data-hero-mobile-background-fit="scale-down"', false);
        $home->assertSee('site-assets/default_media-section/section.jpg', false);
    }

    private function createPlaceholderAssets()
    {
        foreach (DefaultMediaAssets::variants() as $variant) {
            SiteAsset::create([
                'key' => $variant['key'],
                'label' => $variant['label'],
                'path' => 'site-assets/' . str_replace('.', '-', $variant['key']) . '/' . $variant['slug'] . '.jpg',
                'alt' => $variant['label'],
                'is_active' => true,
            ]);
        }

        return SiteAsset::where('is_active', true)->get()->keyBy('key');
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
