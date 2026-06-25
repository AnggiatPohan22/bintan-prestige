<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDashboardAppearanceRequest;
use App\Services\AdminAppearanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardAppearanceController extends Controller
{
    public function __construct(
        private readonly AdminAppearanceService $service
    ) {}

    public function index(): View
    {
        $palettesConfig = config('admin_palettes');

        return view('backend.settings.appearance.index', [
            'appearance'     => $this->service->getCurrent(),
            'darkTokens'     => $this->service->paletteFor('dark'),
            'lightTokens'    => $this->service->paletteFor('light'),
            'sections'       => $palettesConfig['sections'] ?? [],
            'tokenCatalogue' => $palettesConfig['tokens'] ?? [],
            'presets'        => $palettesConfig['presets'] ?? [],
            'initialMode'    => $this->service->resolveModeForUser(auth()->user()),
        ]);
    }

    /**
     * AJAX: save a single mode's palette (dark_palette or light_palette JSON column).
     */
    public function savePalette(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode'   => ['required', 'in:dark,light'],
            'tokens' => ['required', 'array'],
        ]);

        $column = $data['mode'] === 'dark' ? 'dark_palette' : 'light_palette';
        $tokens = $this->sanitizeTokens($data['tokens']);

        $this->service->update([$column => $tokens], $request->user());

        return response()->json(['ok' => true]);
    }

    public function update(UpdateDashboardAppearanceRequest $request): RedirectResponse
    {
        $this->service->update($request->validated(), $request->user());

        return redirect()
            ->route('admin.settings.appearance.index')
            ->with('success', 'Tampilan dashboard berhasil disimpan.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->service->reset($request->user());

        return redirect()
            ->route('admin.settings.appearance.index')
            ->with('success', 'Tampilan dashboard direset ke default.');
    }

    /** Strip unknown keys and sanitize values to hex / rgba only. */
    private function sanitizeTokens(array $raw): array
    {
        $allowed = array_keys(config('admin_palettes.tokens', []));
        $clean   = [];

        foreach ($raw as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }
            $value = trim((string) $value);
            // Allow #hex, rgba(...), rgb(...) only
            if (! preg_match('/^(#[0-9A-Fa-f]{3,8}|rgba?\([^)]+\))$/', $value)) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }
}
