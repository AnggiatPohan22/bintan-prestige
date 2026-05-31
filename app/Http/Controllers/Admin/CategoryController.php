<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
    ) {}

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount('products')
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )
            )
            ->latest()
            ->paginate(10, ['*'], 'categories_page')
            ->withQueryString();

        $archivedCategories = Category::onlyTrashed()
            ->withCount('products')
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )
            )
            ->latest('deleted_at')
            ->paginate(10, ['*'], 'archive_page')
            ->withQueryString();

        return view('backend.categories.index', [
            'categories' => $categories,
            'archivedCategories' => $archivedCategories,
        ]);
    }

    public function create()
    {
        return view('backend.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService
            ->store($request);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('backend.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ) {
        $this->categoryService
            ->update($request, $category);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category moved to archive.');
    }

    public function restore(int $category)
    {
        Category::onlyTrashed()
            ->findOrFail($category)
            ->restore();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category restored successfully.');
    }

    public function forceDelete(int $category)
    {
        $category = Category::onlyTrashed()
            ->withCount('products')
            ->findOrFail($category)
;

        if ($category->products_count > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with(
                    'error',
                    'Category cannot be permanently deleted because it still has products.'
                );
        }

        $category->forceDelete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category permanently deleted.');
    }
}
