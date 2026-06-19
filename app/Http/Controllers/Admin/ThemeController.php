<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateThemeCustomizationRequest;
use App\Models\Theme;
use App\Services\ThemeDiscoveryService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(
        private readonly ThemeDiscoveryService $discoveryService,
        private readonly ThemeService $themeService,
    ) {}

    public function index(): View
    {
        $themes = Theme::ordered()->get();

        return view('backend.themes.index', compact('themes'));
    }

    public function scan(): RedirectResponse
    {
        $discovered = $this->discoveryService->scan();
        $count      = count($discovered);

        $message = $count > 0
            ? "{$count} " . Str::plural('theme', $count) . ' found and registered.'
            : 'No new themes found. Make sure theme.json is present in each themes/ subdirectory.';

        return redirect()
            ->route('admin.themes.index')
            ->with($count > 0 ? 'success' : 'info', $message);
    }

    public function activate(Theme $theme): RedirectResponse
    {
        Theme::query()->where('id', '!=', $theme->id)->update(['is_active' => false]);
        $theme->update(['is_active' => true]);

        return redirect()
            ->route('admin.themes.index')
            ->with('success', "\"{$theme->name}\" is now the active theme.");
    }

    public function customize(Theme $theme): View
    {
        $schema    = $theme->customizationSchema();
        $overrides = $theme->customization ?? [];

        return view('backend.themes.customize', compact('theme', 'schema', 'overrides'));
    }

    public function updateCustomization(UpdateThemeCustomizationRequest $request, Theme $theme): RedirectResponse
    {
        $schema = $theme->customizationSchema();

        // Collect all valid CSS variable keys declared in this theme's schema.
        $validKeys = collect($schema)
            ->flatMap(fn ($group) => collect($group['tokens'] ?? [])->pluck('key'))
            ->all();

        $submitted = $request->input('tokens', []);
        $sanitized = [];

        foreach ($validKeys as $cssVar) {
            if (! array_key_exists($cssVar, $submitted) || ! filled($submitted[$cssVar])) {
                continue;
            }

            // Strip characters that could break out of a CSS :root { } block.
            $value = preg_replace('/[<>"\'{}\\\\\n\r]/', '', trim($submitted[$cssVar]));

            if (filled($value)) {
                $sanitized[$cssVar] = $value;
            }
        }

        $theme->update(['customization' => $sanitized ?: null]);

        return redirect()
            ->route('admin.themes.customize', $theme)
            ->with('success', 'Theme customization saved.');
    }

    public function resetCustomization(Theme $theme): RedirectResponse
    {
        $theme->update(['customization' => null]);

        return redirect()
            ->route('admin.themes.customize', $theme)
            ->with('success', 'Theme customization reset to defaults.');
    }
}
