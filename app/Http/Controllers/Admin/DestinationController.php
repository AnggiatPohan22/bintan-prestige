<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use App\Services\DestinationService;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function __construct(
        protected DestinationService $destinationService,
    ) {}

    public function index(Request $request)
    {
        $destinations = Destination::query()
            ->withCount('products')
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )
            )
            ->latest()
            ->paginate(10, ['*'], 'destinations_page')
            ->withQueryString();

        $archivedDestinations = Destination::onlyTrashed()
            ->withCount('products')
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                )
            )
            ->latest('deleted_at')
            ->paginate(10, ['*'], 'archive_page')
            ->withQueryString();

        return view('backend.destinations.index', [
            'destinations' => $destinations,
            'archivedDestinations' => $archivedDestinations,
        ]);
    }

    public function create()
    {
        return view('backend.destinations.create');
    }

    public function store(StoreDestinationRequest $request)
    {
        $this->destinationService
            ->store($request);

        return redirect()
            ->route('admin.destinations.index')
            ->with('success', 'Destination created successfully.');
    }

    public function edit(Destination $destination)
    {
        return view('backend.destinations.edit', [
            'destination' => $destination,
        ]);
    }

    public function update(
        UpdateDestinationRequest $request,
        Destination $destination
    ) {
        $this->destinationService
            ->update($request, $destination);

        return redirect()
            ->route('admin.destinations.edit', $destination)
            ->with('success', 'Destination updated successfully.');
    }

    public function destroy(Destination $destination)
    {
        $destination->delete();

        return redirect()
            ->route('admin.destinations.index')
            ->with('success', 'Destination moved to archive.');
    }

    public function restore(int $destination)
    {
        Destination::onlyTrashed()
            ->findOrFail($destination)
            ->restore();

        return redirect()
            ->route('admin.destinations.index')
            ->with('success', 'Destination restored successfully.');
    }

    public function forceDelete(int $destination)
    {
        $destination = Destination::onlyTrashed()
            ->withCount('products')
            ->findOrFail($destination);

        if ($destination->products_count > 0) {
            return redirect()
                ->route('admin.destinations.index')
                ->with(
                    'error',
                    'Destination cannot be permanently deleted because it still has products.'
                );
        }

        $this->destinationService
            ->deleteImage($destination);

        $destination->forceDelete();

        return redirect()
            ->route('admin.destinations.index')
            ->with('success', 'Destination permanently deleted.');
    }
}
