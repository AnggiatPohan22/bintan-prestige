<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBuilderPatternRequest;
use App\Models\BuilderPattern;
use App\Services\BuilderPatternService;
use Illuminate\Http\JsonResponse;

class BuilderPatternController extends Controller
{
    public function __construct(
        private readonly BuilderPatternService $patternService,
    ) {}

    public function index(): JsonResponse
    {
        $patterns = BuilderPattern::query()
            ->latest()
            ->get()
            ->map(fn (BuilderPattern $pattern): array => $this->payload($pattern));

        return response()->json(['patterns' => $patterns]);
    }

    public function store(StoreBuilderPatternRequest $request): JsonResponse
    {
        $pattern = $this->patternService->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'pattern' => $this->payload($pattern),
        ], 201);
    }

    public function show(BuilderPattern $builderPattern): JsonResponse
    {
        return response()->json(['pattern' => $this->payload($builderPattern)]);
    }

    public function destroy(BuilderPattern $builderPattern): JsonResponse
    {
        $builderPattern->delete();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(BuilderPattern $pattern): array
    {
        return [
            'id' => $pattern->id,
            'name' => $pattern->name,
            'slug' => $pattern->slug,
            'description' => $pattern->description,
            'category' => $pattern->category,
            'thumbnail' => $pattern->thumbnail,
            'block_type' => $pattern->block_type,
            'is_global' => $pattern->is_global,
            'pattern_data' => $pattern->pattern_data,
            'created_by' => $pattern->created_by,
            'updated_at' => $pattern->updated_at?->toIso8601String(),
        ];
    }
}
