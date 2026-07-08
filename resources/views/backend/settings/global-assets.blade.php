@extends('layouts.admin')

@section('content')

<div class="min-w-0 rounded-xl bg-admin-card p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-admin-secondary">Global Assets</h1>
        <p class="mt-1 text-sm text-admin-secondary">Manage assets that are shared by frontend header, footer, and homepage sections.</p>
    </div>

    <div class="mb-6 max-w-full overflow-x-auto border-b border-admin pb-4">
        <div class="flex min-w-max gap-2 whitespace-nowrap">
        @foreach($assetTabs as $tab)
            <a
                href="{{ route('admin.settings.global-assets.edit', ['tab' => $tab['key']]) }}"
                class="shrink-0 rounded-lg border px-4 py-3 text-sm font-semibold transition {{ $activeTab === $tab['key'] ? 'border-violet-500 bg-violet-900/20 text-violet-300' : 'border-admin bg-admin-card text-admin-secondary hover:opacity-75' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
        </div>
    </div>

    @foreach($assetTabs as $tab)
        @if($activeTab === $tab['key'])
            <div class="mb-5 rounded-xl border border-admin bg-admin-card px-4 py-3">
                <h2 class="text-base font-bold text-admin-secondary">{{ $tab['label'] }}</h2>
                <p class="mt-1 text-sm text-admin-secondary">{{ $tab['description'] }}</p>
            </div>
        @endif
    @endforeach

    <div class="grid min-w-0 grid-cols-1 gap-6">
        @if($activeTab === 'site-logo')
            <form method="POST" action="{{ route('admin.settings.global-assets.site-logo.update') }}" enctype="multipart/form-data" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Site Logo Variants</h2>
                <p class="mt-1 text-sm text-admin-secondary">Manage the logo assets used across frontend header, footer, homepage sections, and future compact placements.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
                    @foreach($logoVariants as $variant)
                        @php
                            $asset = $siteLogos[$variant['key']] ?? null;
                            $slug = $variant['slug'];
                        @endphp

                        <div class="rounded-xl border border-admin bg-admin-card p-4">
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="md:w-44">
                                    @if($asset?->url)
                                        <img src="{{ $asset->url }}" alt="{{ $asset->alt }}" class="h-32 w-full rounded-lg border bg-admin-card object-contain p-4">
                                    @else
                                        <div class="flex h-32 items-center justify-center rounded-lg border border-dashed bg-admin-card text-xs font-bold uppercase text-admin-secondary">
                                            No logo
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold text-admin-secondary">{{ $variant['label'] }}</h3>
                                    <p class="mt-1 text-xs text-admin-secondary">{{ $variant['hint'] }}</p>
                                    <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $variant['key'] }}</p>

                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <x-admin.media-image-field
                                                name="logos[{{ $slug }}]"
                                                :value="old('logos.'.$slug, $asset?->path)"
                                                label="Logo image"
                                                collection="logo"
                                                hint="Pick from the Media Library or upload — saved to the Logo collection." />
                                            @error("logos.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="form-label">Alt text</label>
                                            <input type="text" name="logo_alts[{{ $slug }}]" value="{{ old("logo_alts.$slug", $asset?->alt) }}" class="admin-input" placeholder="{{ $variant['label'] }}">
                                            @error("logo_alts.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Logo Variants</button>
                </div>
            </form>

            <div class="rounded-xl border border-admin bg-admin-card p-5">
                <h2 class="text-lg font-bold text-admin-secondary">Delete Logo Variant</h2>
                <p class="mt-1 text-sm text-admin-secondary">Deleting a variant clears its file and marks that asset inactive. Frontend will use the next fallback.</p>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($logoVariants as $variant)
                        @php $asset = $siteLogos[$variant['key']] ?? null; @endphp
                        <div class="rounded-xl border border-admin bg-admin-card p-4">
                            <div class="text-sm font-bold text-admin-secondary">{{ $variant['label'] }}</div>
                            <div class="mt-1 text-xs text-admin-secondary">{{ $variant['key'] }}</div>

                            @if($asset?->url)
                                <form method="POST" action="{{ route('admin.settings.global-assets.site-logo.destroy', $variant['slug']) }}" class="mt-4">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-confirm="Delete this logo variant?" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                </form>
                            @else
                                <div class="mt-4 rounded-lg border border-dashed bg-admin-card px-4 py-3 text-center text-xs font-semibold text-admin-secondary">Not uploaded</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($activeTab === 'favicon')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <form method="POST" action="{{ route('admin.settings.global-assets.favicon.update') }}" enctype="multipart/form-data" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Browser Favicon</h2>
                <p class="mt-1 text-sm text-admin-secondary">{{ $faviconConfig['hint'] }}</p>
                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $faviconConfig['key'] }}</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">Upload favicon</label>
                        <input type="file" name="favicon" accept=".ico,image/png,image/svg+xml,image/webp,image/jpeg" class="admin-input">
                        <p class="mt-2 text-xs text-admin-secondary">Accepted formats: ICO, PNG, SVG, WEBP, JPG. Maximum size: 1 MB.</p>
                        @error('favicon') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">Alt text</label>
                        <input type="text" name="favicon_alt" value="{{ old('favicon_alt', $favicon?->alt) }}" class="admin-input" placeholder="Bintan Prestige favicon">
                        @error('favicon_alt') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Favicon</button>
                </div>
            </form>

            <div class="rounded-xl border border-admin bg-admin-card p-5">
                <h2 class="text-lg font-bold text-admin-secondary">Current Favicon</h2>

                @if($favicon?->url)
                    <div class="mt-4 rounded-xl border bg-admin-card p-4">
                        <img src="{{ $favicon->url }}" alt="{{ $favicon->alt }}" class="h-28 w-full rounded-lg border bg-admin-card object-contain p-4">
                        <div class="mt-3 text-xs text-admin-secondary">
                            <div><span class="font-semibold">Key:</span> {{ $favicon->key }}</div>
                            <div><span class="font-semibold">Alt:</span> {{ $favicon->alt ?: '-' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.global-assets.favicon.destroy') }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm="Delete the browser favicon?" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete Favicon</button>
                    </form>
                @else
                    <div class="mt-4 flex h-28 items-center justify-center rounded-xl border border-dashed bg-admin-card text-sm font-semibold text-admin-secondary">
                        No favicon uploaded
                    </div>
                @endif
            </div>
            </div>
        @endif

        @if($activeTab === 'brand-colors')
            <form method="POST" action="{{ route('admin.settings.global-assets.brand-colors.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Brand Colors</h2>
                <p class="mt-1 text-sm text-admin-secondary">These colors feed frontend CSS variables used by shared theme, buttons, and brand surfaces.</p>

                <div class="mt-5 space-y-5">
                    @foreach(collect($brandColorFields)->groupBy('group_label') as $groupLabel => $fields)
                        <div class="rounded-xl border border-admin bg-admin-card p-4">
                            <h3 class="text-base font-bold text-admin-secondary">{{ $groupLabel }}</h3>

                            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                @foreach($fields as $field)
                                    @php $value = $brandColors[$field['slug']] ?? $field['default']; @endphp
                                    <div class="rounded-xl border border-admin bg-admin-card p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="text-sm font-bold text-admin-secondary">{{ $field['label'] }}</h4>
                                                <p class="mt-1 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                            </div>

                                            <span class="h-12 w-12 shrink-0 rounded-lg border border-admin" style="background: {{ $value }}"></span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-[72px_minmax(0,1fr)] gap-3">
                                            <input type="color" name="brand_colors[{{ $field['slug'] }}]" value="{{ $value }}" class="h-12 w-full cursor-pointer rounded-lg border border-admin bg-admin-card p-1">
                                            <input type="text" value="{{ $value }}" disabled class="admin-input bg-admin-card font-mono uppercase">
                                        </div>

                                        @error("brand_colors.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Brand Colors</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'social-share-image')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <form method="POST" action="{{ route('admin.settings.global-assets.social-share-image.update') }}" enctype="multipart/form-data" class="rounded-xl border border-admin bg-admin-card p-5">
                    @csrf
                    @method('PUT')

                    <h2 class="text-lg font-bold text-admin-secondary">Default Social Share Image</h2>
                    <p class="mt-1 text-sm text-admin-secondary">{{ $socialShareConfig['hint'] }}</p>
                    <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $socialShareConfig['key'] }}</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <x-admin.media-image-field
                                name="social_share_image"
                                :value="old('social_share_image', $socialShareImage?->path)"
                                label="Social share image"
                                collection="content"
                                hint="Recommended 1200 x 630 px. Pick from the Media Library or upload." />
                            @error('social_share_image') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Alt / internal description</label>
                            <input type="text" name="social_share_image_alt" value="{{ old('social_share_image_alt', $socialShareImage?->alt) }}" class="admin-input" placeholder="Bintan Prestige default social share image">
                            @error('social_share_image_alt') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                        <button type="submit" class="admin-btn-primary">Save Social Share Image</button>
                    </div>
                </form>

                <div class="rounded-xl border border-admin bg-admin-card p-5">
                    <h2 class="text-lg font-bold text-admin-secondary">Current Image</h2>

                    @if($socialShareImage?->url)
                        <div class="mt-4 rounded-xl border bg-admin-card p-4">
                            <img src="{{ $socialShareImage->url }}" alt="{{ $socialShareImage->alt }}" class="aspect-[1200/630] w-full rounded-lg border bg-admin-card object-cover">
                            <div class="mt-3 text-xs text-admin-secondary">
                                <div><span class="font-semibold">Key:</span> {{ $socialShareImage->key }}</div>
                                <div><span class="font-semibold">Alt:</span> {{ $socialShareImage->alt ?: '-' }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.global-assets.social-share-image.destroy') }}" class="mt-4">
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-confirm="Delete the default social share image?" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete Image</button>
                        </form>
                    @else
                        <div class="mt-4 flex aspect-[1200/630] items-center justify-center rounded-xl border border-dashed bg-admin-card text-sm font-semibold text-admin-secondary">
                            No social share image uploaded
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($activeTab === 'business-identity')
            <form method="POST" action="{{ route('admin.settings.global-assets.business-identity.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Business Identity</h2>
                <p class="mt-1 text-sm text-admin-secondary">These values are reused by the frontend header, footer, default metadata, and future structured data.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($businessIdentityFields as $field)
                        @php $value = $businessIdentity[$field['slug']] ?? $field['default'] ?? ''; @endphp
                        <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                            <label class="form-label">{{ $field['label'] }}</label>

                            @if($field['type'] === 'textarea')
                                <textarea name="business_identity[{{ $field['slug'] }}]" rows="4" class="admin-input">{{ $value }}</textarea>
                            @else
                                <input type="text" name="business_identity[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input">
                            @endif

                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @error("business_identity.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Business Identity</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'contact-information')
            <form method="POST" action="{{ route('admin.settings.global-assets.contact-information.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Contact Information</h2>
                <p class="mt-1 text-sm text-admin-secondary">These values are reused by footer information, WhatsApp CTAs, and future contact sections.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($contactInformationFields as $field)
                        @php $value = $contactInformation[$field['slug']] ?? $field['default'] ?? ''; @endphp
                        <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                            <label class="form-label">{{ $field['label'] }}</label>

                            @if($field['type'] === 'textarea')
                                <textarea name="contact_information[{{ $field['slug'] }}]" rows="4" class="admin-input">{{ $value }}</textarea>
                            @else
                                <input type="{{ in_array($field['type'], ['email', 'url'], true) ? $field['type'] : 'text' }}" name="contact_information[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input">
                            @endif

                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @error("contact_information.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Contact Information</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'social-media-links')
            <form method="POST" action="{{ route('admin.settings.global-assets.social-media-links.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Social Media Links</h2>
                <p class="mt-1 text-sm text-admin-secondary">Only links with a URL will be rendered in the frontend footer and future menus.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($socialMediaLinkFields as $field)
                        @php $value = $socialMediaLinks[$field['slug']] ?? ''; @endphp
                        <div class="rounded-xl border border-admin bg-admin-card p-4">
                            <label class="form-label">{{ $field['label'] }} URL</label>
                            <input type="url" name="social_media_links[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="https://...">
                            <p class="mt-2 text-xs text-admin-secondary">Frontend icon label: {{ $field['abbr'] }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
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

                <div class="mt-6 rounded-xl border border-admin bg-admin-card p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-admin-secondary">Custom Social Links</h3>
                            <p class="mt-1 text-sm text-admin-secondary">Add extra platforms that are not listed above.</p>
                        </div>
                        <button type="button" data-add-social-link class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Link</button>
                    </div>

                    <div class="mt-4 space-y-3" data-social-links-list>
                        @foreach($customSocialLinks as $index => $customLink)
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-admin bg-admin-card p-4 lg:grid-cols-[minmax(0,1fr)_120px_minmax(0,1.4fr)_auto]" data-social-link-row>
                                <div>
                                    <label class="form-label">Label</label>
                                    <input type="text" name="custom_social_links[{{ $index }}][label]" value="{{ $customLink['label'] ?? '' }}" class="admin-input" placeholder="Pinterest">
                                    @error("custom_social_links.$index.label") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Icon Label</label>
                                    <input type="text" name="custom_social_links[{{ $index }}][abbr]" value="{{ $customLink['abbr'] ?? '' }}" class="admin-input" placeholder="PT">
                                    @error("custom_social_links.$index.abbr") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">URL</label>
                                    <input type="url" name="custom_social_links[{{ $index }}][url]" value="{{ $customLink['url'] ?? '' }}" class="admin-input" placeholder="https://...">
                                    @error("custom_social_links.$index.url") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end">
                                    <button type="button" data-remove-social-link class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Social Media Links</button>
                </div>
            </form>

            <template data-social-link-template>
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-admin bg-admin-card p-4 lg:grid-cols-[minmax(0,1fr)_120px_minmax(0,1.4fr)_auto]" data-social-link-row>
                    <div>
                        <label class="form-label">Label</label>
                        <input type="text" data-name="label" class="admin-input" placeholder="Pinterest">
                    </div>
                    <div>
                        <label class="form-label">Icon Label</label>
                        <input type="text" data-name="abbr" class="admin-input" placeholder="PT">
                    </div>
                    <div>
                        <label class="form-label">URL</label>
                        <input type="url" data-name="url" class="admin-input" placeholder="https://...">
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
            <form method="POST" action="{{ route('admin.settings.global-assets.navigation-settings.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Header Navigation</h2>
                <p class="mt-1 text-sm text-admin-secondary">Controls the header’s appearance — CTA, sticky behavior, and menu colors.</p>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-violet-500/30 bg-violet-900/20 p-4">
                    <i class="fa-solid fa-circle-info mt-0.5 text-violet-400"></i>
                    <div class="text-sm">
                        <p class="font-semibold text-violet-200">Menu links are managed in the Menu Manager.</p>
                        <p class="mt-1 text-violet-300">
                            These settings control appearance only. To add, edit, reorder, or set link types for
                            header items, use
                            <a href="{{ route('admin.menus.index') }}" class="font-semibold underline">Menu Manager &rarr;</a>
                        </p>
                    </div>
                </div>


                <div class="mt-5 space-y-3" data-navigation-accordion>
                    <details class="rounded-xl border border-admin bg-admin-card" open>
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-admin-secondary transition hover:opacity-75">
                            Header Settings
                        </summary>

                        <div class="grid grid-cols-1 gap-5 border-t border-admin bg-admin-card p-4 lg:grid-cols-2">
                    @foreach($navigationBasicFields as $field)
                        @php
                            $value = $navigationSettings[$field['slug']] ?? $field['default'] ?? '';
                            $checkedValue = (bool) ($navigationSettings[$field['slug']] ?? false);
                        @endphp

                        <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'boolean' ? 'lg:col-span-2' : '' }}">
                            @if($field['type'] === 'boolean')
                                <input type="hidden" name="navigation_settings[{{ $field['slug'] }}]" value="0">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="navigation_settings[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                        <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                        <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                    </span>
                                </label>
                            @else
                                <label class="form-label">{{ $field['label'] }}</label>
                                <input type="text" name="navigation_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @endif

                            @error("navigation_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                        </div>
                    </details>

                    <details class="rounded-xl border border-admin bg-admin-card">
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-admin-secondary transition hover:opacity-75">
                            Menu Colors
                        </summary>

                        <div class="border-t border-admin bg-admin-card p-4">
                            <p class="text-sm text-admin-secondary">Compact color controls for header menu text, hover, active, and dropdown states.</p>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($navigationColorFields as $field)
                            @php $value = $navigationSettings[$field['slug']] ?? $field['default']; @endphp
                            <div class="rounded-xl border border-admin bg-admin-card p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <label class="text-sm font-bold text-admin-secondary">{{ $field['label'] }}</label>
                                        <p class="mt-1 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                    </div>
                                    <span class="h-10 w-10 shrink-0 rounded-lg border border-admin" style="background: {{ $value }}"></span>
                                </div>

                                <div class="mt-3 grid grid-cols-[56px_minmax(0,1fr)] gap-2">
                                    <input type="color" name="navigation_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="h-11 w-full cursor-pointer rounded-lg border border-admin bg-admin-card p-1">
                                    <input type="text" value="{{ $value }}" disabled class="admin-input bg-admin-card py-2 font-mono uppercase">
                                </div>

                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                @error("navigation_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                        </div>
                    </details>

                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Header Navigation</button>
                </div>
            </form>

            <script>
                (function () {
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
                })();
            </script>
        @endif

        @if($activeTab === 'footer-settings')
            <form method="POST" action="{{ route('admin.settings.global-assets.footer-settings.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Footer Settings</h2>
                <p class="mt-1 text-sm text-admin-secondary">Manage footer-specific display settings and menus while contact and social data stay global.</p>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-violet-500/30 bg-violet-900/20 p-4">
                    <i class="fa-solid fa-circle-info mt-0.5 text-violet-400"></i>
                    <div class="text-sm">
                        <p class="font-semibold text-violet-200">Footer links are managed in the Menu Manager.</p>
                        <p class="mt-1 text-violet-300">
                            These settings control footer display, logo, and layout blocks only. Manage the
                            <strong>Quick Links</strong> and <strong>Utility Links</strong> in
                            <a href="{{ route('admin.menus.index') }}" class="font-semibold underline">Menu Manager &rarr;</a>
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3" data-footer-accordion>
                    <details class="rounded-xl border border-admin bg-admin-card" open>
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-admin-secondary transition hover:opacity-75">
                            Footer Display
                        </summary>

                        <div class="grid grid-cols-1 gap-5 border-t border-admin bg-admin-card p-4 lg:grid-cols-2">
                            @foreach($footerFields as $field)
                                @php
                                    $value = $footerSettings[$field['slug']] ?? $field['default'] ?? '';
                                    $checkedValue = (bool) ($footerSettings[$field['slug']] ?? false);
                                @endphp

                                <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'boolean' ? '' : 'lg:col-span-2' }}">
                                    @if($field['type'] === 'boolean')
                                        <input type="hidden" name="footer_settings[{{ $field['slug'] }}]" value="0">
                                        <label class="flex items-start gap-3">
                                            <input type="checkbox" name="footer_settings[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                            <span>
                                                <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                                <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                                <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                            </span>
                                        </label>
                                    @elseif($field['type'] === 'select')
                                        <label class="form-label">{{ $field['label'] }}</label>
                                        <select name="footer_settings[{{ $field['slug'] }}]" class="admin-input">
                                            @foreach($field['options'] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                        <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                    @else
                                        <label class="form-label">{{ $field['label'] }}</label>
                                        <input type="text" name="footer_settings[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                        <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                        <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                    @endif

                                    @error("footer_settings.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </details>

                    @php
                        $footerLayoutBlocks = old('footer_layout_blocks', $footerSettings['layout_blocks'] ?? []);
                        $footerBlockTypes = \App\Support\FooterSettings::blockTypes();
                        $footerWidthOptions = \App\Support\FooterSettings::widthOptions();
                    @endphp

                    <details class="rounded-xl border border-admin bg-admin-card">
                        <summary class="cursor-pointer list-none rounded-xl px-4 py-4 text-base font-bold text-admin-secondary transition hover:opacity-75">
                            Layout Blocks
                        </summary>

                        <div class="border-t border-admin bg-admin-card p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-admin-secondary">Arrange footer columns, choose block width, and enable maps or custom text blocks.</p>
                                    <p class="mt-1 text-xs font-semibold text-admin-secondary" data-footer-layout-status>Active layout capacity: 0 / 3 columns.</p>
                                    @error('footer_layout_blocks') <p class="form-error mt-2">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" data-add-footer-block class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add Block</button>
                            </div>

                            <div class="mt-4 space-y-3" data-footer-blocks-list>
                                @foreach($footerLayoutBlocks as $index => $block)
                                    <div class="rounded-xl border border-admin bg-admin-card p-4" data-footer-block-row>
                                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_140px_220px]">
                                            <div>
                                                <label class="form-label">Type</label>
                                                <select name="footer_layout_blocks[{{ $index }}][type]" data-field="type" class="admin-input">
                                                    @foreach($footerBlockTypes as $typeValue => $typeLabel)
                                                        <option value="{{ $typeValue }}" @selected(($block['type'] ?? '') === $typeValue)>{{ $typeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Title</label>
                                                <input type="text" name="footer_layout_blocks[{{ $index }}][title]" data-field="title" value="{{ $block['title'] ?? '' }}" class="admin-input" placeholder="Quick Links">
                                            </div>
                                            <div>
                                                <label class="form-label">Width</label>
                                                <select name="footer_layout_blocks[{{ $index }}][width]" data-field="width" data-footer-layout-control class="admin-input">
                                                    @foreach($footerWidthOptions as $widthValue => $widthLabel)
                                                        <option value="{{ $widthValue }}" @selected(($block['width'] ?? '1') === $widthValue)>{{ $widthLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-admin-secondary">
                                                <input type="checkbox" name="footer_layout_blocks[{{ $index }}][is_active]" data-field="is_active" data-footer-layout-control value="1" @checked((bool) ($block['is_active'] ?? false)) class="h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                                Active
                                            </label>
                                            <div class="flex flex-wrap items-end gap-2">
                                                <button type="button" data-move-footer-block="up" class="rounded-lg border border-admin px-3 py-2 text-xs font-semibold text-admin-secondary transition hover:opacity-75">Move Up</button>
                                                <button type="button" data-move-footer-block="down" class="rounded-lg border border-admin px-3 py-2 text-xs font-semibold text-admin-secondary transition hover:opacity-75">Move Down</button>
                                                <button type="button" data-remove-footer-block class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                                            <div>
                                                <label class="form-label">Maps embed URL</label>
                                                <input type="text" name="footer_layout_blocks[{{ $index }}][settings][maps_embed_url]" data-setting-field="maps_embed_url" value="{{ $block['settings']['maps_embed_url'] ?? '' }}" class="admin-input" placeholder="https://www.google.com/maps/embed?...">
                                            </div>
                                            <div>
                                                <label class="form-label">Custom text / ads</label>
                                                <textarea name="footer_layout_blocks[{{ $index }}][settings][custom_body]" data-setting-field="custom_body" rows="2" class="admin-input" placeholder="Short support text or ads copy">{{ $block['settings']['custom_body'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </details>



                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Footer Settings</button>
                </div>
            </form>

            <template data-footer-block-template>
                <div class="rounded-xl border border-admin bg-admin-card p-4" data-footer-block-row>
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_150px_140px_220px]">
                        <div>
                            <label class="form-label">Type</label>
                            <select data-field="type" class="admin-input">
                                @foreach(\App\Support\FooterSettings::blockTypes() as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Title</label>
                            <input type="text" data-field="title" class="admin-input" placeholder="Quick Links">
                        </div>
                        <div>
                            <label class="form-label">Width</label>
                            <select data-field="width" data-footer-layout-control class="admin-input">
                                @foreach(\App\Support\FooterSettings::widthOptions() as $widthValue => $widthLabel)
                                    <option value="{{ $widthValue }}">{{ $widthLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 pt-8 text-sm font-semibold text-admin-secondary">
                            <input type="checkbox" data-field="is_active" data-footer-layout-control value="1" checked class="h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                            Active
                        </label>
                        <div class="flex flex-wrap items-end gap-2">
                            <button type="button" data-move-footer-block="up" class="rounded-lg border border-admin px-3 py-2 text-xs font-semibold text-admin-secondary transition hover:opacity-75">Move Up</button>
                            <button type="button" data-move-footer-block="down" class="rounded-lg border border-admin px-3 py-2 text-xs font-semibold text-admin-secondary transition hover:opacity-75">Move Down</button>
                            <button type="button" data-remove-footer-block class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div>
                            <label class="form-label">Maps embed URL</label>
                            <input type="text" data-setting-field="maps_embed_url" class="admin-input" placeholder="https://www.google.com/maps/embed?...">
                        </div>
                        <div>
                            <label class="form-label">Custom text / ads</label>
                            <textarea data-setting-field="custom_body" rows="2" class="admin-input" placeholder="Short support text or ads copy"></textarea>
                        </div>
                    </div>
                </div>
            </template>

            <script>
                (function () {
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
                            status.classList.toggle('text-admin-secondary', !isOverCapacity);
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
                    });

                    document.addEventListener('change', function (event) {
                        if (event.target.matches('[data-footer-layout-control]')) {
                            syncFooterLayoutCapacity();
                        }
                    });

                    reindexFooterBlocks();
                })();
            </script>
        @endif

        @if($activeTab === 'seo-default')
            <form method="POST" action="{{ route('admin.settings.global-assets.seo-default.update') }}" enctype="multipart/form-data" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">SEO Default</h2>
                <p class="mt-1 text-sm text-admin-secondary">Global fallback SEO for pages and products that do not provide their own metadata.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach($seoDefaultFields as $field)
                        @php
                            $value = $seoDefaultSettings[$field['slug']] ?? $field['default'] ?? '';
                            $checkedValue = (bool) ($seoDefaultSettings[$field['slug']] ?? false);
                        @endphp

                        <div class="rounded-xl border border-admin bg-admin-card p-4 {{ in_array($field['type'], ['textarea', 'boolean'], true) ? 'lg:col-span-2' : '' }}">
                            @if($field['type'] === 'boolean')
                                <input type="hidden" name="seo_default[{{ $field['slug'] }}]" value="0">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="seo_default[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                        <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                        <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                    </span>
                                </label>
                            @elseif($field['type'] === 'select')
                                <label class="form-label">{{ $field['label'] }}</label>
                                <select name="seo_default[{{ $field['slug'] }}]" class="admin-input">
                                    @foreach($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @elseif($field['type'] === 'textarea')
                                <label class="form-label">{{ $field['label'] }}</label>
                                <textarea name="seo_default[{{ $field['slug'] }}]" rows="3" class="admin-input">{{ $value }}</textarea>
                                <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @else
                                <label class="form-label">{{ $field['label'] }}</label>
                                <input type="{{ $field['type'] === 'url' ? 'url' : 'text' }}" name="seo_default[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                            @endif

                            @error("seo_default.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="rounded-xl border border-admin bg-admin-card p-4">
                        <x-admin.media-image-field
                            name="seo_default_og_image"
                            :value="old('seo_default_og_image', $seoDefaultOgImage?->path)"
                            label="Default OG image"
                            collection="content"
                            hint="Fallback social preview when a product/page has none. Recommended 1200 x 630 px." />
                        <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ \App\Support\SeoDefaultSettings::OG_IMAGE_KEY }}</p>
                        @error('seo_default_og_image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-admin bg-admin-card p-4">
                        <h3 class="text-base font-bold text-admin-secondary">Current OG Image</h3>

                        @if($seoDefaultOgImage?->url)
                            <img src="{{ $seoDefaultOgImage->url }}" alt="{{ $seoDefaultOgImage->alt }}" class="mt-4 aspect-[1200/630] w-full rounded-lg border bg-admin-card object-cover">
                            <form method="POST" action="{{ route('admin.settings.global-assets.seo-default.og-image.destroy') }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-confirm="Delete the default SEO OG image?" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete OG Image</button>
                            </form>
                        @else
                            <div class="mt-4 flex aspect-[1200/630] items-center justify-center rounded-xl border border-dashed bg-admin-card text-sm font-semibold text-admin-secondary">
                                No SEO OG image uploaded
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save SEO Default</button>
                </div>
            </form>
        @endif

        @if($activeTab === 'tracking-integrations')
            <form method="POST" action="{{ route('admin.settings.global-assets.tracking-integrations.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Tracking / Integrations</h2>
                <p class="mt-1 text-sm text-admin-secondary">Manage analytics scripts, verification tags, custom integrations, and WhatsApp CTA click events from one global source.</p>

                <div class="mt-5 space-y-3" data-tracking-accordion>
                    @foreach($trackingSections as $sectionName => $fields)
                        <details class="rounded-xl border border-admin bg-admin-card" @if($loop->first) open @endif>
                            <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-admin-secondary">
                                {{ $sectionName }}
                            </summary>

                            <div class="grid grid-cols-1 gap-4 border-t border-admin/50 p-4 lg:grid-cols-2">
                                @foreach($fields as $field)
                                    @php
                                        $value = $trackingIntegrationSettings[$field['slug']] ?? $field['default'] ?? '';
                                        $checkedValue = (bool) ($trackingIntegrationSettings[$field['slug']] ?? false);
                                    @endphp

                                    <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                                        @if($field['type'] === 'boolean')
                                            <input type="hidden" name="tracking_integrations[{{ $field['slug'] }}]" value="0">
                                            <label class="flex items-start gap-3">
                                                <input type="checkbox" name="tracking_integrations[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                                <span>
                                                    <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                                    <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                                    <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                                </span>
                                            </label>
                                        @elseif($field['type'] === 'select')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <select name="tracking_integrations[{{ $field['slug'] }}]" class="admin-input">
                                                @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @elseif($field['type'] === 'textarea')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <textarea name="tracking_integrations[{{ $field['slug'] }}]" rows="5" class="admin-input" placeholder="{{ $field['hint'] }}">{{ $value }}</textarea>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @else
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <input type="text" name="tracking_integrations[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @endif

                                        @error("tracking_integrations.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Tracking / Integrations</button>
                </div>
            </form>

            <script>
                (() => {
                    const accordion = document.querySelector('[data-tracking-accordion]');

                    if (!accordion) {
                        return;
                    }

                    accordion.querySelectorAll('details').forEach((details) => {
                        details.addEventListener('toggle', () => {
                            if (!details.open) {
                                return;
                            }

                            accordion.querySelectorAll('details[open]').forEach((openDetails) => {
                                if (openDetails !== details) {
                                    openDetails.open = false;
                                }
                            });
                        });
                    });
                })();
            </script>
        @endif

        @if($activeTab === 'booking-cta')
            <form method="POST" action="{{ route('admin.settings.global-assets.booking-cta.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Booking / CTA</h2>
                <p class="mt-1 text-sm text-admin-secondary">Manage global booking labels, WhatsApp destination, message templates, and frontend CTA placements without changing product or contact tables.</p>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-bold">Fallback priority</p>
                    <p class="mt-1">Product CTA data stays first. Global Booking / CTA is used as a shared fallback, then Contact Information, then system defaults.</p>
                </div>

                <div class="mt-5 space-y-3" data-booking-cta-accordion>
                    @foreach($bookingCtaSections as $sectionName => $fields)
                        <details class="rounded-xl border border-admin bg-admin-card" @if($loop->first) open @endif>
                            <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-admin-secondary">
                                {{ $sectionName }}
                            </summary>

                            <div class="grid grid-cols-1 gap-4 border-t border-admin/50 p-4 lg:grid-cols-2">
                                @foreach($fields as $field)
                                    @php
                                        $value = $bookingCtaSettings[$field['slug']] ?? $field['default'] ?? '';
                                        $checkedValue = (bool) ($bookingCtaSettings[$field['slug']] ?? false);
                                    @endphp

                                    <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                                        @if($field['type'] === 'boolean')
                                            <input type="hidden" name="booking_cta[{{ $field['slug'] }}]" value="0">
                                            <label class="flex items-start gap-3">
                                                <input type="checkbox" name="booking_cta[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                                <span>
                                                    <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                                    <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                                    <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                                </span>
                                            </label>
                                        @elseif($field['type'] === 'select')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <select name="booking_cta[{{ $field['slug'] }}]" class="admin-input">
                                                @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @elseif($field['type'] === 'textarea')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <textarea name="booking_cta[{{ $field['slug'] }}]" rows="4" class="admin-input" placeholder="{{ $field['hint'] }}">{{ $value }}</textarea>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @else
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <input type="text" name="booking_cta[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @endif

                                        @error("booking_cta.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Booking / CTA</button>
                </div>
            </form>

            <script>
                (() => {
                    const accordion = document.querySelector('[data-booking-cta-accordion]');

                    if (!accordion) {
                        return;
                    }

                    accordion.querySelectorAll('details').forEach((details) => {
                        details.addEventListener('toggle', () => {
                            if (!details.open) {
                                return;
                            }

                            accordion.querySelectorAll('details[open]').forEach((openDetails) => {
                                if (openDetails !== details) {
                                    openDetails.open = false;
                                }
                            });
                        });
                    });
                })();
            </script>
        @endif

        @if($activeTab === 'default-media')
            <form method="POST" action="{{ route('admin.settings.global-assets.default-media.update') }}" enctype="multipart/form-data" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Default Media / Placeholder Assets</h2>
                <p class="mt-1 text-sm text-admin-secondary">Upload fallback images used only when product, destination, section, hero, or avatar media is missing.</p>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-bold">Fallback priority</p>
                    <p class="mt-1">Specific product, destination, or section images stay first. Default Media only appears when the original media is empty.</p>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
                    @foreach($defaultMediaVariants as $variant)
                        @php
                            $asset = $defaultMediaAssets[$variant['key']] ?? null;
                            $slug = $variant['slug'];
                            $fitValue = old("default_media_fits.$slug", $defaultMediaSettings[$slug]['fit'] ?? \App\Support\DefaultMediaAssets::defaultFit($slug));
                        @endphp

                        <div class="rounded-xl border border-admin bg-admin-card p-4" data-default-media-card>
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="md:w-48">
                                    <div class="rounded-lg border border-admin bg-admin-card p-2">
                                        <img
                                            src="{{ $asset?->url }}"
                                            alt="{{ $asset?->alt ?: $variant['label'] }}"
                                            class="aspect-[4/3] w-full rounded-md bg-admin-card object-cover {{ $asset?->url ? '' : 'hidden' }}"
                                            data-default-media-preview
                                            data-current-src="{{ $asset?->url }}"
                                            data-current-alt="{{ $asset?->alt ?: $variant['label'] }}"
                                        >

                                        <div class="{{ $asset?->url ? 'hidden' : 'flex' }} aspect-[4/3] w-full items-center justify-center rounded-md border border-dashed bg-admin-card text-xs font-bold uppercase text-admin-secondary" data-default-media-empty>
                                            No image
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-lg border {{ $asset?->url ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-admin bg-admin-card text-admin-secondary' }} px-3 py-2 text-xs font-semibold" data-default-media-status data-saved-status="{{ $asset?->url ? 'Saved image active' : 'No image uploaded' }}">
                                        {{ $asset?->url ? 'Saved image active' : 'No image uploaded' }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold text-admin-secondary">{{ $variant['label'] }}</h3>
                                    <p class="mt-1 text-xs text-admin-secondary">{{ $variant['hint'] }}</p>
                                    <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $variant['key'] }}</p>

                                    <div class="mt-4 space-y-3">
                                        <div>
                                            <x-admin.media-image-field
                                                name="default_media[{{ $slug }}]"
                                                :value="old('default_media.'.$slug, $asset?->path)"
                                                label="Placeholder image"
                                                collection="content"
                                                hint="Pick from the Media Library or upload." />
                                            @error("default_media.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="form-label">Alt text</label>
                                            <input type="text" name="default_media_alts[{{ $slug }}]" value="{{ old("default_media_alts.$slug", $asset?->alt) }}" class="admin-input" placeholder="{{ $variant['label'] }}">
                                            @error("default_media_alts.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="form-label">Image fit</label>
                                            <select name="default_media_fits[{{ $slug }}]" class="admin-input">
                                                @foreach(\App\Support\DefaultMediaAssets::fitOptions() as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected($fitValue === $optionValue)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-admin-secondary">Controls how this placeholder image fills its frontend frame.</p>
                                            @error("default_media_fits.$slug") <p class="form-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($asset?->url)
                                <div class="mt-4 border-t border-admin/50 pt-4">
                                    <button type="submit" form="delete-default-media-{{ $slug }}" data-confirm="Delete this uploaded placeholder and return this variant to the system fallback?" class="w-full rounded-lg border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete / Reset to system fallback</button>
                                    <p class="mt-2 text-xs text-admin-secondary">This removes the saved upload and returns this placeholder to the Laravel/system fallback.</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Default Media</button>
                </div>
            </form>

            @foreach($defaultMediaVariants as $variant)
                @php
                    $asset = $defaultMediaAssets[$variant['key']] ?? null;
                    $slug = $variant['slug'];
                @endphp

                @if($asset?->url)
                    <form id="delete-default-media-{{ $slug }}" method="POST" action="{{ route('admin.settings.global-assets.default-media.destroy', $slug) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach

            <script>
                (() => {
                    document.querySelectorAll('[data-default-media-card]').forEach((card) => {
                        const input = card.querySelector('[data-default-media-input]');
                        const preview = card.querySelector('[data-default-media-preview]');
                        const empty = card.querySelector('[data-default-media-empty]');
                        const clear = card.querySelector('[data-default-media-clear]');
                        const status = card.querySelector('[data-default-media-status]');

                        if (!input || !preview || !empty || !clear || !status) {
                            return;
                        }

                        const currentSrc = preview.dataset.currentSrc || '';
                        const currentAlt = preview.dataset.currentAlt || '';
                        const savedStatus = status.dataset.savedStatus || 'No image uploaded';
                        let previewUrl = null;

                        const setStatus = (text, mode) => {
                            status.textContent = text;
                            status.classList.toggle('border-emerald-200', mode === 'saved');
                            status.classList.toggle('bg-emerald-50', mode === 'saved');
                            status.classList.toggle('text-emerald-700', mode === 'saved');
                            status.classList.toggle('border-amber-200', mode === 'pending');
                            status.classList.toggle('bg-amber-50', mode === 'pending');
                            status.classList.toggle('text-amber-700', mode === 'pending');
                            status.classList.toggle('border-admin', mode === 'empty');
                            status.classList.toggle('bg-admin-card', mode === 'empty');
                            status.classList.toggle('text-admin-secondary', mode === 'empty');
                        };

                        const revokePreview = () => {
                            if (previewUrl) {
                                URL.revokeObjectURL(previewUrl);
                                previewUrl = null;
                            }
                        };

                        clear.addEventListener('click', () => {
                            input.value = '';
                            revokePreview();
                            clear.classList.add('hidden');

                            if (currentSrc) {
                                preview.src = currentSrc;
                                preview.alt = currentAlt;
                                preview.classList.remove('hidden');
                                empty.classList.add('hidden');
                                setStatus(savedStatus, 'saved');
                                return;
                            }

                            preview.removeAttribute('src');
                            preview.classList.add('hidden');
                            empty.classList.remove('hidden');
                            setStatus(savedStatus, 'empty');
                        });

                        input.addEventListener('change', () => {
                            const file = input.files?.[0];

                            if (!file) {
                                clear.click();
                                return;
                            }

                            revokePreview();
                            previewUrl = URL.createObjectURL(file);
                            preview.src = previewUrl;
                            preview.alt = file.name;
                            preview.classList.remove('hidden');
                            empty.classList.add('hidden');
                            clear.classList.remove('hidden');
                            setStatus('New image selected. Save to publish this placeholder.', 'pending');
                        });
                    });
                })();
            </script>
        @endif

        @if($activeTab === 'structured-data')
            <form method="POST" action="{{ route('admin.settings.global-assets.structured-data.update') }}" class="rounded-xl border border-admin bg-admin-card p-5">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-bold text-admin-secondary">Structured Data / Business Schema</h2>
                <p class="mt-1 text-sm text-admin-secondary">Help search engines understand your business identity, website, breadcrumbs, and product/tour pages.</p>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-bold">Simple rule</p>
                    <p class="mt-1">Leave override fields empty if you want schema to follow Business Identity, Contact Information, Social Media Links, Site Logo, and SEO Default automatically.</p>
                    <p class="mt-2">Business schema also respects the SEO Default organization schema switch, so older SEO settings stay compatible while this screen becomes the main place to manage structured data.</p>
                </div>

                <div class="mt-5 space-y-3" data-structured-data-accordion>
                    @foreach($structuredDataSections as $sectionName => $fields)
                        <details class="rounded-xl border border-admin bg-admin-card" @if($loop->first) open @endif>
                            <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-admin-secondary">
                                {{ $sectionName }}
                            </summary>

                            <div class="grid grid-cols-1 gap-4 border-t border-admin/50 p-4 lg:grid-cols-2">
                                @foreach($fields as $field)
                                    @php
                                        $value = $structuredDataSettings[$field['slug']] ?? $field['default'] ?? '';
                                        $checkedValue = (bool) ($structuredDataSettings[$field['slug']] ?? false);
                                    @endphp

                                    <div class="rounded-xl border border-admin bg-admin-card p-4 {{ $field['type'] === 'textarea' ? 'lg:col-span-2' : '' }}">
                                        @if($field['type'] === 'boolean')
                                            <input type="hidden" name="structured_data[{{ $field['slug'] }}]" value="0">
                                            <label class="flex items-start gap-3">
                                                <input type="checkbox" name="structured_data[{{ $field['slug'] }}]" value="1" @checked($checkedValue) class="mt-1 h-5 w-5 rounded border-admin text-emerald-600 focus:ring-emerald-500">
                                                <span>
                                                    <span class="block text-sm font-bold text-admin-secondary">{{ $field['label'] }}</span>
                                                    <span class="mt-1 block text-xs text-admin-secondary">{{ $field['hint'] }}</span>
                                                    <span class="mt-2 block text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</span>
                                                </span>
                                            </label>
                                        @elseif($field['type'] === 'select')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <select name="structured_data[{{ $field['slug'] }}]" class="admin-input">
                                                @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @elseif($field['type'] === 'textarea')
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <textarea name="structured_data[{{ $field['slug'] }}]" rows="4" class="admin-input" placeholder="{{ $field['hint'] }}">{{ $value }}</textarea>
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @else
                                            <label class="form-label">{{ $field['label'] }}</label>
                                            <input type="text" name="structured_data[{{ $field['slug'] }}]" value="{{ $value }}" class="admin-input" placeholder="{{ $field['default'] }}">
                                            <p class="mt-2 text-xs text-admin-secondary">{{ $field['hint'] }}</p>
                                            <p class="mt-2 text-[11px] font-semibold uppercase text-admin-secondary">{{ $field['key'] }}</p>
                                        @endif

                                        @error("structured_data.{$field['slug']}") <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="mt-5 rounded-xl border border-admin bg-admin-card p-4 text-sm text-admin-secondary">
                    <p class="font-bold text-admin-secondary">What this renders</p>
                    <p class="mt-1">One JSON-LD graph containing business schema, website schema, breadcrumbs, and product schema when enabled. It replaces the old standalone SEO Default organization script to avoid duplicate schema.</p>
                </div>

                <div class="mt-5 flex items-center gap-3 border-t border-admin pt-5">
                    <button type="submit" class="admin-btn-primary">Save Structured Data</button>
                </div>
            </form>

            <script>
                (() => {
                    const accordion = document.querySelector('[data-structured-data-accordion]');

                    if (!accordion) {
                        return;
                    }

                    accordion.querySelectorAll('details').forEach((details) => {
                        details.addEventListener('toggle', () => {
                            if (!details.open) {
                                return;
                            }

                            accordion.querySelectorAll('details[open]').forEach((openDetails) => {
                                if (openDetails !== details) {
                                    openDetails.open = false;
                                }
                            });
                        });
                    });
                })();
            </script>
        @endif
    </div>
</div>

@endsection
