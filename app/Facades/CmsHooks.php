<?php

namespace App\Facades;

use App\Support\HookManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void   addAction(string $hook, callable $callback, int $priority = 10)
 * @method static void   doAction(string $hook, mixed ...$args)
 * @method static bool   hasAction(string $hook)
 * @method static void   addFilter(string $hook, callable $callback, int $priority = 10)
 * @method static mixed  applyFilters(string $hook, mixed $value, mixed ...$args)
 * @method static bool   hasFilter(string $hook)
 * @method static void   removeAll(string $hook)
 *
 * @see HookManager
 */
class CmsHooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
