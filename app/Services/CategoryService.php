<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryService
{
    public function store(Request $request): Category
    {
        return Category::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->input('image') ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): Category {
        $category->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->input('image') ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return $category->refresh();
    }
}
