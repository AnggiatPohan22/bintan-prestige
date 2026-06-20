<?php

namespace App\Support;

/**
 * CMS Hook & Filter system — inspired by WordPress action/filter API.
 *
 * Actions: fire-and-forget notifications. Multiple listeners can react to the
 *          same event; their return values are ignored.
 *
 * Filters: value-transformation pipeline. Each listener receives the current
 *          value, modifies it, and MUST return it. Listeners run in priority
 *          order; the final return value is the transformed result.
 *
 * Priority: lower number runs first (default 10). Ties are resolved by
 *           registration order.
 */
class HookManager
{
    /** @var array<string, array<int, callable[]>> */
    private array $actions = [];

    /** @var array<string, array<int, callable[]>> */
    private array $filters = [];

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute all callbacks registered on $hook, passing $args to each.
     * Returns nothing — use filters when you need a return value.
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        if (empty($this->actions[$hook])) {
            return;
        }

        ksort($this->actions[$hook]);

        foreach ($this->actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function hasAction(string $hook): bool
    {
        return ! empty($this->actions[$hook]);
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    /**
     * Pass $value through every filter callback registered on $hook.
     * Each callback receives (currentValue, ...$extraArgs) and MUST return the value.
     * Returns the original $value unchanged if no filters are registered.
     */
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (empty($this->filters[$hook])) {
            return $value;
        }

        ksort($this->filters[$hook]);

        foreach ($this->filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function hasFilter(string $hook): bool
    {
        return ! empty($this->filters[$hook]);
    }

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    /**
     * Remove all action and filter callbacks for the given hook.
     * Primarily useful in tests to isolate state between test methods.
     */
    public function removeAll(string $hook): void
    {
        unset($this->actions[$hook], $this->filters[$hook]);
    }
}
