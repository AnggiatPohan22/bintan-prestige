<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale (Phase 7 — Task A2).
 *
 * Applied per frontend route group with the group's locale as a parameter
 * (e.g. `SetLocale::class.':id'`). The locale is known at route-registration
 * time from the URL-prefix group, so it is route-cache safe and needs no
 * per-request segment sniffing. Falls back to the default locale for anything
 * unknown/inactive, then sets the Laravel app locale and persists it in session.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = ($locale !== null && Locales::isActive($locale))
            ? $locale
            : Locales::default();

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
