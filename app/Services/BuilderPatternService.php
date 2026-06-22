<?php

namespace App\Services;

use App\Models\BuilderPattern;
use App\Models\User;
use App\Support\InlineContentSanitizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuilderPatternService
{
    public function __construct(
        private readonly PageBlockService $pageBlockService,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $user): BuilderPattern
    {
        $rawPattern = $attributes['pattern_data'] ?? [];
        if (! is_array($rawPattern)) {
            throw ValidationException::withMessages(['pattern_data' => 'Pattern data must be a block object.']);
        }

        $nodeCount = 0;
        $patternData = $this->sanitizeNode($rawPattern, $nodeCount);
        $name = InlineContentSanitizer::plaintext((string) ($attributes['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'The pattern name must contain text.']);
        }

        $description = $this->optionalPlaintext($attributes['description'] ?? null);
        $category = $this->optionalPlaintext($attributes['category'] ?? null);

        return BuilderPattern::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $description,
            'category' => $category,
            'thumbnail' => null,
            'pattern_data' => $patternData,
            'block_type' => $patternData['block_type'],
            'is_global' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /** @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function sanitizeNode(array $node, int &$nodeCount, int $depth = 0, ?string $parentType = null): array
    {
        if ($depth > 5 || ++$nodeCount > 200) {
            throw ValidationException::withMessages(['pattern_data' => 'A pattern may contain at most 200 blocks and five nesting levels.']);
        }

        $validTypes = array_keys((array) config('blocks', []));
        $validated = Validator::make($node, [
            'block_type' => ['required', 'string', Rule::in($validTypes)],
            'label' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'is_visible' => ['nullable', 'boolean'],
            'children' => ['nullable', 'array'],
        ])->validate();

        $blockType = (string) $validated['block_type'];
        if ($parentType === 'columns' && $blockType !== 'group') {
            throw ValidationException::withMessages(['pattern_data' => 'Columns patterns may contain Group blocks only.']);
        }

        $children = $validated['children'] ?? [];
        if (! is_array($children)) {
            $children = [];
        }
        if ($children !== [] && ! in_array($blockType, ['group', 'columns'], true)) {
            throw ValidationException::withMessages(['pattern_data' => 'Only Group and Columns blocks may contain pattern children.']);
        }

        $sanitizedChildren = [];
        foreach ($children as $child) {
            if (! is_array($child)) {
                throw ValidationException::withMessages(['pattern_data' => 'Every pattern child must be a block object.']);
            }
            $sanitizedChildren[] = $this->sanitizeNode($child, $nodeCount, $depth + 1, $blockType);
        }

        $rawData = $validated['data'] ?? [];
        $data = $this->pageBlockService->validateAndSanitizeData(
            $blockType,
            is_array($rawData) ? $rawData : [],
        );
        $label = InlineContentSanitizer::plaintext((string) ($validated['label'] ?? ''));

        return [
            'block_type' => $blockType,
            'label' => $label !== '' ? $label : (string) config("blocks.{$blockType}.label", Str::headline($blockType)),
            'data' => $data,
            'is_visible' => (bool) ($validated['is_visible'] ?? true),
            'children' => $sanitizedChildren,
        ];
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
        $base = Str::slug($name) ?: 'pattern';
        $slug = $base;
        $suffix = 2;

        while (BuilderPattern::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
