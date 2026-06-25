<?php

namespace App\View\Composers;

use App\Services\AdminAppearanceService;
use Illuminate\View\View;

class AdminAppearanceComposer
{
    public function __construct(
        private readonly AdminAppearanceService $service
    ) {}

    public function compose(View $view): void
    {
        $appearance  = $this->service->getCurrent();
        $resolved    = $this->service->resolveModeForUser(auth()->user());

        // Inject customizer CSS only when the resolved mode matches the global
        // preset. If the user overrode to the opposite mode, omit it so the
        // built-in [data-admin-mode="light"] block in admin.css applies cleanly.
        $css = $resolved === $appearance->mode
            ? $this->service->toCssVars($appearance)
            : null;

        $view->with([
            'adminAppearance'    => $appearance,
            'adminAppearanceCss' => $css,
            'adminUiMode'        => $resolved,
        ]);
    }
}
