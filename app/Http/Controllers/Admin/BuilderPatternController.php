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
        $paginator = BuilderPattern::query()
            ->latest()
            ->paginate(20);

        // Keep `patterns` a flat array for the builder client (it reads
        // `json.patterns`); expose pagination state separately under `meta`.
        return response()->json([
            'patterns' => $paginator->getCollection()
                ->map(fn (BuilderPattern $pattern): array => $this->payload($pattern))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
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
