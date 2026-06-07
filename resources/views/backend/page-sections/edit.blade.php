@extends('layouts.admin')

@section('content')

@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
    $extraData = old('extra_data', json_encode($pageSection->extra_data ?? [], JSON_PRETTY_PRINT));
    $animation = old('animation', $pageSection->animation);
    $galleryCount = $pageSection->media->where('role', 'gallery')->count();
    $remainingSlots = max(0, \App\Models\PageSection::MEDIA_LIMIT - $galleryCount);
    $supportsLegacyImages = \App\Support\HomepageSectionMedia::supportsLegacyImages($pageSection->section_key);
@endphp

<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Page Section</h1>
            <p class="mt-1 text-sm text-slate-500">Manage content and images by their real layout position.</p>
        </div>
        <a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('admin.page-sections.update', $pageSection) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label class="form-label">Page key</label><input type="text" value="{{ $pageSection->page_key }}" disabled class="{{ $inputClass }} bg-slate-100"></div>
            <div><label class="form-label">Section key</label><input type="text" value="{{ $pageSection->section_key }}" disabled class="{{ $inputClass }} bg-slate-100"></div>
            <div><label class="form-label">Label</label><input type="text" name="label" value="{{ old('label', $pageSection->label) }}" class="{{ $inputClass }}"></div>
            <div><label class="form-label">Title</label><input type="text" name="title" value="{{ old('title', $pageSection->title) }}" class="{{ $inputClass }}"></div>
            <div class="md:col-span-2"><label class="form-label">Subtitle</label><input type="text" name="subtitle" value="{{ old('subtitle', $pageSection->subtitle) }}" class="{{ $inputClass }}"></div>
            <div class="md:col-span-2"><label class="form-label">Description</label><textarea name="description" rows="5" class="{{ $inputClass }}">{{ old('description', $pageSection->description) }}</textarea></div>
            <div><label class="form-label">Button text</label><input type="text" name="button_text" value="{{ old('button_text', $pageSection->button_text) }}" class="{{ $inputClass }}"></div>
            <div><label class="form-label">Button URL</label><input type="text" name="button_url" value="{{ old('button_url', $pageSection->button_url) }}" class="{{ $inputClass }}"></div>

            @if($usesLogo)
                <div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <h2 class="text-sm font-bold text-amber-900">Logo website global</h2>
                    <p class="mt-2 text-sm text-amber-800">Section ini memakai site logo dari Global Assets. Upload dan delete logo dilakukan dari halaman settings agar semua section memakai sumber yang sama.</p>
                    <a href="{{ route('admin.settings.global-assets.edit') }}" class="mt-3 inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Open Global Assets</a>
                </div>
            @endif

            @if(count($mediaSlots))
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-slate-800">Section image slots</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload sesuai posisi gambar di layout section ini.</p>
                    @unless($supportsMediaDisplayOptions ?? false)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Image size and position controls need the latest migration. Run <span class="font-semibold">php artisan migrate</span> to enable them.
                        </div>
                    @endunless
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($mediaSlots as $slot)
                            @php
                                $slotMedia = $pageSection->mediaSlot($slot['role'], $slot['slot_key']);
                                $slotInputName = "slot_uploads[{$slot['role']}][{$slot['slot_key']}]";
                                $slotFitName = "slot_object_fits[{$slot['role']}][{$slot['slot_key']}]";
                                $slotPositionName = "slot_object_positions[{$slot['role']}][{$slot['slot_key']}]";
                                $slotFit = old("slot_object_fits.{$slot['role']}.{$slot['slot_key']}", $slotMedia?->resolved_object_fit ?? 'cover');
                                $slotPosition = old("slot_object_positions.{$slot['role']}.{$slot['slot_key']}", $slotMedia?->resolved_object_position ?? 'center center');
                            @endphp
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <label class="form-label">{{ $slot['label'] }}</label>
                                <input type="file" name="{{ $slotInputName }}" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                                <p class="mt-2 text-xs text-slate-500">{{ $slot['hint'] ?? 'Leave empty to keep current image.' }}</p>
                                @if($slotMedia?->url)
                                    <img src="{{ $slotMedia->url }}" alt="{{ $slotMedia->alt }}" class="mt-3 h-32 w-full rounded-lg border bg-white" style="{{ $slotMedia->image_style }}">
                                @else
                                    <div class="mt-3 flex h-32 items-center justify-center rounded-lg border border-dashed bg-white text-xs font-bold uppercase text-slate-400">No image</div>
                                @endif
                                @if($supportsMediaDisplayOptions ?? false)
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="form-label">Image size</label>
                                            <select name="{{ $slotFitName }}" class="{{ $inputClass }}">
                                                @foreach(\App\Models\PageSectionMedia::OBJECT_FIT_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}" @selected($slotFit === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-slate-500">Controls how the image fills its frame.</p>
                                        </div>
                                        <div>
                                            <label class="form-label">Image position</label>
                                            <select name="{{ $slotPositionName }}" class="{{ $inputClass }}">
                                                @foreach(\App\Models\PageSectionMedia::OBJECT_POSITION_OPTIONS as $value => $label)
                                                    <option value="{{ $value }}" @selected($slotPosition === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-2 text-xs text-slate-500">Controls which part stays visible when cropped.</p>
                                        </div>
                                    </div>
                                @endif
                                @error("slot_uploads.{$slot['role']}.{$slot['slot_key']}") <p class="form-error">{{ $message }}</p> @enderror
                                @error("slot_object_fits.{$slot['role']}.{$slot['slot_key']}") <p class="form-error">{{ $message }}</p> @enderror
                                @error("slot_object_positions.{$slot['role']}.{$slot['slot_key']}") <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($supportsLegacyImages)
                <div>
                    <label class="form-label">Legacy image upload</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    <p class="mt-2 text-xs text-slate-400">Fallback lama. Leave empty to keep current image.</p>
                    @if($pageSection->image_url)<img src="{{ $pageSection->image_url }}" alt="{{ $pageSection->title }}" class="mt-3 h-32 w-full rounded-lg border object-cover">@endif
                </div>
                <div>
                    <label class="form-label">Legacy mobile image upload</label>
                    <input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                    <p class="mt-2 text-xs text-slate-400">Fallback lama. Leave empty to keep current image.</p>
                    @if($pageSection->mobile_image_url)<img src="{{ $pageSection->mobile_image_url }}" alt="{{ $pageSection->title }}" class="mt-3 h-32 w-full rounded-lg border object-cover">@endif
                </div>
            @else
                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-bold text-slate-800">No section image upload for this layout</h2>
                    <p class="mt-2 text-sm text-slate-500">Frontend for this section does not render a dedicated page-section image. It may use product, category, testimonial avatar, footer, or global placeholder media instead.</p>
                </div>
            @endif

            @if($supportsLegacyImages)
                <div><label class="form-label">Legacy image path</label><input type="text" name="image_path" value="{{ old('image_path', $pageSection->image) }}" class="{{ $inputClass }}"></div>
                <div><label class="form-label">Legacy mobile image path</label><input type="text" name="mobile_image_path" value="{{ old('mobile_image_path', $pageSection->mobile_image) }}" class="{{ $inputClass }}"></div>
            @endif

            @if($allowsGallery)
                <div class="md:col-span-2">
                    <label class="form-label">Section gallery images</label>
                    <input type="file" name="media_uploads[]" accept="image/jpeg,image/png,image/webp" multiple class="{{ $inputClass }}" @disabled($remainingSlots === 0)>
                    <p class="mt-2 text-xs text-slate-400">Upload up to {{ \App\Models\PageSection::MEDIA_LIMIT }} images. Remaining slots: {{ $remainingSlots }}.</p>
                    @error('media_uploads') <p class="form-error">{{ $message }}</p> @enderror
                    @error('media_uploads.*') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="form-label">Animation</label>
                <select name="animation" class="{{ $inputClass }}">
                    @foreach(\App\Models\PageSection::ANIMATION_OPTIONS as $option)
                        <option value="{{ $option }}" @selected($animation === $option)>{{ ucwords(str_replace('-', ' ', $option)) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="form-label">Sort order</label><input type="number" name="sort_order" min="0" value="{{ old('sort_order', $pageSection->sort_order) }}" class="{{ $inputClass }}"></div>
            <div class="md:col-span-2"><label class="form-label">Extra data JSON</label><textarea name="extra_data" rows="6" class="{{ $inputClass }}">{{ $extraData }}</textarea></div>
            <label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $pageSection->is_active))><span class="text-sm font-semibold text-slate-700">Active</span></label>
        </div>
        <div class="flex items-center gap-3 border-t pt-5"><button type="submit" class="btn-primary">Save Section</button><a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">Cancel</a></div>
    </form>

    @if($pageSection->media->count())
        <div class="mt-8 border-t pt-6">
            <h2 class="text-lg font-bold text-slate-800">Stored Section Media</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($pageSection->media as $media)
                    <div class="rounded-xl border bg-white p-3 shadow-sm">
                        <img src="{{ $media->url }}" alt="{{ $media->alt }}" class="h-32 w-full rounded-lg object-cover">
                        <div class="mt-2 text-xs font-semibold text-slate-500">
                            {{ $media->label ?: ucwords(str_replace('_', ' ', $media->slot_key)) }}
                        </div>
                        <div class="mt-1 text-[11px] uppercase text-slate-400">
                            {{ $media->role }} / {{ $media->slot_key }}
                        </div>
                        <form method="POST" action="{{ route('admin.page-sections.media.destroy', $media) }}" class="mt-3">@csrf @method('DELETE')<button type="submit" onclick="return confirm('Delete this section image?')" class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Delete Image</button></form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@endsection
