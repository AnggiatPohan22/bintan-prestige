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

        $view->with([
            'adminAppearance'    => $appearance,
            'adminAppearanceCss' => $this->service->toCssVars($appearance),
        ]);
    }
}
