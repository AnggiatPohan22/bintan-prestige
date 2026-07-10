<?php

namespace App\Support;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Small helper that keeps per-row-per-locale translation persistence out of the
 * child controllers' hot path (Phase 7 — B6.1).
 *
 * Every product-child controller (Highlight, Feature, FAQ, Itinerary, Note)
 * calls `ChildTranslations::syncFromRequest($request, $model, [...fields])` at
 * the end of store()/update(). One method, one code-path, one place to audit.
 */
class ChildTranslations
{
    /**
     * Persist `translations[{locale}][{field}]` from the request onto the model.
     * Only non-default active locales are read; empty values clear the sidecar
     * row so the base column fallback resumes.
     *
     * @param  list<string>  $fields  allow-list; anything not in the list is ignored.
     */
    public static function syncFromRequest(Request $request, Model $model, array $fields): void
    {
        // The trait check keeps this safe if the helper is ever pointed at the
        // wrong model — a silent no-op is preferable to a fatal.
        if (! self::usesTranslatable($model)) {
            return;
        }

        $payload = (array) $request->input('translations', []);

        foreach (Locales::nonDefaultActive() as $locale) {
            foreach ($fields as $field) {
                $value = $payload[$locale][$field] ?? null;
                // usesTranslatable() confirmed the method is present. PHPStan
                // can't infer trait-mixed methods on a bare Model type, so
                // dispatch via call_user_func to stay type-clean.
                call_user_func(
                    [$model, 'setTranslation'],
                    $field,
                    $locale,
                    is_string($value) ? trim($value) : null,
                );
            }
        }
    }

    /** Validation rules to merge into a child controller's rules array. */
    public static function rulesFor(array $fields, int $maxLength = 5000): array
    {
        $rules = [
            'translations'   => ['nullable', 'array'],
            'translations.*' => ['nullable', 'array'],
        ];

        foreach ($fields as $field) {
            $rules["translations.*.{$field}"] = ['nullable', 'string', 'max:'.$maxLength];
        }

        return $rules;
    }

    private static function usesTranslatable(Model $model): bool
    {
        return in_array(Translatable::class, class_uses_recursive($model), true);
    }
}
