<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Locales (Phase 7 — Internationalization)
    |--------------------------------------------------------------------------
    | Code-first locale catalogue (mirrors config/media.php). The DEFAULT locale
    | is served with NO URL prefix so every existing URL keeps its exact meaning
    | and SEO history; every other active locale is served under /{code}/...
    |
    | Adding a locale later (e.g. 'zh' for the Chinese market) = one entry here
    | plus translations — zero schema work. Keys must stay stable once used.
    |
    | is_active = true → the locale is published (routed + shown in the switcher).
    | Set false to build a translation quietly before exposing it.
    */

    'default'  => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    'locales' => [
        'en' => [
            'native'    => 'English',
            'label'     => 'English',
            'flag'      => '🇬🇧',
            'is_active' => true,
        ],
        'id' => [
            'native'    => 'Bahasa Indonesia',
            'label'     => 'Indonesian',
            'flag'      => '🇮🇩',
            'is_active' => true,
        ],
    ],

];
