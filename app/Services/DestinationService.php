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
        return Destination::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Str::slug($request->name),
            'description' => $request->description,
            'image' => $this->storeImage($request),
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    public function update(
        Request $request,
        Destination $destination
    ): Destination {
        $image = $destination->image;

        if ($request->hasFile('image')) {
            if ($image) {
                Storage::disk('public')
                    ->delete($image);
            }

            $image = $this->storeImage($request);
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
