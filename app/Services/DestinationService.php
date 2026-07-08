<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DestinationService
{
    public function store(Request $request): Destination
    {
        $picked = trim((string) $request->input('image_path', ''));

        return Destination::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $picked !== '' ? $picked : $this->storeImage($request),
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    public function update(
        Request $request,
        Destination $destination
    ): Destination {
        $image = $destination->image;
        $picked = trim((string) $request->input('image_path', ''));

        if ($request->hasFile('image')) {
            $this->deleteLegacyFile($image);
            $image = $this->storeImage($request);
        } elseif ($picked !== '' && $picked !== $image) {
            // Media-Library selection: point at the shared asset. Only the old
            // module-owned file (destinations/) is removed — library files stay,
            // they belong to the Media Library and may be reused elsewhere.
            $this->deleteLegacyFile($image);
            $image = $picked;
        } elseif ($picked === '' && $request->has('image_path') && $image !== null) {
            // Field explicitly cleared in the form: detach (and clean up a
            // legacy file). Requests without the field keep the image as-is.
            $this->deleteLegacyFile($image);
            $image = null;
        }

        $destination->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $image,
            'is_active' => $request->boolean('is_active'),
        ]);

        return $destination->refresh();
    }

    public function deleteImage(Destination $destination): void
    {
        if (! $destination->image) {
            return;
        }

        Storage::disk('public')
            ->delete($destination->image);

        $destination->update([
            'image' => null,
        ]);
    }

    /**
     * Delete only module-owned uploads (destinations/…). Paths under media/
     * are Media Library assets and are managed (and delete-guarded) there.
     */
    protected function deleteLegacyFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'destinations/')) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request
            ->file('image')
            ->store('destinations', 'public');
    }
}
