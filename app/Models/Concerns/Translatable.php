<?php

namespace App\Models\Concerns;

use App\Models\Translation;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Translatable (Phase 7 — B1).
 *
 * Gives a model per-locale attribute values via the polymorphic `translations`
 * sidecar, WITHOUT touching its own table. The base column always holds the
 * default-locale value; the sidecar holds non-default locales only, with
 * fallback to the base column when a translation is missing.
 *
 * Declare the translatable fields on the model:
 *
 *   use App\Models\Concerns\Translatable;
 *   protected array $translatable = ['name', 'description'];
 *
 * Declaring `$translatable` is REQUIRED — the trait reads it directly (it cannot
 * declare the property itself without conflicting with a model's own default).
 *
 * Read with `->translate('name')` (current locale) and ALWAYS eager-load to
 * avoid N+1: `Product::query()->withTranslations()->get()` (current locale) or
 * `->withAllTranslations()` for admin edit screens (all locales).
 */
trait Translatable
{
    /**
     * Registers a delete hook so translations don't outlive their owner. Soft
     * deletes are respected: translations survive a soft delete (for restore)
     * and are purged only on a real/force delete.
     */
    public static function bootTranslatable(): void
    {
        static::deleted(function ($model): void {
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

            if ($usesSoftDeletes && ! $model->isForceDeleting()) {
                return; // soft delete — keep translations for a potential restore
            }

            $model->translations()->delete();
        });
    }

    /** @return MorphMany<Translation, $this> */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * Fields this model exposes for translation. The consuming model must declare
     * `protected array $translatable = [...]`.
     *
     * @return list<string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable;
    }

    public function isTranslatableField(string $field): bool
    {
        return in_array($field, $this->getTranslatableAttributes(), true);
    }

    /**
     * Resolve a field for the given (or current) locale, falling back to the
     * base column when no non-empty translation exists. The default locale
     * always reads the base column.
     */
    public function translate(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?: Locales::current();
        $base = $this->getAttribute($field);

        if ($locale === Locales::default() || ! $this->isTranslatableField($field)) {
            return $base;
        }

        $value = $this->rawTranslation($field, $locale);

        return ($value === null || $value === '') ? $base : $value;
    }

    /**
     * The raw sidecar value for a field+locale, or null if none. Reads from the
     * loaded `translations` relation (in-memory) so it never fires a query per
     * attribute — the relation is loaded once (eager or lazy) per model.
     */
    public function rawTranslation(string $field, string $locale): ?string
    {
        $match = $this->translations
            ->first(fn (Translation $t): bool => $t->locale === $locale && $t->field === $field);

        return $match?->value;
    }

    /** True when a non-empty translation exists for this field+locale. */
    public function hasTranslation(string $field, string $locale): bool
    {
        $value = $this->rawTranslation($field, $locale);

        return $value !== null && $value !== '';
    }

    /**
     * Upsert (or clear) a translation. Writing the default locale updates the
     * base column instead of the sidecar; writing an empty value removes the
     * sidecar row so resolution falls back to the base column.
     */
    public function setTranslation(string $field, string $locale, ?string $value): void
    {
        if ($locale === Locales::default()) {
            $this->setAttribute($field, $value);

            return;
        }

        if ($value === null || $value === '') {
            $this->translations()
                ->where('locale', $locale)
                ->where('field', $field)
                ->delete();
        } else {
            $this->translations()->updateOrCreate(
                ['locale' => $locale, 'field' => $field],
                ['value' => $value]
            );
        }

        // Drop the cached relation so the next read reflects the change.
        if ($this->relationLoaded('translations')) {
            $this->unsetRelation('translations');
        }
    }

    /**
     * Eager-load only the given (or current) locale's translations. Use on
     * list/detail queries — this is the N+1 guard (C2).
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithTranslations(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?: Locales::current();

        return $query->with(['translations' => fn ($q) => $q->where('locale', $locale)]);
    }

    /**
     * Eager-load every locale's translations — for admin edit screens that show
     * all locales at once.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithAllTranslations(Builder $query): Builder
    {
        return $query->with('translations');
    }
}
