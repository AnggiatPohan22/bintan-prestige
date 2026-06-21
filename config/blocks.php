<?php

return [
    'group' => [
        'label' => 'Group / Section', 'icon' => 'layer-group', 'category' => 'layout',
        'description' => 'Container for nested blocks with section width and spacing controls.',
        'keywords' => ['section', 'container', 'wrapper'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
    ],
    'columns' => [
        'label' => 'Columns', 'icon' => 'columns', 'category' => 'layout',
        'description' => 'Responsive two-to-four-column layout containing Group blocks.',
        'keywords' => ['grid', 'layout', 'row'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
    ],
    'hero' => [
        'label' => 'Hero', 'icon' => 'photo', 'category' => 'content',
        'description' => 'Large opening section with image, headline, and call to action.',
        'keywords' => ['banner', 'header', 'intro'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'heading' => [
        'label' => 'Heading', 'icon' => 'heading', 'category' => 'content',
        'description' => 'Semantic section heading with level and alignment controls.',
        'keywords' => ['title', 'headline'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => true, 'children' => false],
    ],
    'text' => [
        'label' => 'Text', 'icon' => 'document-text', 'category' => 'content',
        'description' => 'Rich text content with an optional heading.',
        'keywords' => ['paragraph', 'copy', 'article'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'image' => [
        'label' => 'Image', 'icon' => 'photograph', 'category' => 'media',
        'description' => 'Single image with alternative text, caption, and width controls.',
        'keywords' => ['photo', 'media'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'gallery' => [
        'label' => 'Gallery', 'icon' => 'collection', 'category' => 'media',
        'description' => 'Responsive image gallery with carousel and lightbox options.',
        'keywords' => ['photos', 'carousel', 'slider'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'video_embed' => [
        'label' => 'Video / Embed', 'icon' => 'video-camera', 'category' => 'media',
        'description' => 'Privacy-conscious YouTube or Vimeo video embed.',
        'keywords' => ['youtube', 'vimeo', 'movie'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => false],
    ],
    'button_group' => [
        'label' => 'Button Group', 'icon' => 'cursor-click', 'category' => 'conversion',
        'description' => 'One or more action links with style and alignment controls.',
        'keywords' => ['button', 'link', 'action'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => true, 'children' => false],
    ],
    'stats' => [
        'label' => 'Stats', 'icon' => 'chart-bar', 'category' => 'content',
        'description' => 'Trust signals and key figures displayed in a responsive grid.',
        'keywords' => ['numbers', 'counter', 'facts'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => true, 'children' => false],
    ],
    'tour_itinerary' => [
        'label' => 'Tour Itinerary', 'icon' => 'route', 'category' => 'travel',
        'description' => 'Day-by-day or step-by-step travel itinerary timeline.',
        'keywords' => ['timeline', 'schedule', 'trip'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => false],
    ],
    'pricing_table' => [
        'label' => 'Pricing Table', 'icon' => 'tags', 'category' => 'conversion',
        'description' => 'Comparable package prices and included features.',
        'keywords' => ['price', 'package', 'plan'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => false],
    ],
    'cta' => [
        'label' => 'Call to Action', 'icon' => 'speakerphone', 'category' => 'conversion',
        'description' => 'Prominent call-to-action section with supporting copy.',
        'keywords' => ['banner', 'button', 'conversion'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'products_grid' => [
        'label' => 'Products Grid', 'icon' => 'view-grid', 'category' => 'travel',
        'description' => 'Dynamic product selection filtered by category or destination.',
        'keywords' => ['tour', 'activity', 'product'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'faq' => [
        'label' => 'FAQ', 'icon' => 'question-mark-circle', 'category' => 'content',
        'description' => 'Frequently asked questions from inline content or the FAQ library.',
        'keywords' => ['accordion', 'questions', 'answers'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'testimonials' => [
        'label' => 'Testimonials', 'icon' => 'chat-alt-2', 'category' => 'travel',
        'description' => 'Guest quotes with ratings and optional avatars.',
        'keywords' => ['reviews', 'rating', 'quote'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'map' => [
        'label' => 'Map', 'icon' => 'location-marker', 'category' => 'travel',
        'description' => 'Location map from a validated HTTP or HTTPS embed URL.',
        'keywords' => ['location', 'destination', 'address'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'divider' => [
        'label' => 'Divider', 'icon' => 'minus', 'category' => 'layout',
        'description' => 'Visual separator or vertical spacing between blocks.',
        'keywords' => ['separator', 'spacer', 'line'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
    'contact_form' => [
        'label' => 'Contact Form', 'icon' => 'mail', 'category' => 'conversion',
        'description' => 'Published form from the contact form builder.',
        'keywords' => ['form', 'inquiry', 'contact'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
];
