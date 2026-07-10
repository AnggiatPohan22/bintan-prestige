<?php

namespace App\Services;

use App\Models\Category;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryService
{
    public function store(Request $request): Category
    {
        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $request->input('image') ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncTranslations($request, $category);

        return $category;
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

        $this->syncTranslations($request, $category);

        return $category->refresh();
    }

    /**
     * Persist per-locale translations for name + description (Phase 7 — B6).
     * Only non-default active locales; empty clears the sidecar row (fallback).
     */
    private function syncTranslations(Request $request, Category $category): void
    {
        $translations = (array) $request->input('translations', []);

        foreach (Locales::nonDefaultActive() as $locale) {
            foreach (['name', 'description'] as $field) {
                $value = $translations[$locale][$field] ?? null;
                $category->setTranslation($field, $locale, is_string($value) ? trim($value) : null);
            }
        }
    }
}
