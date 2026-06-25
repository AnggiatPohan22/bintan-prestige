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
        $appearance = $this->service->getCurrent();
        $resolved   = $this->service->resolveModeForUser(auth()->user());

        // Step 19.2+: emit token bag for the *resolved* mode. paletteFor()
        // returns DB JSON when set, otherwise a starter preset. When the bag
        // is empty (legacy installs before Step 19.2 ran), toCssVarsForMode()
        // returns '' and we fall through to the legacy emitter so the page
        // never renders without theme vars.
        $modeCss = $this->service->toCssVarsForMode($resolved);

        if ($modeCss === '') {
            // Legacy path: use stored hex columns, matching old behavior.
            $modeCss = $resolved === $appearance->mode
                ? $this->service->toCssVars($appearance)
                : null;
        }

        $view->with([
            'adminAppearance'    => $appearance,
            'adminAppearanceCss' => $modeCss,
            'adminUiMode'        => $resolved,
        ]);
    }
}
