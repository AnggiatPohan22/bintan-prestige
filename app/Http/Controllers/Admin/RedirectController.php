<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index()
    {
        $redirects = Redirect::orderByDesc('updated_at')->paginate(25);

        return view('backend.seo.redirects.index', compact('redirects'));
    }

    public function create()
    {
        return view('backend.seo.redirects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_url'    => ['required', 'string', 'max:500', 'unique:redirects,from_url'],
            'to_url'      => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'integer', 'in:301,302'],
            'is_active'   => ['boolean'],
        ]);

        $data['from_url']  = '/'.ltrim($data['from_url'], '/');
        $data['is_active'] = $request->boolean('is_active', true);

        Redirect::create($data);

        return redirect()->route('admin.seo.redirects.index')
            ->with('success', 'Redirect created.');
    }

    public function edit(Redirect $redirect)
    {
        return view('backend.seo.redirects.edit', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect)
    {
        $data = $request->validate([
            'from_url'    => ['required', 'string', 'max:500', 'unique:redirects,from_url,'.$redirect->id],
            'to_url'      => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'integer', 'in:301,302'],
            'is_active'   => ['boolean'],
        ]);

        $data['from_url']  = '/'.ltrim($data['from_url'], '/');
        $data['is_active'] = $request->boolean('is_active', true);

        $redirect->update($data);

        return redirect()->route('admin.seo.redirects.index')
            ->with('success', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return redirect()->route('admin.seo.redirects.index')
            ->with('success', 'Redirect deleted.');
    }
}
