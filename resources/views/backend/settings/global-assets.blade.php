@extends('layouts.admin')

@section('content')

@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
@endphp

<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Global Assets</h1>
        <p class="mt-1 text-sm text-slate-500">Manage assets that are shared by frontend header, footer, and homepage sections.</p>
    </div>

    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-4">
        @foreach($assetTabs as $tab)
            <a
                href="{{ route('admin.settings.global-assets.edit', ['tab' => $tab['key']]) }}"
                class="rounded-lg border px-4 py-3 text-sm font-semibold transition {{ $activeTab === $tab['key'] ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    @foreach($assetTabs as $tab)
        @if($activeTab === $tab['key'])
            <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-base font-bold text-slate-800">{{ $tab['label'] }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $tab['description'] }}</p>
            </div>
        @endif
    @endforeach

    <div class="grid grid-cols-1 gap-6">
        @if($activeTab === 'site-logo')
            <form method="POST" action="{{ route('admin.settings.global-assets.site-logo.update') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Site Logo Variants</h2>
                <p class="mt-1 text-sm text-slate-500">Manage the logo assets used across frontend header, footer, homepage sections, and future compact placements.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
                    @foreach($logoVariants as $variant)
                        @php
                            $asset = $siteLogos[$variant['key']] ?? null;
                            $slug = $variant['slug'];
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="md:w-44">
                                    @if($asset?->url)
                                        <img src="{{ $asset->url }}" alt="{{ $asset->alt }}" class="h-32 w-full rounded-lg border bg-white object-contain p-4">
                                    @else
                                        <div class="flex h-32 items-center justify-center rounded-lg border border-dashed bg-slate-50 text-xs font-bold uppercase text-slate-400">
                                            No logo
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold text-slate-800">{{ $variant['label'] }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $variant['hint'] }}</p>
                                    <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $variant['key'] }}</p>

                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <label class="form-label">Upload</label>
                                            <input type="file" name="logos[{{ $slug }}]" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                                            @error("logos.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="form-label">Alt text</label>
                                            <input type="text" name="logo_alts[{{ $slug }}]" value="{{ old("logo_alts.$slug", $asset?->alt) }}" class="{{ $inputClass }}" placeholder="{{ $variant['label'] }}">
                                            @error("logo_alts.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Logo Variants</button>
                </div>
            </form>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-800">Delete Logo Variant</h2>
                <p class="mt-1 text-sm text-slate-500">Deleting a variant clears its file and marks that asset inactive. Frontend will use the next fallback.</p>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($logoVariants as $variant)
                        @php
                            $asset = $siteLogos[$variant['key']] ?? null;
                        @endphp

                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="text-sm font-bold text-slate-800">{{ $variant['label'] }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $variant['key'] }}</div>

                            @if($asset?->url)
                                <form method="POST" action="{{ route('admin.settings.global-assets.site-logo.destroy', $variant['slug']) }}" class="mt-4">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this logo variant?')" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                </form>
                            @else
                                <div class="mt-4 rounded-lg border border-dashed bg-slate-50 px-4 py-3 text-center text-xs font-semibold text-slate-400">Not uploaded</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($activeTab === 'favicon')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <form method="POST" action="{{ route('admin.settings.global-assets.favicon.update') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Browser Favicon</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $faviconConfig['hint'] }}</p>
                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $faviconConfig['key'] }}</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">Upload favicon</label>
                        <input type="file" name="favicon" accept=".ico,image/png,image/svg+xml,image/webp,image/jpeg" class="{{ $inputClass }}">
                        <p class="mt-2 text-xs text-slate-400">Accepted formats: ICO, PNG, SVG, WEBP, JPG. Maximum size: 1 MB.</p>
                        @error('favicon') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Alt text</label>
                        <input type="text" name="favicon_alt" value="{{ old('favicon_alt', $favicon?->alt) }}" class="{{ $inputClass }}" placeholder="Bintan Prestige favicon">
                        @error('favicon_alt') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Favicon</button>
                </div>
            </form>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-800">Current Favicon</h2>

                @if($favicon?->url)
                    <div class="mt-4 rounded-xl border bg-slate-50 p-4">
                        <img src="{{ $favicon->url }}" alt="{{ $favicon->alt }}" class="h-28 w-full rounded-lg border bg-white object-contain p-4">
                        <div class="mt-3 text-xs text-slate-500">
                            <div><span class="font-semibold">Key:</span> {{ $favicon->key }}</div>
                            <div><span class="font-semibold">Alt:</span> {{ $favicon->alt ?: '-' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.global-assets.favicon.destroy') }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete the browser favicon?')" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete Favicon</button>
                    </form>
                @else
                    <div class="mt-4 flex h-28 items-center justify-center rounded-xl border border-dashed bg-slate-50 text-sm font-semibold text-slate-400">
                        No favicon uploaded
                    </div>
                @endif
            </div>
            </div>
        @endif

        @if($activeTab === 'brand-colors')
            <form method="POST" action="{{ route('admin.settings.global-assets.brand-colors.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Brand Colors</h2>
                <p class="mt-1 text-sm text-slate-500">These colors feed frontend CSS variables used by shared theme, buttons, and brand surfaces.</p>

                <div class="mt-5 space-y-5">
                    @foreach(collect($brandColorFields)->groupBy('group_label') as $groupLabel => $fields)
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <h3 class="text-base font-bold text-slate-800">{{ $groupLabel }}</h3>

                            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                @foreach($fields as $field)
                                    @php
                                        $value = old("brand_colors.{$field['slug']}", $brandColors[$field['slug']] ?? $field['default']);
                                    @endphp

                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="text-sm font-bold text-slate-800">{{ $field['label'] }}</h4>
                                                <p class="mt-1 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                                            </div>

                                            <span class="h-12 w-12 shrink-0 rounded-lg border border-slate-200" style="background: {{ $value }}"></span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-[72px_minmax(0,1fr)] gap-3">
                                            <input type="color" name="brand_colors[{{ $field['slug'] }}]" value="{{ $value }}" class="h-12 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                                            <input type="text" value="{{ $value }}" disabled class="{{ $inputClass }} bg-slate-100 font-mono uppercase">
                                        </div>

                                        @error("brand_colors.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Brand Colors</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'social-share-image')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <form method="POST" action="{{ route('admin.settings.global-assets.social-share-image.update') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    @csrf
                    @method('PUT')

                    <h2 class="text-lg font-bold text-slate-800">Default Social Share Image</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $socialShareConfig['hint'] }}</p>
                    <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $socialShareConfig['key'] }}</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="form-label">Upload image</label>
                            <input type="file" name="social_share_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                            <p class="mt-2 text-xs text-slate-400">Recommended size: 1200 x 630 px. Accepted formats: JPG, PNG, WEBP. Maximum size: 4 MB.</p>
                            @error('social_share_image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Alt / internal description</label>
                            <input type="text" name="social_share_image_alt" value="{{ old('social_share_image_alt', $socialShareImage?->alt) }}" class="{{ $inputClass }}" placeholder="Bintan Prestige default social share image">
                            @error('social_share_image_alt') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                        <button type="submit" class="btn-primary">Save Social Share Image</button>
                    </div>
                </form>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-lg font-bold text-slate-800">Current Image</h2>

                    @if($socialShareImage?->url)
                        <div class="mt-4 rounded-xl border bg-slate-50 p-4">
                            <img src="{{ $socialShareImage->url }}" alt="{{ $socialShareImage->alt }}" class="aspect-[1200/630] w-full rounded-lg border bg-white object-cover">
                            <div class="mt-3 text-xs text-slate-500">
                                <div><span class="font-semibold">Key:</span> {{ $socialShareImage->key }}</div>
                                <div><span class="font-semibold">Alt:</span> {{ $socialShareImage->alt ?: '-' }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.global-assets.social-share-image.destroy') }}" class="mt-4">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete the default social share image?')" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete Image</button>
                        </form>
                    @else
                        <div class="mt-4 flex aspect-[1200/630] items-center justify-center rounded-xl border border-dashed bg-slate-50 text-sm font-semibold text-slate-400">
                            No social share image uploaded
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
