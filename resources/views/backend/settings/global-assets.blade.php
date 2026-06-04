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

    <div class="grid grid-cols-1 gap-6">
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
    </div>
</div>

@endsection
