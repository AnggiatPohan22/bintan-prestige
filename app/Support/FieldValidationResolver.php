<?php

namespace App\Support;

use App\Models\ContentType;
use App\Models\Field;

final class FieldValidationResolver
{
    /**
     * Build data.* validation rules for every field in a content type.
     * Returns rules keyed by validation path ('data.field_key').
     *
     * @return array<string, list<string>>
     */
    public function resolveForContentType(ContentType $contentType): array
    {
        $groups = $contentType->fieldGroups()
            ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $rules = [];

        foreach ($groups as $group) {
            foreach ($group->fields as $field) {
                foreach ($this->resolveForField($field) as $path => $fieldRules) {
                    $rules[$path] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * Resolve validation rules for a single field.
     * Returns one or more entries keyed by validation path
     * (array types produce an extra 'data.key.*' entry).
     *
     * @return array<string, list<string>>
     */
    public function resolveForField(Field $field, string $prefix = 'data'): array
    {
        $key = $prefix . '.' . $field->key;

        return match ($field->type) {
            'gallery'      => $this->arrayWithScalarItems($field, $key, ['nullable', 'integer', 'min:1']),
            'checkbox'     => $this->arrayWithScalarItems($field, $key, ['nullable', 'string', 'max:255']),
            'relationship' => $this->arrayWithScalarItems($field, $key, ['nullable', 'integer', 'min:1']),
            'repeater'     => $this->arrayWithArrayItems($field, $key),
            // The catalog defines two conflicting date_format rules for datetime; use 'date' instead.
            'datetime'     => [$key => $this->applyRequired($field, ['nullable', 'date'])],
            default        => [$key => $this->catalogRules($field)],
        };
    }

    /**
     * Build rules from the field-types catalog, resolving {setting} placeholders.
     * Rules whose placeholders are not configured in the field's settings are dropped.
     *
     * @return list<string>
     */
    private function catalogRules(Field $field): array
    {
        $typeDef  = FieldTypeRegistry::get($field->type);
        $settings = $field->settings ?? [];
        $base     = $typeDef !== null
            ? (array) ($typeDef['validation_rules'] ?? ['nullable'])
            : ['nullable'];

        $resolved = [];

        foreach ($base as $rule) {
            $ruleStr = (string) $rule;

            // No placeholder — include as-is.
            if (! str_contains($ruleStr, '{')) {
                $resolved[] = $ruleStr;
                continue;
            }

            // Collect all placeholder keys from the rule string.
            $count = preg_match_all('/\{(\w+)\}/', $ruleStr, $matches);

            if ($count === false || $count === 0) {
                $resolved[] = $ruleStr;
                continue;
            }

            /** @var list<non-empty-string> $placeholders */
            $placeholders = $matches[1];

            // If any placeholder is missing from settings, skip this rule entirely.
            $allPresent = true;
            foreach ($placeholders as $placeholder) {
                if (! array_key_exists($placeholder, $settings)) {
                    $allPresent = false;
                    break;
                }
            }

            if (! $allPresent) {
                continue;
            }

            // Resolve all placeholders.
            $resolvedRule = preg_replace_callback(
                '/\{(\w+)\}/',
                fn (array $m): string => (string) ($settings[$m[1]] ?? ''),
                $ruleStr
            );

            if ($resolvedRule !== null) {
                $resolved[] = $resolvedRule;
            }
        }

        return $this->applyRequired($field, $resolved);
    }

    /**
     * Prepend 'required' (removing 'nullable') or ensure 'nullable' is present.
     *
     * @param  list<string> $rules
     * @return list<string>
     */
    private function applyRequired(Field $field, array $rules): array
    {
        if ($field->is_required) {
            $rules = array_values(
                array_filter($rules, fn (string $r): bool => $r !== 'nullable')
            );
            array_unshift($rules, 'required');
        } else {
            if (! in_array('nullable', $rules, true)) {
                array_unshift($rules, 'nullable');
            }
        }

        return $rules;
    }

    /**
     * Array-typed field whose items are scalars (gallery IDs, checkbox strings, etc.).
     *
     * @param  list<string>               $itemRules
     * @return array<string, list<string>>
     */
    private function arrayWithScalarItems(Field $field, string $key, array $itemRules): array
    {
        return [
            $key        => $this->applyRequired($field, ['nullable', 'array']),
            $key . '.*' => $itemRules,
        ];
    }

    /**
     * Array-typed field whose items are themselves arrays (repeater rows).
     *
     * @return array<string, list<string>>
     */
    private function arrayWithArrayItems(Field $field, string $key): array
    {
        return [
            $key        => $this->applyRequired($field, ['nullable', 'array']),
            $key . '.*' => ['nullable', 'array'],
        ];
    }
}
