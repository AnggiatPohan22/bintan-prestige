<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBuilderTemplateRequest;
use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Services\BuilderTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuilderTemplateController extends Controller
{
    public function __construct(
        private readonly BuilderTemplateService $templateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $templates = BuilderTemplate::query()
            ->with('baseTemplate:id,name,blade_file')
            ->where('template_type', BuilderTemplate::TYPE_PAGE)
            ->where('is_active', true)
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->string('category')->trim()->value(), fn ($query, string $category) => $query->where('category', $category))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'templates' => collect($templates->items())->map(fn (BuilderTemplate $template): array => $this->summary($template)),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function store(StoreBuilderTemplateRequest $request, Page $page): JsonResponse
    {
        $template = $this->templateService->create($request->validated(), $page, $request->user());
        $template->load('baseTemplate:id,name,blade_file');

        return response()->json([
            'success' => true,
            'template' => $this->payload($template),
        ], 201);
    }

    public function show(BuilderTemplate $builderTemplate): JsonResponse
    {
        abort_unless($builderTemplate->is_active && $builderTemplate->template_type === BuilderTemplate::TYPE_PAGE, 404);
        $builderTemplate->load('baseTemplate:id,name,blade_file');

        return response()->json(['template' => $this->payload($builderTemplate)]);
    }

    public function destroy(BuilderTemplate $builderTemplate): JsonResponse
    {
        $builderTemplate->delete();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function summary(BuilderTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'category' => $template->category,
            'template_type' => $template->template_type,
            'schema_version' => $template->schema_version,
            'base_template_id' => $template->base_template_id,
            'base_template_name' => $template->base_template_id === null
                ? 'Standard'
                : $template->baseTemplate->name,
            'thumbnail' => $template->thumbnail,
            'created_by' => $template->created_by,
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function payload(BuilderTemplate $template): array
    {
        return $this->summary($template) + [
            'template_data' => $template->template_data,
        ];
    }
}
