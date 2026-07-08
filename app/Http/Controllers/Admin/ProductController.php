<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;

use Illuminate\Http\Request;

use App\Services\ProductService;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;


class ProductController extends Controller
{

    public function __construct(
    protected ProductService $productService,
    ) {}

    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'destination', 'prices'])
            ->withCount([
                'features',
                'faqs',
                'itineraries',
                'images',
                'features as included_features_count' => fn ($query) =>
                    $query->where('label', 'included'),
                'features as excluded_features_count' => fn ($query) =>
                    $query->where('label', 'excluded'),
            ])
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )
            )
            ->when(
                $request->category_id,
                fn ($query) => $query->where(
                    'category_id',
                    $request->category_id
                )
            )
            ->when(
                $request->destination_id,
                fn ($query) => $query->where(
                    'destination_id',
                    $request->destination_id
                )
            )
            ->when(
                $request->status,
                fn ($query) => $query->where(
                    'status',
                    $request->status
                )
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get();
        $destinations = Destination::query()
            ->orderBy('name')
            ->get();
        $statuses = Product::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view(
            'backend.products.index',
            compact('products', 'categories', 'destinations', 'statuses')
        );
    }

    public function create()
    {
        return view('backend.products.create', [
            'categories' => Category::all(),
            'destinations' => Destination::all(),
        ]);
    }

    public function store(
    StoreProductRequest $request
    )
    {
        $product = $this->productService
            ->store($request);

        return redirect()
            ->route(
                'admin.products.index',
                $product
            )
            ->with(
                'success',
                'Product created successfully.'
            );
    }

    public function edit(Product $product)
    {
        $product->load([
            'highlights',
            'features',
            'faqs',
            'itineraries',
            'notes',
            'images',
            'prices',
        ]);    

        return view('backend.products.edit', [
            'product' => $product,
            'categories' => Category::all(),
            'destinations' => Destination::all(),
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    )
    {
        $this->productService
            ->update($request, $product);

        return redirect()
        ->route(
            'admin.products.edit',
            $product
        )
        ->withFragment('product-info-section')
        ->with(
            'success',
            'Product updated successfully.'
        );
    }

    public function updateSearchBooking(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'pickup_available' => [
                'nullable',
                'boolean',
            ],
            'pickup_type' => [
                'nullable',
                'max:255',
            ],
            'pickup_note' => [
                'nullable',
            ],
            'cta_title' => [
                'nullable',
                'max:255',
            ],
            'cta_description' => [
                'nullable',
            ],
            'cta_button_text' => [
                'nullable',
                'max:255',
            ],
            'meta_title' => [
                'nullable',
                'max:255',
            ],
            'meta_description' => [
                'nullable',
            ],
            'meta_keywords' => [
                'nullable',
            ],
            'canonical_url' => [
                'nullable',
                'url',
            ],
        ]);

        $validated['pickup_available'] =
            $request->boolean('pickup_available');

        $product->update($validated);

        return redirect()
            ->back()
            ->withFragment('search-booking-section')
            ->with(
                'success',
                'Search & booking settings saved successfully.'
            );
    }

    // Delete Product
    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with(
            'success',
            'Product deleted successfully.'
        );
    }

    // Toggle Featured
    public function toggleFeatured(Product $product)
    {
        $product->update([
            'is_featured' => !$product->is_featured
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Featured updated successfully.'
        ]);
    }

    // Toggle Status
    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' =>
                $product->status === 'published'
                    ? 'draft'
                    : 'published'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }


    // Delete Product Image
    public function destroyImage(
        ProductImage $image
    ) {
        $product =
            $image->product;

        $wasThumbnail =
            $product
            && $product->thumbnail === $image->image;

        // Only removes a legacy module upload (products/…); Media Library files
        // (media/…) stay — they belong to the library.
        app(\App\Services\ProductImageService::class)
            ->deleteIfModuleOwned($image->image);

        $image->delete();

        if ($wasThumbnail) {
            $product->update([
                'thumbnail' => null,
            ]);

            app(\App\Services\ProductImageService::class)
                ->autoThumbnail($product);
        }

        return redirect()
            ->back()
            ->withFragment('product-info-section')
            ->with(
                'success',
                'Gallery image deleted.'
            );
    }

    public function setThumbnailFromImage(
        ProductImage $image
    ) {
        $image->product->update([
            'thumbnail' => $image->image,
        ]);

        return redirect()
            ->back()
            ->withFragment('product-info-section')
            ->with(
                'success',
                'Thumbnail updated from gallery image.'
            );
    }

    public function destroyThumbnail(
        Product $product
    ) {
        if (! $product->thumbnail) {
            return redirect()
                ->back()
                ->withFragment('product-info-section')
                ->with(
                    'success',
                    'Thumbnail is already empty.'
                );
        }

        $thumbnail =
            $product->thumbnail;

        $isGalleryImage =
            $product->images()
                ->where('image', $thumbnail)
                ->exists();

        if (! $isGalleryImage) {
            app(\App\Services\ProductImageService::class)
                ->deleteIfModuleOwned($thumbnail);
        }

        $product->update([
            'thumbnail' => null,
        ]);

        app(\App\Services\ProductImageService::class)
            ->autoThumbnail($product);

        return redirect()
            ->back()
            ->withFragment('product-info-section')
            ->with(
                'success',
                'Thumbnail removed. If gallery exists, the first image is used automatically.'
            );
    }
}
