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
