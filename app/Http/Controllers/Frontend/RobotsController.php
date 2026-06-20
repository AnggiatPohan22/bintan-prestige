<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class RobotsController extends Controller
{
    private const PATH = 'seo/robots.txt';

    public function index()
    {
        $content = Storage::disk('local')->exists(self::PATH)
            ? Storage::disk('local')->get(self::PATH)
            : "User-agent: *\nAllow: /\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
