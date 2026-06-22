<?php

return [
    'group' => [
        'label' => 'Group / Section', 'icon' => 'layer-group', 'category' => 'layout',
        'description' => 'Container for nested blocks with section width and spacing controls.',
        'keywords' => ['section', 'container', 'wrapper'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
        'fields' => [
            ['key' => 'width', 'type' => 'select', 'label' => 'Content Width', 'default' => 'contained', 'options' => ['contained' => 'Contained', 'wide' => 'Wide', 'full' => 'Full width']],
            ['key' => 'spacing', 'type' => 'select', 'label' => 'Vertical Spacing', 'default' => 'md', 'options' => ['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
        ],
    ],
    'columns' => [
        'label' => 'Columns', 'icon' => 'columns', 'category' => 'layout',
        'description' => 'Responsive two-to-four-column layout containing Group blocks.',
        'keywords' => ['grid', 'layout', 'row'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
        'fields' => [
            ['key' => 'columns', 'type' => 'select', 'label' => 'Column Count', 'default' => '2', 'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns']],
            ['key' => 'gap', 'type' => 'select', 'label' => 'Gap', 'default' => 'md', 'options' => ['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
            ['key' => 'stack_mobile', 'type' => 'toggle', 'label' => 'Stack columns vertically on mobile', 'default' => true],
        ],
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
        'fields' => [
            ['key' => 'text', 'type' => 'text', 'label' => 'Heading Text', 'maxlength' => 500, 'placeholder' => 'Section heading'],
            ['key' => 'level', 'type' => 'select', 'label' => 'Semantic Level', 'default' => 'h2', 'options' => ['h2' => 'H2 — Main section', 'h3' => 'H3 — Subsection', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6']],
            ['key' => 'alignment', 'type' => 'select', 'label' => 'Alignment', 'default' => 'left', 'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
        ],
    ],
    'text' => [
        'label' => 'Text', 'icon' => 'document-text', 'category' => 'content',
        'description' => 'Rich text content with an optional heading.',
        'keywords' => ['paragraph', 'copy', 'article'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Heading', 'placeholder' => 'Section heading (optional)'],
            ['key' => 'body_html', 'type' => 'richtext', 'label' => 'Body HTML', 'rows' => 8, 'placeholder' => 'Allowed tags: p, br, strong, em, ul, ol, li, a, h2, h3, blockquote', 'help' => 'HTML is sanitized on save. Allowed: p, br, strong, em, ul, ol, li, a, h2, h3, blockquote'],
        ],
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
        'fields' => [
            ['key' => 'url', 'type' => 'url', 'label' => 'YouTube or Vimeo URL', 'placeholder' => 'https://www.youtube.com/watch?v=...', 'help' => 'Only YouTube and Vimeo URLs are accepted; converted to a safe embed URL on save.'],
            ['key' => 'title', 'type' => 'text', 'label' => 'Accessible Title', 'maxlength' => 255, 'placeholder' => 'Bintan island tour video'],
            ['key' => 'aspect_ratio', 'type' => 'select', 'label' => 'Aspect Ratio', 'default' => '16-9', 'options' => ['16-9' => 'Widescreen (16:9)', '4-3' => 'Standard (4:3)', '1-1' => 'Square (1:1)']],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Caption', 'maxlength' => 1000, 'placeholder' => 'Optional caption'],
        ],
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
        'fields' => [
            ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'placeholder' => 'CTA headline'],
            ['key' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3, 'placeholder' => 'Supporting text'],
            ['key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'placeholder' => 'e.g. Book Now'],
            ['key' => 'button_url', 'type' => 'text', 'label' => 'Button URL', 'placeholder' => '/products'],
            ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'dark', 'options' => ['dark' => 'Dark (black background)', 'light' => 'Light (white background)', 'gold' => 'Gold accent']],
        ],
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
        'fields' => [
            ['key' => 'embed_url', 'type' => 'url', 'label' => 'Google Maps Embed URL', 'placeholder' => 'https://www.google.com/maps/embed?...', 'help' => 'Google Maps → Share → Embed a map → copy the src URL.'],
            ['key' => 'address', 'type' => 'text', 'label' => 'Address (text)', 'placeholder' => 'e.g. Lagoi Bay, Bintan Island, Indonesia'],
            ['key' => 'zoom', 'type' => 'number', 'label' => 'Zoom Level', 'default' => 14, 'min' => 1, 'max' => 20, 'help' => '1 (world) to 20 (building). Default: 14.'],
        ],
    ],
    'divider' => [
        'label' => 'Divider', 'icon' => 'minus', 'category' => 'layout',
        'description' => 'Visual separator or vertical spacing between blocks.',
        'keywords' => ['separator', 'spacer', 'line'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'style', 'type' => 'select', 'label' => 'Divider Style', 'default' => 'line', 'options' => ['line' => 'Line (thin horizontal rule)', 'space' => 'Space (blank vertical gap)', 'gold-line' => 'Gold line (brand accent)']],
        ],
    ],
    'contact_form' => [
        'label' => 'Contact Form', 'icon' => 'mail', 'category' => 'conversion',
        'description' => 'Published form from the contact form builder.',
        'keywords' => ['form', 'inquiry', 'contact'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
    ],
];
