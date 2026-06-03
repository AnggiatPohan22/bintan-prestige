<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\PageSection;
use App\Models\PageSectionMedia;
use App\Services\PageSectionImageService;
use App\Services\PageSectionService;
use Illuminate\Validation\ValidationException;

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
        $pageSection->load('media');

        return view('admin.page-sections.edit', [
            'pageSection' => $pageSection,
            'extraDataJson' => $pageSection->extra_data
                ? json_encode($pageSection->extra_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : '',
        ]);
    }

    public function update(UpdatePageSectionRequest $request, PageSection $pageSection)
    {
        $data = $request->pageSectionData();

        if ($request->hasFile('image_upload')) {
            $data['image'] = $this->pageSectionImageService
                ->storeUploadedImage($request->file('image_upload'), $pageSection->image, $pageSection);
        }

        if ($request->hasFile('mobile_image_upload')) {
            $data['mobile_image'] = $this->pageSectionImageService
                ->storeUploadedImage($request->file('mobile_image_upload'), $pageSection->mobile_image, $pageSection);
        }

        $pageSection->update($data);

        if ($request->hasFile('media_uploads')) {
            $files = $request->file('media_uploads');
            $currentCount = $pageSection->media()->count();

            if ($currentCount + count($files) > 10) {
                throw ValidationException::withMessages([
                    'media_uploads' => 'Maximum 10 section images are allowed.',
                ]);
            }

            $nextSortOrder = (int) $pageSection->media()->max('sort_order') + 10;

            foreach ($files as $file) {
                $this->pageSectionImageService
                    ->storeMediaUpload($pageSection, $file, $nextSortOrder);
                $nextSortOrder += 10;
            }
        }

        return redirect()
            ->route('admin.page-sections.edit', $pageSection)
            ->with('success', 'Page section updated successfully.');
    }

    public function destroyMedia(PageSectionMedia $media)
    {
        $pageSection = $media->pageSection;

        $this->pageSectionImageService->deleteMedia($media);

        return redirect()
            ->route('admin.page-sections.edit', $pageSection)
            ->with('success', 'Section image deleted successfully.');
    }
}
