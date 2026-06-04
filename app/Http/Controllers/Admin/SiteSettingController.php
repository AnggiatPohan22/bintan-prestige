<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteAsset;
use App\Services\PageSectionImageService;
use App\Support\HomepageSectionMedia;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function __construct(protected PageSectionImageService $imageService) {}

    public function edit()
    {
        $activeTab = request('tab', 'site-logo');

        if (! in_array($activeTab, ['site-logo', 'favicon'], true)) {
            $activeTab = 'site-logo';
        }

        $assetTabs = $this->assetTabs();
        $logoVariants = $this->logoVariants();
        $siteAssets = SiteAsset::query()
            ->whereIn('key', [
                ...array_column($logoVariants, 'key'),
                $this->faviconConfig()['key'],
            ])
            ->get()
            ->keyBy('key');
        $siteLogos = $siteAssets->only(array_column($logoVariants, 'key'));
        $favicon = $siteAssets[$this->faviconConfig()['key']] ?? null;
        $faviconConfig = $this->faviconConfig();

        return view('backend.settings.global-assets', compact('activeTab', 'assetTabs', 'logoVariants', 'siteLogos', 'favicon', 'faviconConfig'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'logos' => ['nullable', 'array'],
            'logos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logo_alts' => ['nullable', 'array'],
            'logo_alts.*' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->logoVariants() as $variant) {
            $slug = $variant['slug'];
            $alt = $validated['logo_alts'][$slug] ?? null;

            if ($request->hasFile("logos.$slug")) {
                $this->imageService->storeSiteAssetUpload(
                    $request->file("logos.$slug"),
                    $variant['key'],
                    $variant['label'],
                    $alt
                );

                continue;
            }

            if ($alt !== null) {
                SiteAsset::query()
                    ->where('key', $variant['key'])
                    ->update(['alt' => $alt]);
            }
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'site-logo'])
            ->with('success', 'Logo variants updated successfully.');
    }

    public function destroyLogo(string $variant)
    {
        $variantConfig = collect($this->logoVariants())->firstWhere('slug', $variant);

        abort_unless($variantConfig, 404);

        $asset = SiteAsset::query()
            ->where('key', $variantConfig['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'site-logo'])
            ->with('success', $variantConfig['label'] . ' deleted successfully.');
    }

    public function updateFavicon(Request $request)
    {
        $validated = $request->validate([
            'favicon' => ['required', 'file', 'mimes:ico,png,svg,webp,jpg,jpeg', 'max:1024'],
            'favicon_alt' => ['nullable', 'string', 'max:255'],
        ]);

        $favicon = $this->faviconConfig();

        $this->imageService->storeSiteAssetUpload(
            $request->file('favicon'),
            $favicon['key'],
            $favicon['label'],
            $validated['favicon_alt'] ?? null
        );

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'favicon'])
            ->with('success', 'Browser favicon updated successfully.');
    }

    public function destroyFavicon()
    {
        $asset = SiteAsset::query()
            ->where('key', $this->faviconConfig()['key'])
            ->first();

        if ($asset) {
            $this->imageService->clearSiteAsset($asset);
        }

        return redirect()
            ->route('admin.settings.global-assets.edit', ['tab' => 'favicon'])
            ->with('success', 'Browser favicon deleted successfully.');
    }

    private function assetTabs(): array
    {
        return [
            [
                'key' => 'site-logo',
                'label' => 'Site Logo',
                'description' => 'Logo variants for header, footer, sections, and compact placements.',
            ],
            [
                'key' => 'favicon',
                'label' => 'Browser Favicon',
                'description' => 'Browser tab, bookmark, and shortcut icon.',
            ],
        ];
    }

    private function logoVariants(): array
    {
        return [
            [
                'slug' => 'main',
                'key' => HomepageSectionMedia::SITE_LOGO_KEY,
                'label' => 'Main website logo',
                'hint' => 'Default logo used when no specific variant is available.',
            ],
            [
                'slug' => 'dark',
                'key' => 'site.logo.dark',
                'label' => 'Dark logo',
                'hint' => 'Logo for light backgrounds, such as the main header.',
            ],
            [
                'slug' => 'light',
                'key' => 'site.logo.light',
                'label' => 'Light logo',
                'hint' => 'Logo for dark backgrounds, such as the footer.',
            ],
            [
                'slug' => 'icon',
                'key' => 'site.logo.icon',
                'label' => 'Icon logo',
                'hint' => 'Compact mark for small spaces, favicon, or future mobile UI.',
            ],
        ];
    }

    private function faviconConfig(): array
    {
        return [
            'key' => 'site.favicon',
            'label' => 'Browser favicon',
            'hint' => 'Icon shown in browser tabs, bookmarks, and shortcut previews.',
        ];
    }
}
