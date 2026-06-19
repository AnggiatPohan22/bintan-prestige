<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class ThemeService
{
    public const CACHE_KEY = 'theme.active.v1';
    public const CACHE_TTL_MINUTES = 30;

    /** Per-request widget cache: area → Collection<Widget>. Populated on first widgetsForArea() call. */
    private ?array $widgetsByArea = null;

    public function getActiveTheme(): ?Theme
    {
        $slug = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Theme::active()->value('slug')
        );

        return $slug ? Theme::where('slug', $slug)->first() : null;
    }

    /**
     * Resolve the layout view name for the given key.
     *
     * Checks: themes/{slug}/layouts/{key}.blade.php → frontend.templates.{key}
     */
    public function resolveLayout(string $key = 'default'): string
    {
        $theme = $this->getActiveTheme();

        if ($theme !== null) {
            $diskPath = $theme->basePath() . '/layouts/' . $key . '.blade.php';

            if (file_exists($diskPath)) {
                View::addNamespace('theme-active', $theme->basePath());

                return 'theme-active::layouts.' . $key;
            }
        }

        return 'frontend.templates.' . $key;
    }

    /**
     * Resolve the partial view name for the given key.
     *
     * Checks: themes/{slug}/partials/{key}.blade.php → frontend.partials.{key}
     */
    public function resolvePartial(string $key): string
    {
        $theme = $this->getActiveTheme();

        if ($theme !== null) {
            $diskPath = $theme->basePath() . '/partials/' . $key . '.blade.php';

            if (file_exists($diskPath)) {
                View::addNamespace('theme-active', $theme->basePath());

                return 'theme-active::partials.' . $key;
            }
        }

        return 'frontend.partials.' . $key;
    }

    /**
     * Return the active theme's stored token overrides, or [] when there is none.
     *
     * @return array<string, string>
     */
    public function getTokenOverrides(): array
    {
        return $this->getActiveTheme()?->customization ?? [];
    }

    /**
     * Return all visible widgets for the given area on the active theme.
     * Loads all widgets in one query on first call, then serves from memory.
     *
     * @return Collection<int, \App\Models\Widget>
     */
    public function widgetsForArea(string $area): Collection
    {
        if ($this->widgetsByArea === null) {
            $theme = $this->getActiveTheme();
            $this->widgetsByArea = [];

            if ($theme !== null) {
                $theme->widgets()
                    ->visible()
                    ->ordered()
                    ->get()
                    ->each(function ($widget): void {
                        $this->widgetsByArea[$widget->area][] = $widget;
                    });
            }
        }

        return collect($this->widgetsByArea[$area] ?? []);
    }

    /** Clear the active-theme cache and reset per-request widget state. Called on Theme/Widget saved/deleted. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->widgetsByArea = null;
    }
}
