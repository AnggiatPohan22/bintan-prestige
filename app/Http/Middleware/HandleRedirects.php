<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');

        $map = Cache::remember('cms.redirects', 600, function () {
            return Redirect::active()
                ->get(['from_url', 'to_url', 'status_code'])
                ->keyBy('from_url')
                ->all();
        });

        if (isset($map[$path])) {
            $r = $map[$path];
            return redirect($r->to_url, $r->status_code);
        }

        return $next($request);
    }
}
