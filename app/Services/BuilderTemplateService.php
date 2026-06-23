<?php

namespace App\Services;

use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Models\PageTemplate;
use App\Models\User;
use App\Support\InlineContentSanitizer;
use App\Support\PageTemplateRegistry;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuilderTemplateService
{
    public function __construct(
        private readonly BuilderTreeSanitizer $treeSanitizer,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, Page $page, User $user): BuilderTemplate
    {
        $rawTree = $attributes['template_data'] ?? [];
        if (! is_array($rawTree)) {
            throw ValidationException::withMessages(['template_data' => 'Template data must be a block tree.']);
        }

        $name = InlineContentSanitizer::plaintext((string) ($attributes['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'The template name must contain text.']);
        }

        $baseTemplate = $this->resolveBaseTemplate($attributes['base_template_id'] ?? $page->template_id);

        return BuilderTemplate::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $this->optionalPlaintext($attributes['description'] ?? null),
            'category' => $this->optionalPlaintext($attributes['category'] ?? null),
            'template_type' => BuilderTemplate::TYPE_PAGE,
            'schema_version' => BuilderTemplate::SCHEMA_VERSION,
            'base_template_id' => $baseTemplate?->id,
            'thumbnail' => null,
            'template_data' => $this->treeSanitizer->sanitizeTree($rawTree, 'template_data'),
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function resolveBaseTemplate(mixed $id): ?PageTemplate
    {
        if (! is_numeric($id)) {
            return null;
        }

        return PageTemplate::query()
            ->active()
            ->whereIn('blade_file', PageTemplateRegistry::keys())
            ->find((int) $id);
    }

    private function optionalPlaintext(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = InlineContentSanitizer::plaintext($value);

        return $value === '' ? null : $value;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'template';
        $slug = $base;
        $suffix = 2;

        while (BuilderTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
