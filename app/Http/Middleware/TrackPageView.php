<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip tracking for authenticated admin/editor users.
        if ($request->user()) {
            return $response;
        }

        $page = $request->route('page');
        if (! $page instanceof Page || ! $response->isSuccessful()) {
            return $response;
        }

        try {
            PageView::create([
                'page_id'      => $page->id,
                'visitor_hash' => hash('sha256', $request->ip() . $request->userAgent()),
                'referrer'     => $request->header('referer'),
                'viewed_date'  => now()->toDateString(),
            ]);
        } catch (\Throwable) {
            // Tracking must never break the page response.
        }

        return $response;
    }
}
