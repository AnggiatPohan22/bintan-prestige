<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\PageSection;
use App\Services\PageSectionImageService;
use App\Services\PageSectionService;

class PageSectionController extends Controller
{
    public function __construct(
        protected PageSectionService $pageSectionService,
        protected PageSectionImageService $pageSectionImageService,
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
    ) {
        $data = $request->pageSectionData();

        if ($request->hasFile('image')) {
            $data['image'] = $this->pageSectionImageService
                ->storeUploadedImage($request->file('image'), $pageSection->image);
        }

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $this->pageSectionImageService
                ->storeUploadedImage($request->file('mobile_image'), $pageSection->mobile_image);
        }

        $pageSection->update($data);

        return redirect()
            ->route('admin.page-sections.edit', $pageSection)
            ->with('success', 'Page section updated successfully.');
    }
}
