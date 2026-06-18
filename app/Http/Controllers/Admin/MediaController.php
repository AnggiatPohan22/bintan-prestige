<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
    ) {}

    /**
     * Grid view with search + type (extension) filter. `?picker=1` renders a
     * minimal grid for the (future) block-editor media picker.
     */
    public function index(Request $request)
    {
        $media = $this->mediaService->list([
            'search'    => $request->query('search'),
            'extension' => $request->query('type'),
        ]);

        $view = $request->boolean('picker')
            ? 'backend.media.picker'
            : 'backend.media.index';

        return view($view, [
            'media'      => $media,
            'extensions' => $this->mediaService->availableExtensions(),
            'search'     => $request->query('search'),
            'activeType' => $request->query('type'),
        ]);
    }

    public function store(StoreMediaRequest $request)
    {
        $created = [];

        foreach ($request->file('files', []) as $file) {
            $created[] = $this->mediaService->store($file, $request->user());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'media'   => collect($created)->map(fn (Media $m) => $this->payload($m)),
            ]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', count($created) . ' file(s) uploaded.');
    }

    public function update(UpdateMediaRequest $request, Media $media)
    {
        $this->mediaService->updateMeta($media, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'media' => $this->payload($media->refresh())]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', 'Media details updated.');
    }

    public function destroy(Request $request, Media $media)
    {
        $this->mediaService->delete($media);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', 'Media deleted.');
    }

    /**
     * Batch upload for gallery blocks. Now also registers Media records.
     * Accepts up to 20 files[], returns array of { id, path, url, filename }.
     */
    public function uploadBatch(Request $request)
    {
        $request->validate([
            'files'   => ['required', 'array', 'max:20'],
            'files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $results = [];

        foreach ($request->file('files') as $file) {
            $media     = $this->mediaService->store($file, $request->user());
            $results[] = $this->payload($media);
        }

        return response()->json(['success' => true, 'files' => $results]);
    }

    /**
     * Quick upload for block image / background fields. Registers a Media record.
     * Returns JSON with the storage URL so Alpine.js can fill the path field.
     */
    public function uploadQuick(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $media = $this->mediaService->store($request->file('image'), $request->user());

        return response()->json($this->payload($media) + ['success' => true]);
    }

    /** Shared JSON shape (back-compatible: keeps path/url/filename). */
    private function payload(Media $media): array
    {
        return [
            'id'       => $media->id,
            'path'     => $media->path,
            'url'      => $media->url,
            'filename' => $media->original_name,
        ];
    }
}
