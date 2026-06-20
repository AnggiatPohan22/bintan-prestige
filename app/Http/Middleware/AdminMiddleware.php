<?php

namespace App\Http\Middleware;

use App\Facades\CmsHooks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Ensure authenticated users have admin access before entering the CMS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessAdmin()) {
            abort(403);
        }

        $response = $next($request);

        CmsHooks::doAction('admin.loaded', $request);

        return $response;
    }
}
