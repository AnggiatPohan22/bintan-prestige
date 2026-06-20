<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleFontsService
{
    public const CACHE_KEY = 'google_fonts.list.v1';
    public const CACHE_TTL = 86400; // 24 hours

    /**
     * Return a sorted-by-popularity list of Google Font family names.
     *
     * Returns [] when:
     *   - GOOGLE_FONTS_API_KEY is not configured
     *   - The API call fails or returns a non-200 response
     *
     * Result is cached for 24 hours.
     *
     * @return string[]
     */
    public function getFontList(): array
    {
        $key = config('services.google_fonts.key');

        if (empty($key)) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($key): array {
            try {
                $response = Http::timeout(5)->get('https://www.googleapis.com/webfonts/v1/webfonts', [
                    'key'  => $key,
                    'sort' => 'popularity',
                ]);

                if (! $response->successful()) {
                    Log::warning('GoogleFontsService: API returned non-successful response.', [
                        'status' => $response->status(),
                    ]);

                    return [];
                }

                return collect($response->json('items', []))
                    ->pluck('family')
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                Log::warning('GoogleFontsService: failed to fetch font list.', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /** Clear the cached font list (e.g. after refreshing from the admin). */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
