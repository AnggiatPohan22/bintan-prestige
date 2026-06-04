<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Models\PageSectionMedia;
use App\Services\PageSectionImageService;
use App\Support\HomepageSectionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PageSectionController extends Controller
{
    public function __construct(protected PageSectionImageService $imageService) {}

    public function index()
    {
        $pageSections = PageSection::query()
            ->withCount('media')
            ->orderBy('page_key')
            ->orderBy('sort_order')
            ->orderBy('section_key')
            ->paginate(20);

        return view('backend.page-sections.index', compact('pageSections'));
    }

    public function edit(PageSection $pageSection)
    {
        $pageSection->load('media');
        $mediaSlots = HomepageSectionMedia::slotsFor($pageSection->section_key);
        $allowsGallery = HomepageSectionMedia::allowsGallery($pageSection->section_key);
        $usesLogo = HomepageSectionMedia::usesLogo($pageSection->section_key);

        return view('backend.page-sections.edit', compact(
            'pageSection',
            'mediaSlots',
            'allowsGallery',
            'usesLogo'
        ));
    }

    public function update(Request $request, PageSection $pageSection)
    {
        $mediaSlots = HomepageSectionMedia::slotsFor($pageSection->section_key);
        $allowsGallery = HomepageSectionMedia::allowsGallery($pageSection->section_key);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'slot_uploads' => ['nullable', 'array'],
            'slot_uploads.*.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'media_uploads' => ['nullable', 'array', 'max:' . PageSection::MEDIA_LIMIT],
            'media_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'extra_data' => ['nullable', 'json'],
            'animation' => ['nullable', 'string', 'in:' . implode(',', PageSection::ANIMATION_OPTIONS)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $uploadedMedia = Arr::wrap($request->file('media_uploads', []));
        $mediaCount = $pageSection->media()
            ->where('role', 'gallery')
            ->count();

        if (! $allowsGallery && count($uploadedMedia)) {
            return back()
                ->withErrors(['media_uploads' => 'Gallery upload is not enabled for this section.'])
                ->withInput();
        }

        if ($allowsGallery && $mediaCount + count($uploadedMedia) > PageSection::MEDIA_LIMIT) {
            return back()
                ->withErrors(['media_uploads' => 'Maximum ' . PageSection::MEDIA_LIMIT . ' gallery images are allowed for each section.'])
                ->withInput();
        }

        $extraData = json_decode($validated['extra_data'] ?? '[]', true) ?: [];

        if ($request->filled('animation')) {
            $extraData['animation'] = $request->input('animation');
        }

        $data = [
            'label' => $validated['label'] ?? null,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'image' => $validated['image_path'] ?? $pageSection->image,
            'mobile_image' => $validated['mobile_image_path'] ?? $pageSection->mobile_image,
            'extra_data' => $extraData,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->storeUploadedImage($request->file('image'), $pageSection, $pageSection->image);
        }

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $this->imageService->storeUploadedImage($request->file('mobile_image'), $pageSection, $pageSection->mobile_image);
        }

        $pageSection->update($data);

        foreach ($mediaSlots as $slot) {
            $role = $slot['role'];
            $slotKey = $slot['slot_key'];

            if (! $request->hasFile("slot_uploads.$role.$slotKey")) {
                continue;
            }

            $this->imageService->storeSlotUpload(
                $request->file("slot_uploads.$role.$slotKey"),
                $pageSection,
                $role,
                $slotKey,
                $slot['label']
            );
        }

        if ($allowsGallery) {
            foreach ($uploadedMedia as $mediaFile) {
                $this->imageService->storeMediaUpload($mediaFile, $pageSection, $mediaCount++);
            }
        }

        return redirect()->route('admin.page-sections.edit', $pageSection)->with('success', 'Page section updated successfully.');
    }

    public function destroyMedia(PageSectionMedia $media)
    {
        $pageSection = $media->pageSection;
        $this->imageService->deleteMedia($media);

        return redirect()->route('admin.page-sections.edit', $pageSection)->with('success', 'Section image deleted successfully.');
    }
}
