<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Models\PageSectionMedia;
use App\Services\PageSectionImageService;
use App\Support\HomepageSectionMedia;
use App\Support\PageSectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PageSectionController extends Controller
{
    public function __construct(protected PageSectionImageService $imageService) {}

    public function index(Request $request)
    {
        PageSectionRegistry::syncRegisteredSections();

        $pageKeys = $this->pageKeys();
        $pageOptions = PageSectionRegistry::pageOptions($pageKeys);
        $activePageKey = $request->query('page');

        if (! $pageKeys->contains($activePageKey)) {
            $activePageKey = null;
        }

        return view('backend.page-sections.index', compact(
            'pageKeys',
            'activePageKey',
            'pageOptions'
        ));
    }

    public function sections(Request $request)
    {
        PageSectionRegistry::syncRegisteredSections();

        $pageKeys = $this->pageKeys();
        $pageOptions = PageSectionRegistry::pageOptions($pageKeys);
        $pageKey = $request->query('page');
        $selectedPage = $pageOptions->firstWhere('key', $pageKey);

        if (! $pageKey || ! $selectedPage) {
            return redirect()
                ->route('admin.page-sections.index')
                ->with('warning', 'Please select a valid page before managing sections.');
        }

        $orderedKeys = array_values(array_unique([
            ...HomepageSectionMedia::orderedSectionKeys(),
            ...PageSectionRegistry::registeredSectionKeys(),
        ]));
        $orderSql = collect($orderedKeys)
            ->map(fn (string $key, int $index) => "WHEN ? THEN {$index}")
            ->implode(' ');

        $pageSections = PageSection::query()
            ->withCount('media')
            ->where('page_key', $pageKey)
            ->when($orderSql !== '', fn ($query) => $query->orderByRaw("CASE section_key {$orderSql} ELSE 9999 END", $orderedKeys))
            ->orderBy('sort_order')
            ->orderBy('section_key')
            ->paginate(20)
            ->withQueryString();

        return view('backend.page-sections.sections', compact(
            'pageSections',
            'pageKey',
            'selectedPage'
        ));
    }

    public function edit(PageSection $pageSection)
    {
        $pageSection->load('media');
        $mediaSlots = HomepageSectionMedia::slotsFor($pageSection->section_key);
        $allowsGallery = HomepageSectionMedia::allowsGallery($pageSection->section_key);
        $usesLogo = HomepageSectionMedia::usesLogo($pageSection->section_key);
        $supportsMediaDisplayOptions = $this->supportsMediaDisplayOptions();

        return view('backend.page-sections.edit', compact(
            'pageSection',
            'mediaSlots',
            'allowsGallery',
            'usesLogo',
            'supportsMediaDisplayOptions'
        ));
    }

    public function update(Request $request, PageSection $pageSection)
    {
        $mediaSlots = HomepageSectionMedia::slotsFor($pageSection->section_key);
        $allowsGallery = HomepageSectionMedia::allowsGallery($pageSection->section_key);
        $supportsMediaDisplayOptions = $this->supportsMediaDisplayOptions();

        $rules = [
            'label' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'slot_uploads' => ['nullable', 'array'],
            'slot_uploads.*.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'slot_paths' => ['nullable', 'array'],
            'slot_paths.*.*' => ['nullable', 'string', 'max:500'],
            'media_uploads' => ['nullable', 'array', 'max:' . PageSection::MEDIA_LIMIT],
            'media_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'media_paths' => ['nullable', 'array', 'max:' . PageSection::MEDIA_LIMIT],
            'media_paths.*' => ['string', 'max:500'],
            'extra_data' => ['nullable', 'json'],
            'animation' => ['nullable', 'string', 'in:' . implode(',', PageSection::ANIMATION_OPTIONS)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        if ($supportsMediaDisplayOptions) {
            $rules['slot_object_fits'] = ['nullable', 'array'];
            $rules['slot_object_fits.*.*'] = ['nullable', 'string', 'in:' . implode(',', array_keys(PageSectionMedia::OBJECT_FIT_OPTIONS))];
            $rules['slot_object_positions'] = ['nullable', 'array'];
            $rules['slot_object_positions.*.*'] = ['nullable', 'string', 'in:' . implode(',', array_keys(PageSectionMedia::OBJECT_POSITION_OPTIONS))];
        }

        $validated = $request->validate($rules);

        $mediaPaths = array_values(array_filter(
            Arr::wrap($validated['media_paths'] ?? []),
            fn ($path) => is_string($path) && trim($path) !== '',
        ));
        $mediaCount = $pageSection->media()
            ->where('role', 'gallery')
            ->count();

        if (! $allowsGallery && count($mediaPaths)) {
            return back()
                ->withErrors(['media_paths' => 'Gallery images are not enabled for this section.'])
                ->withInput();
        }

        if ($allowsGallery && $mediaCount + count($mediaPaths) > PageSection::MEDIA_LIMIT) {
            return back()
                ->withErrors(['media_paths' => 'Maximum ' . PageSection::MEDIA_LIMIT . ' gallery images are allowed for each section.'])
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
            $objectFit = $supportsMediaDisplayOptions ? ($validated['slot_object_fits'][$role][$slotKey] ?? null) : null;
            $objectPosition = $supportsMediaDisplayOptions ? ($validated['slot_object_positions'][$role][$slotKey] ?? null) : null;

            $slotPath = $validated['slot_paths'][$role][$slotKey] ?? null;

            if (! is_string($slotPath) || trim($slotPath) === '') {
                if ($supportsMediaDisplayOptions) {
                    $pageSection->media()
                        ->where('role', $role)
                        ->where('slot_key', $slotKey)
                        ->update([
                            'object_fit' => $objectFit ?: null,
                            'object_position' => $objectPosition ?: null,
                        ]);
                }

                continue;
            }

            $this->imageService->setSlotPath(
                $pageSection,
                $role,
                $slotKey,
                $slot['label'],
                trim($slotPath),
                $objectFit,
                $objectPosition
            );
        }

        if ($allowsGallery && $mediaPaths !== []) {
            $this->imageService->attachGalleryPaths($pageSection, $mediaPaths, $mediaCount);
        }

        return redirect()->route('admin.page-sections.edit', $pageSection)->with('success', 'Page section updated successfully.');
    }

    private function supportsMediaDisplayOptions(): bool
    {
        return Schema::hasColumn('page_section_media', 'object_fit')
            && Schema::hasColumn('page_section_media', 'object_position');
    }

    private function pageKeys()
    {
        return PageSection::query()
            ->select('page_key')
            ->distinct()
            ->orderBy('page_key')
            ->pluck('page_key')
            ->filter()
            ->values();
    }

    public function destroyMedia(PageSectionMedia $media)
    {
        $pageSection = $media->pageSection;
        $this->imageService->deleteMedia($media);

        return redirect()->route('admin.page-sections.edit', $pageSection)->with('success', 'Section image deleted successfully.');
    }
}
