<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeoRobotsController extends Controller
{
    private const PATH = 'seo/robots.txt';

    public function edit()
    {
        $content = Storage::disk('local')->exists(self::PATH)
            ? Storage::disk('local')->get(self::PATH)
            : "User-agent: *\nAllow: /\n";

        return view('backend.seo.robots', compact('content'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        Storage::disk('local')->put(self::PATH, $request->input('content'));

        return redirect()->route('admin.seo.robots.edit')
            ->with('success', 'robots.txt updated.');
    }
}
