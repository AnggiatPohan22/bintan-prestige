<?php

namespace App\Services;

use App\Models\BuilderPattern;
use App\Models\User;
use App\Support\InlineContentSanitizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuilderPatternService
{
    public function __construct(
        private readonly BuilderTreeSanitizer $treeSanitizer,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $user): BuilderPattern
    {
        $rawPattern = $attributes['pattern_data'] ?? [];
        if (! is_array($rawPattern)) {
            throw ValidationException::withMessages(['pattern_data' => 'Pattern data must be a block object.']);
        }

        $patternData = $this->treeSanitizer->sanitizeNode($rawPattern, 'pattern_data');
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
