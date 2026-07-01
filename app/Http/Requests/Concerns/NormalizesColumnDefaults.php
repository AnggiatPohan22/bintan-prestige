<?php

namespace App\Http\Requests\Concerns;

/**
 * Guards NOT-NULL columns that carry a database-level DEFAULT against
 * explicit-NULL inserts coming from empty form fields.
 *
 * Laravel's ConvertEmptyStringsToNull middleware turns a blank input into null;
 * validated() then hands that null to create()/update(). MySQL rejects it because
 * a column DEFAULT only applies when the column is OMITTED, not when NULL is
 * passed explicitly (SQLSTATE[23000] "Column 'x' cannot be null").
 *
 * Call from prepareForValidation() with a map of column => fallback value.
 */
trait NormalizesColumnDefaults
{
    /**
     * Coerce present-but-empty request keys to a fallback default.
     *
     * Only keys already present in the request are touched, so a partial update
     * that omits a field keeps the stored value (and a create that omits a field
     * still lets the DB default apply).
     *
     * @param array<string, mixed> $defaults column => fallback value
     */
    protected function applyColumnDefaults(array $defaults): void
    {
        $merge = [];

        foreach ($defaults as $key => $default) {
            if (! $this->exists($key)) {
                continue;
            }

            $value = $this->input($key);

            if ($value === null || $value === '') {
                $merge[$key] = $default;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
