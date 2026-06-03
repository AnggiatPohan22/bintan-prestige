<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\PageSection;
use App\Services\PageSectionService;

class PageSectionController extends Controller
{
    public function __construct(
        protected PageSectionService $pageSectionService,
    ) {}

    public function index()
    {
        return view('admin.page-sections.index', [
            'pageSections' => $this->pageSectionService->getAdminSections(),
            'tableExists' => $this->pageSectionService->tableExists(),
        ]);
    }

    public function edit(PageSection $pageSection)
    {
        return view('admin.page-sections.edit', [
            'pageSection' => $pageSection,
            'extraDataJson' => $pageSection->extra_data
                ? json_encode($pageSection->extra_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '',
        ]);
    }

    public function update(
        UpdatePageSectionRequest $request,
        PageSection $pageSection
    )
    {
        $pageSection->update($request->pageSectionData());

        return redirect()
            ->route('admin.page-sections.edit', $pageSection)
            ->with('success', 'Page section updated successfully.');
    }
}
