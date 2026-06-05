@extends('layouts.admin')

@section('content')

@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
@endphp

<div class="min-w-0 rounded-xl bg-white p-6 shadow">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Global Assets</h1>
        <p class="mt-1 text-sm text-slate-500">Manage assets that are shared by frontend header, footer, and homepage sections.</p>
    </div>

    <div class="mb-6 max-w-full overflow-x-auto border-b border-slate-200 pb-4">
        <div class="flex min-w-max gap-2 whitespace-nowrap">
        @foreach($assetTabs as $tab)
            <a
                href="{{ route('admin.settings.global-assets.edit', ['tab' => $tab['key']]) }}"
                class="shrink-0 rounded-lg border px-4 py-3 text-sm font-semibold transition {{ $activeTab === $tab['key'] ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
        </div>
    </div>

    @foreach($assetTabs as $tab)
        @if($activeTab === $tab['key'])
            <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-base font-bold text-slate-800">{{ $tab['label'] }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $tab['description'] }}</p>
            </div>
        @endif
    @endforeach

    <div class="grid min-w-0 grid-cols-1 gap-6">
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

        @if($activeTab === 'business-identity')
            <form method="POST" action="{{ route('admin.settings.global-assets.business-identity.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Business Identity</h2>
                <p class="mt-1 text-sm text-slate-500">These values are reused by the frontend header, footer, default metadata, and future structured data.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($businessIdentityFields as $field)
                        @php
                            $value = old("business_identity.{$field['slug']}", $businessIdentity[$field['slug']] ?? $field['default']);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                            <label class="form-label">{{ $field['label'] }}</label>

                            @if($field['type'] === 'textarea')
                                <textarea name="business_identity[{{ $field['slug'] }}]" rows="4" class="{{ $inputClass }}">{{ $value }}</textarea>
                            @else
                                <input type="text" name="business_identity[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}">
                            @endif

                            <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @error("business_identity.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Business Identity</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'contact-information')
            <form method="POST" action="{{ route('admin.settings.global-assets.contact-information.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Contact Information</h2>
                <p class="mt-1 text-sm text-slate-500">These values are reused by footer information, WhatsApp CTAs, and future contact sections.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($contactInformationFields as $field)
                        @php
                            $value = old("contact_information.{$field['slug']}", $contactInformation[$field['slug']] ?? $field['default']);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                            <label class="form-label">{{ $field['label'] }}</label>

                            @if($field['type'] === 'textarea')
                                <textarea name="contact_information[{{ $field['slug'] }}]" rows="4" class="{{ $inputClass }}">{{ $value }}</textarea>
                            @else
                                <input type="{{ in_array($field['type'], ['email', 'url'], true) ? $field['type'] : 'text' }}" name="contact_information[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}">
                            @endif

                            <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @error("contact_information.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Contact Information</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'social-media-links')
            <form method="POST" action="{{ route('admin.settings.global-assets.social-media-links.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Social Media Links</h2>
                <p class="mt-1 text-sm text-slate-500">Only links with a URL will be rendered in the frontend footer and future menus.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($socialMediaLinkFields as $field)
                        @php
                            $value = old("social_media_links.{$field['slug']}", $socialMediaLinks[$field['slug']] ?? $field['default']);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <label class="form-label">{{ $field['label'] }} URL</label>
                            <input type="url" name="social_media_links[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}" placeholder="https://...">
                            <p class="mt-2 text-xs text-slate-500">Frontend icon label: {{ $field['abbr'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @error("social_media_links.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                @php
                    $customSocialLinks = old('custom_social_links', $socialMediaLinks['custom_links'] ?? []);
                    if (empty($customSocialLinks)) {
                        $customSocialLinks = [['label' => '', 'abbr' => '', 'url' => '']];
                    }
                @endphp

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Custom Social Links</h3>
                            <p class="mt-1 text-sm text-slate-500">Add extra platforms that are not listed above.</p>
                        </div>
                        <button type="button" data-add-social-link class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Link</button>
                    </div>

                    <div class="mt-4 space-y-3" data-social-links-list>
                        @foreach($customSocialLinks as $index => $customLink)
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[minmax(0,1fr)_120px_minmax(0,1.4fr)_auto]" data-social-link-row>
                                <div>
                                    <label class="form-label">Label</label>
                                    <input type="text" name="custom_social_links[{{ $index }}][label]" value="{{ $customLink['label'] ?? '' }}" class="{{ $inputClass }}" placeholder="Pinterest">
                                    @error("custom_social_links.$index.label") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Icon Label</label>
                                    <input type="text" name="custom_social_links[{{ $index }}][abbr]" value="{{ $customLink['abbr'] ?? '' }}" class="{{ $inputClass }}" placeholder="PT">
                                    @error("custom_social_links.$index.abbr") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">URL</label>
                                    <input type="url" name="custom_social_links[{{ $index }}][url]" value="{{ $customLink['url'] ?? '' }}" class="{{ $inputClass }}" placeholder="https://...">
                                    @error("custom_social_links.$index.url") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end">
                                    <button type="button" data-remove-social-link class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Social Media Links</button>
                </div>
            </form>

            <template data-social-link-template>
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[minmax(0,1fr)_120px_minmax(0,1.4fr)_auto]" data-social-link-row>
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" data-name="label" class="{{ $inputClass }}" placeholder="Pinterest">
                    </div>
                    <div>
                        <label class="form-label">Icon Label</label>
                        <input type="text" data-name="abbr" class="{{ $inputClass }}" placeholder="PT">
                    </div>
                    <div>
                        <label class="form-label">URL</label>
                        <input type="url" data-name="url" class="{{ $inputClass }}" placeholder="https://...">
                    </div>
                    <div class="flex items-end">
                        <button type="button" data-remove-social-link class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                    </div>
                </div>
            </template>

            <script>
                document.addEventListener('click', function (event) {
                    if (event.target.matches('[data-add-social-link]')) {
                        const list = document.querySelector('[data-social-links-list]');
                        const template = document.querySelector('[data-social-link-template]');
                        const index = list.querySelectorAll('[data-social-link-row]').length;
                        const row = template.content.firstElementChild.cloneNode(true);

                        row.querySelectorAll('[data-name]').forEach(function (input) {
                            input.name = `custom_social_links[${index}][${input.dataset.name}]`;
                            input.removeAttribute('data-name');
                        });

                        list.appendChild(row);
                    }

                    if (event.target.matches('[data-remove-social-link]')) {
                        const row = event.target.closest('[data-social-link-row]');
                        const list = document.querySelector('[data-social-links-list]');

                        if (list.querySelectorAll('[data-social-link-row]').length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input').forEach(function (input) {
                                input.value = '';
                            });
                        }
                    }
                });
            </script>
        @endif

        @if($activeTab === 'navigation-settings')
            <form method="POST" action="{{ route('admin.settings.global-assets.navigation-settings.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Header Navigation</h2>
                <p class="mt-1 text-sm text-slate-500">Manage public header menu items and the main header action without editing frontend Blade.</p>

                @php
                    $navigationBasicFields = collect($navigationFields)->reject(fn ($field) => $field['type'] === 'color');
                    $navigationColorFields = collect($navigationFields)->filter(fn ($field) => $field['type'] === 'color');
                @endphp

                <div class="mt-5 space-y-3" data-navigation-accordion>
                    <details class="rounded-xl border border-slate-200 bg-white" open>
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                            Header Settings
                        </summary>

                        <div class="grid grid-cols-1 gap-5 border-t border-slate-200 bg-slate-50 p-4 lg:grid-cols-2">
                    @foreach($navigationBasicFields as $field)
                        @php
                            $value = old("navigation_settings.{$field['slug']}", $navigationSettings[$field['slug']] ?? $field['default']);
                            $checkedValue = filter_var($value, FILTER_VALIDATE_BOOL);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4 {{ $field['type'] === 'boolean' ? 'lg:col-span-2' : '' }}">
                            @if($field['type'] === 'boolean')
                                <input type="hidden" name="navigation_settings[{{ $field['slug'] }}]" value="0">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="navigation_settings[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-800">{{ $field['label'] }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $field['hint'] }}</span>
                                        <span class="mt-2 block text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</span>
                                    </span>
                                </label>
                            @else
                                <label class="form-label">{{ $field['label'] }}</label>
                                <input type="text" name="navigation_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}" placeholder="{{ $field['default'] }}">
                                <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @endif

                            @error("navigation_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                        </div>
                    </details>

                    <details class="rounded-xl border border-slate-200 bg-white">
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                            Menu Colors
                        </summary>

                        <div class="border-t border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">Compact color controls for header menu text, hover, active, and dropdown states.</p>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($navigationColorFields as $field)
                            @php
                                $value = old("navigation_settings.{$field['slug']}", $navigationSettings[$field['slug']] ?? $field['default']);
                            @endphp

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <label class="text-sm font-bold text-slate-800">{{ $field['label'] }}</label>
                                        <p class="mt-1 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                    </div>
                                    <span class="h-10 w-10 shrink-0 rounded-lg border border-slate-200" style="background: {{ $value }}"></span>
                                </div>

                                <div class="mt-3 grid grid-cols-[56px_minmax(0,1fr)] gap-2">
                                    <input type="color" name="navigation_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="h-11 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                                    <input type="text" value="{{ $value }}" disabled class="{{ $inputClass }} bg-slate-100 py-2 font-mono uppercase">
                                </div>

                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                                @error("navigation_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                        </div>
                    </details>

                @php
                    $navigationItems = old('navigation_items', $navigationSettings['items'] ?? []);
                    if (empty($navigationItems)) {
                        $navigationItems = [['label' => '', 'url' => '', 'is_external' => false, 'children' => []]];
                    }
                @endphp

                    <details class="rounded-xl border border-slate-200 bg-white">
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                            Menu Items
                        </summary>

                        <div class="border-t border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Menu Items</h3>
                            <p class="mt-1 text-sm text-slate-500">Only rows with label and URL will render. Use Move Up or Move Down to reorder menus.</p>
                        </div>
                        <button type="button" data-add-navigation-item class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Menu</button>
                    </div>

                    <div class="mt-4 space-y-3" data-navigation-items-list>
                        @foreach($navigationItems as $index => $item)
                            @php
                                $children = $item['children'] ?? [];
                            @endphp

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4" data-navigation-item-row>
                                <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_220px]">
                                    <div>
                                        <label class="form-label">Label</label>
                                        <input type="text" name="navigation_items[{{ $index }}][label]" data-field="label" value="{{ $item['label'] ?? '' }}" class="{{ $inputClass }}" placeholder="Packages">
                                        @error("navigation_items.$index.label") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="form-label">URL</label>
                                        <input type="text" name="navigation_items[{{ $index }}][url]" data-field="url" value="{{ $item['url'] ?? '' }}" class="{{ $inputClass }}" placeholder="/products">
                                        @error("navigation_items.$index.url") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="navigation_items[{{ $index }}][is_external]" data-field="is_external" value="1" @checked((bool) ($item['is_external'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        Open in new tab
                                    </label>
                                    <div class="flex flex-wrap items-end gap-2">
                                        <button type="button" data-move-navigation-item="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white">Move Up</button>
                                        <button type="button" data-move-navigation-item="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white">Move Down</button>
                                        <button type="button" data-remove-navigation-item class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Dropdown Items</h4>
                                            <p class="mt-1 text-xs text-slate-500">Optional submenu shown under this menu item.</p>
                                        </div>
                                        <button type="button" data-add-navigation-child class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">Add Dropdown</button>
                                    </div>

                                    <div class="mt-3 space-y-3" data-navigation-children-list>
                                        @foreach($children as $childIndex => $child)
                                            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_auto]" data-navigation-child-row>
                                                <div>
                                                    <label class="form-label">Dropdown label</label>
                                                    <input type="text" name="navigation_items[{{ $index }}][children][{{ $childIndex }}][label]" data-child-field="label" value="{{ $child['label'] ?? '' }}" class="{{ $inputClass }}" placeholder="Private Trip">
                                                </div>
                                                <div>
                                                    <label class="form-label">Dropdown URL</label>
                                                    <input type="text" name="navigation_items[{{ $index }}][children][{{ $childIndex }}][url]" data-child-field="url" value="{{ $child['url'] ?? '' }}" class="{{ $inputClass }}" placeholder="/products/private-trip">
                                                </div>
                                                <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                                                    <input type="checkbox" name="navigation_items[{{ $index }}][children][{{ $childIndex }}][is_external]" data-child-field="is_external" value="1" @checked((bool) ($child['is_external'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                    New tab
                                                </label>
                                                <div class="flex items-end">
                                                    <button type="button" data-remove-navigation-child class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                        </div>
                    </details>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Header Navigation</button>
                </div>
            </form>

            <template data-navigation-item-template>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4" data-navigation-item-row>
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_220px]">
                        <div>
                            <label class="form-label">Label</label>
                            <input type="text" data-field="label" class="{{ $inputClass }}" placeholder="Packages">
                        </div>
                        <div>
                            <label class="form-label">URL</label>
                            <input type="text" data-field="url" class="{{ $inputClass }}" placeholder="/products">
                        </div>
                        <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                            <input type="checkbox" data-field="is_external" value="1" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Open in new tab
                        </label>
                        <div class="flex flex-wrap items-end gap-2">
                            <button type="button" data-move-navigation-item="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white">Move Up</button>
                            <button type="button" data-move-navigation-item="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white">Move Down</button>
                            <button type="button" data-remove-navigation-item class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">Dropdown Items</h4>
                                <p class="mt-1 text-xs text-slate-500">Optional submenu shown under this menu item.</p>
                            </div>
                            <button type="button" data-add-navigation-child class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">Add Dropdown</button>
                        </div>
                        <div class="mt-3 space-y-3" data-navigation-children-list></div>
                    </div>
                </div>
            </template>

            <template data-navigation-child-template>
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_auto]" data-navigation-child-row>
                    <div>
                        <label class="form-label">Dropdown label</label>
                        <input type="text" data-child-field="label" class="{{ $inputClass }}" placeholder="Private Trip">
                    </div>
                    <div>
                        <label class="form-label">Dropdown URL</label>
                        <input type="text" data-child-field="url" class="{{ $inputClass }}" placeholder="/products/private-trip">
                    </div>
                    <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                        <input type="checkbox" data-child-field="is_external" value="1" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        New tab
                    </label>
                    <div class="flex items-end">
                        <button type="button" data-remove-navigation-child class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                    </div>
                </div>
            </template>

            <script>
                (function () {
                    const listSelector = '[data-navigation-items-list]';
                    const accordionSelector = '[data-navigation-accordion] details';

                    document.querySelectorAll(accordionSelector).forEach(function (accordion) {
                        accordion.addEventListener('toggle', function () {
                            if (!accordion.open) {
                                return;
                            }

                            document.querySelectorAll(accordionSelector).forEach(function (sibling) {
                                if (sibling !== accordion) {
                                    sibling.open = false;
                                }
                            });
                        });
                    });

                    function reindexNavigationItems() {
                        const list = document.querySelector('[data-navigation-items-list]');
                        if (!list) {
                            return;
                        }

                        list.querySelectorAll('[data-navigation-item-row]').forEach(function (row, index) {
                            row.querySelectorAll('[data-field]').forEach(function (input) {
                                input.name = `navigation_items[${index}][${input.dataset.field}]`;
                            });

                            row.querySelectorAll('[data-navigation-child-row]').forEach(function (childRow, childIndex) {
                                childRow.querySelectorAll('[data-child-field]').forEach(function (input) {
                                    input.name = `navigation_items[${index}][children][${childIndex}][${input.dataset.childField}]`;
                                });
                            });
                        });
                    }

                    document.addEventListener('click', function (event) {
                        if (event.target.matches('[data-add-navigation-item]')) {
                            const list = document.querySelector(listSelector);
                            const template = document.querySelector('[data-navigation-item-template]');
                            const row = template.content.firstElementChild.cloneNode(true);

                            list.appendChild(row);
                            reindexNavigationItems();
                        }

                        if (event.target.matches('[data-add-navigation-child]')) {
                            const row = event.target.closest('[data-navigation-item-row]');
                            const childrenList = row.querySelector('[data-navigation-children-list]');
                            const template = document.querySelector('[data-navigation-child-template]');
                            const child = template.content.firstElementChild.cloneNode(true);

                            childrenList.appendChild(child);
                            reindexNavigationItems();
                        }

                        if (event.target.matches('[data-move-navigation-item]')) {
                            const row = event.target.closest('[data-navigation-item-row]');
                            const direction = event.target.dataset.moveNavigationItem;

                            if (direction === 'up' && row.previousElementSibling) {
                                row.parentNode.insertBefore(row, row.previousElementSibling);
                            }

                            if (direction === 'down' && row.nextElementSibling) {
                                row.parentNode.insertBefore(row.nextElementSibling, row);
                            }

                            reindexNavigationItems();
                        }

                        if (event.target.matches('[data-remove-navigation-child]')) {
                            event.target.closest('[data-navigation-child-row]').remove();
                            reindexNavigationItems();
                        }

                        if (event.target.matches('[data-remove-navigation-item]')) {
                            const row = event.target.closest('[data-navigation-item-row]');
                            const list = document.querySelector(listSelector);

                            if (list.querySelectorAll('[data-navigation-item-row]').length > 1) {
                                row.remove();
                            } else {
                                row.querySelectorAll('input').forEach(function (input) {
                                    if (input.type === 'checkbox') {
                                        input.checked = false;
                                    } else {
                                        input.value = '';
                                    }
                                });
                                row.querySelector('[data-navigation-children-list]').innerHTML = '';
                            }

                            reindexNavigationItems();
                        }
                    });

                    reindexNavigationItems();
                })();
            </script>
        @endif

        @if($activeTab === 'footer-settings')
            <form method="POST" action="{{ route('admin.settings.global-assets.footer-settings.update') }}" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">Footer Settings</h2>
                <p class="mt-1 text-sm text-slate-500">Manage footer-specific display settings and menus while contact and social data stay global.</p>

                <div class="mt-5 space-y-3" data-footer-accordion>
                    <details class="rounded-xl border border-slate-200 bg-white" open>
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                            Footer Display
                        </summary>

                        <div class="grid grid-cols-1 gap-5 border-t border-slate-200 bg-slate-50 p-4 lg:grid-cols-2">
                            @foreach($footerFields as $field)
                                @php
                                    $value = old("footer_settings.{$field['slug']}", $footerSettings[$field['slug']] ?? $field['default']);
                                    $checkedValue = filter_var($value, FILTER_VALIDATE_BOOL);
                                @endphp

                                <div class="rounded-xl border border-slate-200 bg-white p-4 {{ $field['type'] === 'boolean' ? '' : 'lg:col-span-2' }}">
                                    @if($field['type'] === 'boolean')
                                        <input type="hidden" name="footer_settings[{{ $field['slug'] }}]" value="0">
                                        <label class="flex items-start gap-3">
                                            <input type="checkbox" name="footer_settings[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <span>
                                                <span class="block text-sm font-bold text-slate-800">{{ $field['label'] }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ $field['hint'] }}</span>
                                                <span class="mt-2 block text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</span>
                                            </span>
                                        </label>
                                    @elseif($field['type'] === 'select')
                                        <label class="form-label">{{ $field['label'] }}</label>
                                        <select name="footer_settings[{{ $field['slug'] }}]" class="{{ $inputClass }}">
                                            @foreach($field['options'] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                        <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                                    @else
                                        <label class="form-label">{{ $field['label'] }}</label>
                                        <input type="text" name="footer_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}" placeholder="{{ $field['default'] }}">
                                        <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                        <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                                    @endif

                                    @error("footer_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </details>

                    @php
                        $footerQuickLinks = old('footer_quick_links', $footerSettings['quick_links'] ?? []);
                        $footerUtilityLinks = old('footer_utility_links', $footerSettings['utility_links'] ?? []);
                        $footerLayoutBlocks = old('footer_layout_blocks', $footerSettings['layout_blocks'] ?? []);
                        $footerBlockTypes = \App\Support\FooterSettings::blockTypes();
                        $footerWidthOptions = \App\Support\FooterSettings::widthOptions();
                    @endphp

                    <details class="rounded-xl border border-slate-200 bg-white">
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                            Layout Blocks
                        </summary>

                        <div class="border-t border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-slate-500">Arrange footer columns, choose block width, and enable maps or custom text blocks.</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500" data-footer-layout-status>Active layout capacity: 0 / 3 columns.</p>
                                    @error('footer_layout_blocks') <p class="form-error mt-2">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" data-add-footer-block class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Block</button>
                            </div>

                            <div class="mt-4 space-y-3" data-footer-blocks-list>
                                @foreach($footerLayoutBlocks as $index => $block)
                                    <div class="rounded-xl border border-slate-200 bg-white p-4" data-footer-block-row>
                                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_140px_220px]">
                                            <div>
                                                <label class="form-label">Type</label>
                                                <select name="footer_layout_blocks[{{ $index }}][type]" data-field="type" class="{{ $inputClass }}">
                                                    @foreach($footerBlockTypes as $typeValue => $typeLabel)
                                                        <option value="{{ $typeValue }}" @selected(($block['type'] ?? '') === $typeValue)>{{ $typeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Title</label>
                                                <input type="text" name="footer_layout_blocks[{{ $index }}][title]" data-field="title" value="{{ $block['title'] ?? '' }}" class="{{ $inputClass }}" placeholder="Quick Links">
                                            </div>
                                            <div>
                                                <label class="form-label">Width</label>
                                                <select name="footer_layout_blocks[{{ $index }}][width]" data-field="width" data-footer-layout-control class="{{ $inputClass }}">
                                                    @foreach($footerWidthOptions as $widthValue => $widthLabel)
                                                        <option value="{{ $widthValue }}" @selected(($block['width'] ?? '1') === $widthValue)>{{ $widthLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                                                <input type="checkbox" name="footer_layout_blocks[{{ $index }}][is_active]" data-field="is_active" data-footer-layout-control value="1" @checked((bool) ($block['is_active'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                Active
                                            </label>
                                            <div class="flex flex-wrap items-end gap-2">
                                                <button type="button" data-move-footer-block="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Up</button>
                                                <button type="button" data-move-footer-block="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Down</button>
                                                <button type="button" data-remove-footer-block class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                                            <div>
                                                <label class="form-label">Maps embed URL</label>
                                                <input type="text" name="footer_layout_blocks[{{ $index }}][settings][maps_embed_url]" data-setting-field="maps_embed_url" value="{{ $block['settings']['maps_embed_url'] ?? '' }}" class="{{ $inputClass }}" placeholder="https://www.google.com/maps/embed?...">
                                            </div>
                                            <div>
                                                <label class="form-label">Custom text / ads</label>
                                                <textarea name="footer_layout_blocks[{{ $index }}][settings][custom_body]" data-setting-field="custom_body" rows="2" class="{{ $inputClass }}" placeholder="Short support text or ads copy">{{ $block['settings']['custom_body'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </details>

                    @foreach([
                        ['key' => 'quick', 'title' => 'Quick Links', 'description' => 'Primary footer menu links.', 'list' => $footerQuickLinks, 'input' => 'footer_quick_links'],
                        ['key' => 'utility', 'title' => 'Utility Links', 'description' => 'Secondary footer links such as policy, FAQ, and blog.', 'list' => $footerUtilityLinks, 'input' => 'footer_utility_links'],
                    ] as $menuGroup)
                        <details class="rounded-xl border border-slate-200 bg-white">
                            <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-slate-800 transition hover:bg-slate-50">
                                {{ $menuGroup['title'] }}
                            </summary>

                            <div class="border-t border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-sm text-slate-500">{{ $menuGroup['description'] }}</p>
                                    <button type="button" data-add-footer-link="{{ $menuGroup['key'] }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Link</button>
                                </div>

                                <div class="mt-4 space-y-3" data-footer-links-list="{{ $menuGroup['key'] }}">
                                    @foreach($menuGroup['list'] as $index => $link)
                                        <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_220px]" data-footer-link-row>
                                            <div>
                                                <label class="form-label">Label</label>
                                                <input type="text" name="{{ $menuGroup['input'] }}[{{ $index }}][label]" data-field="label" value="{{ $link['label'] ?? '' }}" class="{{ $inputClass }}" placeholder="Packages">
                                            </div>
                                            <div>
                                                <label class="form-label">URL</label>
                                                <input type="text" name="{{ $menuGroup['input'] }}[{{ $index }}][url]" data-field="url" value="{{ $link['url'] ?? '' }}" class="{{ $inputClass }}" placeholder="/products">
                                            </div>
                                            <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                                                <input type="checkbox" name="{{ $menuGroup['input'] }}[{{ $index }}][is_external]" data-field="is_external" value="1" @checked((bool) ($link['is_external'] ?? false)) class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                Open in new tab
                                            </label>
                                            <div class="flex flex-wrap items-end gap-2">
                                                <button type="button" data-move-footer-link="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Up</button>
                                                <button type="button" data-move-footer-link="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Down</button>
                                                <button type="button" data-remove-footer-link class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save Footer Settings</button>
                </div>
            </form>

            <template data-footer-link-template>
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_150px_220px]" data-footer-link-row>
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" data-field="label" class="{{ $inputClass }}" placeholder="Packages">
                    </div>
                    <div>
                        <label class="form-label">URL</label>
                        <input type="text" data-field="url" class="{{ $inputClass }}" placeholder="/products">
                    </div>
                    <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                        <input type="checkbox" data-field="is_external" value="1" class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Open in new tab
                    </label>
                    <div class="flex flex-wrap items-end gap-2">
                        <button type="button" data-move-footer-link="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Up</button>
                        <button type="button" data-move-footer-link="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Down</button>
                        <button type="button" data-remove-footer-link class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                    </div>
                </div>
            </template>

            <template data-footer-block-template>
                <div class="rounded-xl border border-slate-200 bg-white p-4" data-footer-block-row>
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_140px_220px]">
                        <div>
                            <label class="form-label">Type</label>
                            <select data-field="type" class="{{ $inputClass }}">
                                @foreach(\App\Support\FooterSettings::blockTypes() as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Title</label>
                            <input type="text" data-field="title" class="{{ $inputClass }}" placeholder="Quick Links">
                        </div>
                        <div>
                            <label class="form-label">Width</label>
                            <select data-field="width" data-footer-layout-control class="{{ $inputClass }}">
                                @foreach(\App\Support\FooterSettings::widthOptions() as $widthValue => $widthLabel)
                                    <option value="{{ $widthValue }}">{{ $widthLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-slate-700">
                            <input type="checkbox" data-field="is_active" data-footer-layout-control value="1" checked class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Active
                        </label>
                        <div class="flex flex-wrap items-end gap-2">
                            <button type="button" data-move-footer-block="up" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Up</button>
                            <button type="button" data-move-footer-block="down" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Move Down</button>
                            <button type="button" data-remove-footer-block class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div>
                            <label class="form-label">Maps embed URL</label>
                            <input type="text" data-setting-field="maps_embed_url" class="{{ $inputClass }}" placeholder="https://www.google.com/maps/embed?...">
                        </div>
                        <div>
                            <label class="form-label">Custom text / ads</label>
                            <textarea data-setting-field="custom_body" rows="2" class="{{ $inputClass }}" placeholder="Short support text or ads copy"></textarea>
                        </div>
                    </div>
                </div>
            </template>

            <script>
                (function () {
                    const inputNames = {
                        quick: 'footer_quick_links',
                        utility: 'footer_utility_links',
                    };

                    function reindexFooterBlocks() {
                        const list = document.querySelector('[data-footer-blocks-list]');

                        if (!list) {
                            return;
                        }

                        list.querySelectorAll('[data-footer-block-row]').forEach(function (row, index) {
                            row.querySelectorAll('[data-field]').forEach(function (input) {
                                input.name = `footer_layout_blocks[${index}][${input.dataset.field}]`;
                            });

                            row.querySelectorAll('[data-setting-field]').forEach(function (input) {
                                input.name = `footer_layout_blocks[${index}][settings][${input.dataset.settingField}]`;
                            });
                        });

                        syncFooterLayoutCapacity();
                    }

                    function footerBlockWidthValue(row) {
                        const width = row.querySelector('[data-field="width"]')?.value || '1';

                        if (width === 'full') {
                            return 3;
                        }

                        return Number.parseInt(width, 10) || 1;
                    }

                    function syncFooterLayoutCapacity() {
                        const form = document.querySelector('form[action*="footer-settings"]');
                        const status = document.querySelector('[data-footer-layout-status]');
                        const submitButton = form?.querySelector('button[type="submit"]');
                        const activeWidth = Array.from(document.querySelectorAll('[data-footer-block-row]'))
                            .filter(function (row) {
                                return row.querySelector('[data-field="is_active"]')?.checked;
                            })
                            .reduce(function (total, row) {
                                return total + footerBlockWidthValue(row);
                            }, 0);
                        const isOverCapacity = activeWidth > 3;

                        if (status) {
                            status.textContent = `Active layout capacity: ${activeWidth} / 3 columns.`;
                            status.classList.toggle('text-red-600', isOverCapacity);
                            status.classList.toggle('text-slate-500', !isOverCapacity);
                        }

                        if (submitButton) {
                            submitButton.disabled = isOverCapacity;
                            submitButton.classList.toggle('opacity-50', isOverCapacity);
                            submitButton.classList.toggle('cursor-not-allowed', isOverCapacity);
                        }
                    }

                    document.querySelectorAll('[data-footer-accordion] details').forEach(function (accordion) {
                        accordion.addEventListener('toggle', function () {
                            if (!accordion.open) {
                                return;
                            }

                            document.querySelectorAll('[data-footer-accordion] details').forEach(function (sibling) {
                                if (sibling !== accordion) {
                                    sibling.open = false;
                                }
                            });
                        });
                    });

                    function reindexFooterLinks(group) {
                        const list = document.querySelector(`[data-footer-links-list="${group}"]`);

                        if (!list) {
                            return;
                        }

                        list.querySelectorAll('[data-footer-link-row]').forEach(function (row, index) {
                            row.querySelectorAll('[data-field]').forEach(function (input) {
                                input.name = `${inputNames[group]}[${index}][${input.dataset.field}]`;
                            });
                        });
                    }

                    document.addEventListener('click', function (event) {
                        if (event.target.matches('[data-add-footer-block]')) {
                            const list = document.querySelector('[data-footer-blocks-list]');
                            const template = document.querySelector('[data-footer-block-template]');
                            const row = template.content.firstElementChild.cloneNode(true);

                            list.appendChild(row);
                            reindexFooterBlocks();
                        }

                        if (event.target.matches('[data-move-footer-block]')) {
                            const row = event.target.closest('[data-footer-block-row]');
                            const direction = event.target.dataset.moveFooterBlock;

                            if (direction === 'up' && row.previousElementSibling) {
                                row.parentNode.insertBefore(row, row.previousElementSibling);
                            }

                            if (direction === 'down' && row.nextElementSibling) {
                                row.parentNode.insertBefore(row.nextElementSibling, row);
                            }

                            reindexFooterBlocks();
                        }

                        if (event.target.matches('[data-remove-footer-block]')) {
                            const row = event.target.closest('[data-footer-block-row]');
                            const list = document.querySelector('[data-footer-blocks-list]');

                            if (list.querySelectorAll('[data-footer-block-row]').length > 1) {
                                row.remove();
                            } else {
                                row.querySelectorAll('input, textarea').forEach(function (input) {
                                    if (input.type === 'checkbox') {
                                        input.checked = false;
                                    } else {
                                        input.value = '';
                                    }
                                });
                            }

                            reindexFooterBlocks();
                        }

                        if (event.target.matches('[data-add-footer-link]')) {
                            const group = event.target.dataset.addFooterLink;
                            const list = document.querySelector(`[data-footer-links-list="${group}"]`);
                            const template = document.querySelector('[data-footer-link-template]');
                            const row = template.content.firstElementChild.cloneNode(true);

                            list.appendChild(row);
                            reindexFooterLinks(group);
                        }

                        if (event.target.matches('[data-move-footer-link]')) {
                            const row = event.target.closest('[data-footer-link-row]');
                            const list = row.closest('[data-footer-links-list]');
                            const group = list.dataset.footerLinksList;
                            const direction = event.target.dataset.moveFooterLink;

                            if (direction === 'up' && row.previousElementSibling) {
                                row.parentNode.insertBefore(row, row.previousElementSibling);
                            }

                            if (direction === 'down' && row.nextElementSibling) {
                                row.parentNode.insertBefore(row.nextElementSibling, row);
                            }

                            reindexFooterLinks(group);
                        }

                        if (event.target.matches('[data-remove-footer-link]')) {
                            const row = event.target.closest('[data-footer-link-row]');
                            const list = row.closest('[data-footer-links-list]');
                            const group = list.dataset.footerLinksList;

                            if (list.querySelectorAll('[data-footer-link-row]').length > 1) {
                                row.remove();
                            } else {
                                row.querySelectorAll('input').forEach(function (input) {
                                    if (input.type === 'checkbox') {
                                        input.checked = false;
                                    } else {
                                        input.value = '';
                                    }
                                });
                            }

                            reindexFooterLinks(group);
                        }
                    });

                    document.addEventListener('change', function (event) {
                        if (event.target.matches('[data-footer-layout-control]')) {
                            syncFooterLayoutCapacity();
                        }
                    });

                    Object.keys(inputNames).forEach(reindexFooterLinks);
                    reindexFooterBlocks();
                })();
            </script>
        @endif

        @if($activeTab === 'seo-default')
            <form method="POST" action="{{ route('admin.settings.global-assets.seo-default.update') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-slate-800">SEO Default</h2>
                <p class="mt-1 text-sm text-slate-500">Global fallback SEO for pages and products that do not provide their own metadata.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($seoDefaultFields as $field)
                        @php
                            $value = old("seo_default.{$field['slug']}", $seoDefaultSettings[$field['slug']] ?? $field['default']);
                            $checkedValue = filter_var($value, FILTER_VALIDATE_BOOL);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4 {{ in_array($field['type'], ['textarea', 'boolean'], true) ? 'lg:col-span-2' : '' }}">
                            @if($field['type'] === 'boolean')
                                <input type="hidden" name="seo_default[{{ $field['slug'] }}]" value="0">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="seo_default[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-800">{{ $field['label'] }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $field['hint'] }}</span>
                                        <span class="mt-2 block text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</span>
                                    </span>
                                </label>
                            @elseif($field['type'] === 'select')
                                <label class="form-label">{{ $field['label'] }}</label>
                                <select name="seo_default[{{ $field['slug'] }}]" class="{{ $inputClass }}">
                                    @foreach($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @elseif($field['type'] === 'textarea')
                                <label class="form-label">{{ $field['label'] }}</label>
                                <textarea name="seo_default[{{ $field['slug'] }}]" rows="3" class="{{ $inputClass }}">{{ $value }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @else
                                <label class="form-label">{{ $field['label'] }}</label>
                                <input type="{{ $field['type'] === 'url' ? 'url' : 'text' }}" name="seo_default[{{ $field['slug'] }}]" value="{{ $value }}" class="{{ $inputClass }}" placeholder="{{ $field['default'] }}">
                                <p class="mt-2 text-xs text-slate-500">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ $field['key'] }}</p>
                            @endif

                            @error("seo_default.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <label class="form-label">Default OG image</label>
                        <input type="file" name="seo_default_og_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                        <p class="mt-2 text-xs text-slate-500">Fallback social preview image when a product or page does not provide one. Recommended size: 1200 x 630 px.</p>
                        <p class="mt-2 text-[11px] font-semibold uppercase text-slate-400">{{ \App\Support\SeoDefaultSettings::OG_IMAGE_KEY }}</p>
                        @error('seo_default_og_image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h3 class="text-base font-bold text-slate-800">Current OG Image</h3>

                        @if($seoDefaultOgImage?->url)
                            <img src="{{ $seoDefaultOgImage->url }}" alt="{{ $seoDefaultOgImage->alt }}" class="mt-4 aspect-[1200/630] w-full rounded-lg border bg-white object-cover">
                            <form method="POST" action="{{ route('admin.settings.global-assets.seo-default.og-image.destroy') }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete the default SEO OG image?')" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete OG Image</button>
                            </form>
                        @else
                            <div class="mt-4 flex aspect-[1200/630] items-center justify-center rounded-xl border border-dashed bg-slate-50 text-sm font-semibold text-slate-400">
                                No SEO OG image uploaded
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-slate-200 pt-5">
                    <button type="submit" class="btn-primary">Save SEO Default</button>
                </div>
            </form>
        @endif
    </div>
</div>

@endsection
