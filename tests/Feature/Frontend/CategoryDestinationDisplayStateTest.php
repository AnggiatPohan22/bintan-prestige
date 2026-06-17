<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\SiteAsset;
use App\Support\CategoryDestinationDisplayState;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDestinationDisplayStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_display_state_is_text_first_and_uses_public_product_context(): void
    {
        $category = Category::factory()->create([
            'name' => ' Island Tours ',
            'slug' => 'island-tours',
            'description' => '  Curated Bintan island routes.  ',
        ]);
        $otherCategory = Category::factory()->create();
        $destination = Destination::factory()->create();
        $inactiveDestination = Destination::factory()->create([
            'is_active' => false,
        ]);

        $visibleProduct = $this->createProduct([
            'name' => 'Visible Category Display Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Draft Category Display Tour',
            'status' => 'draft',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Inactive Destination Category Tour',
            'status' => 'published',
        ], $category, $inactiveDestination);
        $this->createProduct([
            'name' => 'Other Category Display Tour',
            'status' => 'published',
        ], $otherCategory, $destination);

        $products = CategoryDestinationDisplayState::categoryProductsQuery($category)
            ->orderBy('products.id')
            ->paginate(9);
        $state = CategoryDestinationDisplayState::category(
            $category,
            $products,
            [
                'query' => [
                    'category' => [$category->id],
                    'destination' => [$destination->id],
                    'page' => 3,
                ],
                'selected' => [
                    'destination' => [(string) $destination->id],
                ],
            ],
            'price_low'
        );

        $this->assertSame('Island Tours', $state['pageTitle']);
        $this->assertSame('Curated Bintan island routes.', $state['description']);
        $this->assertTrue($state['descriptionState']['has_description']);
        $this->assertSame('text_first', $state['mediaStrategy']['type']);
        $this->assertFalse($state['mediaStrategy']['available']);
        $this->assertSame(1, $state['productCount']);
        $this->assertSame($visibleProduct->id, $state['products']->first()->id);
        $this->assertTrue($state['products']->first()->relationLoaded('category'));
        $this->assertTrue($state['products']->first()->relationLoaded('destination'));
        $this->assertTrue($state['products']->first()->relationLoaded('prices'));
        $this->assertTrue($state['products']->first()->relationLoaded('images'));
        $this->assertSame(route('products.index', ['category' => [$category->id]]), $state['baseUrl']);
        $this->assertSame($state['baseUrl'], $state['resetUrl']);
        $this->assertArrayNotHasKey('page', $state['filters']['query']);
        $this->assertArrayNotHasKey('category', $state['filters']['query']);
        $this->assertSame([$destination->id], $state['filters']['query']['destination']);
        $this->assertSame('price_low', $state['filters']['query']['sort']);
        $this->assertSame([(string) $category->id], $state['filters']['pagination']['category']);
        $this->assertSame('has_results', $state['emptyState']['type']);
        $this->assertSame('Products', $state['breadcrumbs'][1]['label']);
        $this->assertSame('Island Tours', $state['breadcrumbs']->last()['label']);
        $this->assertTrue($state['breadcrumbs']->last()['current']);
        $this->assertFalse($state['metadataReady']['is_final_seo']);
    }

    public function test_category_display_state_handles_empty_filtered_and_high_page_contexts(): void
    {
        $category = Category::factory()->create([
            'name' => 'Empty Category',
            'description' => '',
        ]);
        $destination = Destination::factory()->create();

        $emptyProducts = CategoryDestinationDisplayState::categoryProductsQuery($category)
            ->paginate(9);
        $emptyState = CategoryDestinationDisplayState::category($category, $emptyProducts);

        $this->assertNull($emptyState['description']);
        $this->assertFalse($emptyState['descriptionState']['has_description']);
        $this->assertSame('entity_empty', $emptyState['emptyState']['type']);
        $this->assertSame('No packages are currently available for this category', $emptyState['emptyState']['title']);

        $filteredState = CategoryDestinationDisplayState::category(
            $category,
            $emptyProducts,
            [
                'query' => [
                    'destination' => [$destination->id],
                ],
            ]
        );

        $this->assertSame('filtered_empty', $filteredState['emptyState']['type']);
        $this->assertSame($filteredState['baseUrl'], $filteredState['emptyState']['action_url']);

        for ($i = 1; $i <= 10; $i++) {
            $this->createProduct([
                'name' => 'High Page Category Tour ' . $i,
                'status' => 'published',
            ], $category, $destination);
        }

        $highPageProducts = CategoryDestinationDisplayState::categoryProductsQuery($category)
            ->orderBy('products.id')
            ->paginate(9, ['*'], 'page', 3);
        $highPageState = CategoryDestinationDisplayState::category($category, $highPageProducts);

        $this->assertSame(10, $highPageState['productCount']);
        $this->assertSame(0, $highPageState['products']->count());
        $this->assertSame('high_page_empty', $highPageState['emptyState']['type']);
    }

    public function test_destination_display_state_prepares_media_fallback_and_public_product_context(): void
    {
        $category = Category::factory()->create([
            'name' => 'Destination Display Category',
            'slug' => 'destination-display-category',
        ]);
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Destination Display Category',
            'slug' => 'inactive-destination-display-category',
            'is_active' => false,
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi Bay',
            'slug' => 'lagoi-bay',
            'description' => '  Resort destination in Bintan. ',
            'image' => 'destinations/lagoi.jpg',
        ]);
        $otherDestination = Destination::factory()->create([
            'name' => 'Tanjung Pinang',
            'slug' => 'tanjung-pinang-display-state',
        ]);

        $visibleProduct = $this->createProduct([
            'name' => 'Visible Destination Display Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Draft Destination Display Tour',
            'status' => 'draft',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Inactive Category Destination Tour',
            'status' => 'published',
        ], $inactiveCategory, $destination);
        $this->createProduct([
            'name' => 'Other Destination Display Tour',
            'status' => 'published',
        ], $category, $otherDestination);

        $products = CategoryDestinationDisplayState::destinationProductsQuery($destination)
            ->orderBy('products.id')
            ->paginate(9);
        $state = CategoryDestinationDisplayState::destination(
            $destination,
            $products,
            collect(),
            [],
            [
                'query' => [
                    'destination' => [$destination->id],
                    'category' => [$category->id],
                    'page' => 4,
                ],
            ],
            'newest'
        );

        $this->assertSame('Lagoi Bay', $state['pageTitle']);
        $this->assertSame('Resort destination in Bintan.', $state['description']);
        $this->assertTrue($state['media']['available']);
        $this->assertSame(asset('storage/destinations/lagoi.jpg'), $state['media']['url']);
        $this->assertSame('Lagoi Bay destination image', $state['media']['alt']);
        $this->assertFalse($state['media']['is_fallback']);
        $this->assertSame(1, $state['productCount']);
        $this->assertSame($visibleProduct->id, $state['products']->first()->id);
        $this->assertTrue($state['products']->first()->relationLoaded('category'));
        $this->assertTrue($state['products']->first()->relationLoaded('destination'));
        $this->assertArrayNotHasKey('page', $state['filters']['query']);
        $this->assertArrayNotHasKey('destination', $state['filters']['query']);
        $this->assertSame([$category->id], $state['filters']['query']['category']);
        $this->assertSame([(string) $destination->id], $state['filters']['pagination']['destination']);
        $this->assertSame('Destinations', $state['breadcrumbs'][2]['label']);
        $this->assertSame('Lagoi Bay', $state['breadcrumbs']->last()['label']);
        $this->assertSame($state['media']['url'], $state['metadataReady']['image']);
    }

    public function test_destination_display_state_uses_default_media_when_image_is_missing(): void
    {
        $destination = Destination::factory()->create([
            'name' => 'Trikora Coast',
            'description' => '',
            'image' => null,
        ]);
        $fallback = SiteAsset::create([
            'key' => 'default_media.destination',
            'label' => 'Destination fallback',
            'path' => 'defaults/destination.jpg',
            'alt' => 'Default destination image',
            'is_active' => true,
        ]);

        $products = CategoryDestinationDisplayState::destinationProductsQuery($destination)
            ->paginate(9);
        $state = CategoryDestinationDisplayState::destination(
            $destination,
            $products,
            collect([$fallback->key => $fallback]),
            ['destination' => ['fit' => 'contain']]
        );

        $this->assertNull($state['description']);
        $this->assertFalse($state['descriptionState']['has_description']);
        $this->assertTrue($state['media']['available']);
        $this->assertSame(asset('storage/defaults/destination.jpg'), $state['media']['url']);
        $this->assertSame('Default destination image', $state['media']['alt']);
        $this->assertSame('contain', $state['media']['fit']);
        $this->assertTrue($state['media']['is_fallback']);
        $this->assertSame('entity_empty', $state['emptyState']['type']);
        $this->assertSame('No packages are currently available for this destination', $state['emptyState']['title']);
    }

    public function test_active_entity_lookup_rejects_invalid_inactive_and_archived_slugs(): void
    {
        $activeCategory = Category::factory()->create([
            'slug' => 'active-category',
        ]);
        $inactiveCategory = Category::factory()->create([
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);
        $archivedDestination = Destination::factory()->create([
            'slug' => 'archived-destination',
        ]);
        $activeDestination = Destination::factory()->create([
            'slug' => 'active-destination',
        ]);
        $archivedDestination->delete();

        $this->assertTrue(CategoryDestinationDisplayState::activeCategoryBySlug(' active-category ')->is($activeCategory));
        $this->assertTrue(CategoryDestinationDisplayState::activeDestinationBySlug('active-destination')->is($activeDestination));

        $this->expectException(ModelNotFoundException::class);
        CategoryDestinationDisplayState::activeCategoryBySlug($inactiveCategory->slug);
    }

    public function test_active_destination_lookup_rejects_archived_and_invalid_slugs(): void
    {
        $archivedDestination = Destination::factory()->create([
            'slug' => 'archived-destination',
        ]);
        $archivedDestination->delete();

        $this->expectException(ModelNotFoundException::class);
        CategoryDestinationDisplayState::activeDestinationBySlug('archived-destination');
    }

    private function createProduct(
        array $attributes = [],
        ?Category $category = null,
        ?Destination $destination = null
    ): Product {
        $category ??= Category::factory()->create();
        $destination ??= Destination::factory()->create();

        return Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            ...$attributes,
        ]);
    }
}
