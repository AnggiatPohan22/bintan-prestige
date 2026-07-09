<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Collections (Phase 6.1)
    |--------------------------------------------------------------------------
    | Storage structure by position. Uploads are stored under
    | media/{collection}/YYYY/MM/{uuid}.{ext}. Keys are stored in
    | media.collection and must stay stable once used — add new entries,
    | never rename existing ones (paths on disk reference them).
    */

    'default_collection' => 'general',

    /*
    | Collections whose uploads are stored in their ORIGINAL format instead of
    | being re-encoded to WebP. Logos/icons are typically transparent PNGs where
    | crisp edges and exact fidelity matter more than a few KB — so we keep the
    | file as-is. (Everything else is optimized to alpha-preserving WebP.)
    */
    'preserve_original_collections' => ['logo', 'icon'],

    'collections' => [
        'hero'        => 'Hero',
        'product'     => 'Product',
        'category'    => 'Category',
        'destination' => 'Destination',
        'logo'        => 'Logo',
        'icon'        => 'Icon',
        'gallery'     => 'Gallery',
        'section'     => 'Section',
        'content'     => 'Content',
        'general'     => 'General',
    ],

];
