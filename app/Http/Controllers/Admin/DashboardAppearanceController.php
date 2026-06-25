<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDashboardAppearanceRequest;
use App\Services\AdminAppearanceService;
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
        return view('backend.settings.appearance.index', [
            'appearance' => $this->service->getCurrent(),
            'presets'    => config('admin_appearance_presets'),
        ]);
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
}
