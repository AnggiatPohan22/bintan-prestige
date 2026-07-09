<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_sets_thumbnail_and_gallery_from_media_library_paths(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload([
                'thumbnail' => 'media/product/2026/07/thumb.webp',
                'gallery' => [
                    'media/product/2026/07/g1.webp',
                    'media/product/2026/07/g2.webp',
                ],
            ]))
            ->assertRedirect();

        $product = Product::query()->firstOrFail();

        $this->assertSame('media/product/2026/07/thumb.webp', $product->thumbnail);
        $this->assertSame(
            ['media/product/2026/07/g1.webp', 'media/product/2026/07/g2.webp'],
            $product->images()->orderBy('sort_order')->pluck('image')->all(),
        );
    }

    public function test_auto_thumbnail_uses_first_gallery_image_when_thumbnail_is_empty(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload([
                'gallery' => [
                    'media/product/2026/07/first.webp',
                    'media/product/2026/07/second.webp',
                ],
            ]))
            ->assertRedirect();

        $product = Product::query()->firstOrFail();

        $this->assertSame('media/product/2026/07/first.webp', $product->thumbnail);
    }

    public function test_replacing_a_legacy_thumbnail_cleans_the_old_file_but_keeps_library_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // Legacy product thumbnail stored under the module-owned products/ folder.
        Storage::disk('public')->put('products/old-thumb.webp', 'x');
        Storage::disk('public')->put('media/product/2026/07/new-thumb.webp', 'x');

        Category::factory()->create();
        Destination::factory()->create();
        $product = Product::factory()->create(['thumbnail' => 'products/old-thumb.webp']);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'thumbnail' => 'media/product/2026/07/new-thumb.webp',
            ]))
            ->assertRedirect();

        $this->assertSame('media/product/2026/07/new-thumb.webp', $product->fresh()->thumbnail);
        // Legacy module file removed; library file untouched.
        Storage::disk('public')->assertMissing('products/old-thumb.webp');
        Storage::disk('public')->assertExists('media/product/2026/07/new-thumb.webp');
    }

    public function test_deleting_a_gallery_image_keeps_the_media_library_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('media/product/2026/07/g1.webp', 'x');

        Category::factory()->create();
        Destination::factory()->create();
        $product = Product::factory()->create(['thumbnail' => null]);
        $image = ProductImage::create([
            'product_id' => $product->id,
            'image' => 'media/product/2026/07/g1.webp',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.images.destroy', $image))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        // Library file must survive a gallery-row deletion.
        Storage::disk('public')->assertExists('media/product/2026/07/g1.webp');
    }

    public function test_product_form_renders_the_media_library_pickers(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create();
        Destination::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Add Gallery Images')
            ->assertSee('Media Library')
            ->assertDontSee('type="file" name="gallery[]"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        // Reuse existing rows so tests that also seed a Category/Destination for
        // Product::factory() don't hit a slug-unique collision.
        $category = Category::first() ?? Category::factory()->create();
        $destination = Destination::first() ?? Destination::factory()->create();

        return array_merge([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Lagoi Private Tour',
            'slug' => 'lagoi-private-tour-'.uniqid(),
            'short_description' => 'A short description of the tour.',
            'description' => 'A longer description of the private tour experience.',
            'meeting_point' => 'Lagoi Bay',
            'duration' => 'Full Day',
            'whatsapp_number' => '628123456789',
            'idr_price' => 1500000,
            'sgd_price' => 150,
            'status' => 'published',
        ], $overrides);
    }
}
