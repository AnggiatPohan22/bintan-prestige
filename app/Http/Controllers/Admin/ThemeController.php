<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportThemeRequest;
use App\Http\Requests\Admin\UpdateThemeCustomizationRequest;
use App\Models\Theme;
use App\Services\GoogleFontsService;
use App\Services\ThemeDiscoveryService;
use App\Services\ZipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThemeController extends Controller
{
    public function __construct(
        private readonly ThemeDiscoveryService $discoveryService,
        private readonly ZipService $zipService,
        private readonly GoogleFontsService $googleFontsService,
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
        $fontList  = $this->googleFontsService->getFontList();

        return view('backend.themes.customize', compact('theme', 'schema', 'overrides', 'fontList'));
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

        // Save the selected Google Font family (non-CSS metadata stored alongside tokens).
        $fontInput = trim((string) $request->input('google_font', ''));
        if (filled($fontInput)) {
            // Allow only printable letters, digits, and spaces — valid in a font family name.
            $fontFamily = preg_replace('/[^a-zA-Z0-9 ]/', '', $fontInput);
            if (filled($fontFamily)) {
                $sanitized['_google_font'] = $fontFamily;
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

    public function export(Theme $theme): StreamedResponse
    {
        $zipPath  = $this->zipService->createFromDirectory(
            $theme->basePath(),
            ['theme_config.json' => $this->buildThemeConfig($theme)],
        );

        $filename = $theme->slug . '-' . ($theme->version ?? '1.0.0') . '.zip';

        return response()->streamDownload(function () use ($zipPath): void {
            readfile($zipPath);
            @unlink($zipPath);
        }, $filename, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(ImportThemeRequest $request): RedirectResponse
    {
        $zipPath = $request->file('theme_zip')->getRealPath();

        try {
            $manifest = $this->zipService->extractTheme($zipPath, base_path('themes'));
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        $this->discoveryService->scan();

        return redirect()
            ->route('admin.themes.index')
            ->with('success', "Theme \"{$manifest['name']}\" imported and registered successfully.");
    }

    private function buildThemeConfig(Theme $theme): string
    {
        $widgets = $theme->widgets()
            ->ordered()
            ->get(['area', 'widget_type', 'title', 'data', 'sort_order', 'is_visible'])
            ->toArray();

        return json_encode([
            'exported_at'   => now()->toISOString(),
            'customization' => $theme->customization ?? [],
            'widgets'       => $widgets,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
