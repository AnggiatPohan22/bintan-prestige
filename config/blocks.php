<?php

/*
|--------------------------------------------------------------------------
| Block registry
|--------------------------------------------------------------------------
| Each block declares its editable `fields` (read by the visual builder's
| settings panel). A field may carry a 'tab' (layout|style|advanced); when a
| block uses more than one tab the panel shows a Layout/Style/Advanced switcher.
| Blocks without tabs render as a single list (unchanged).
*/

// Style + Advanced tabs shared by container blocks (Group / Columns).
$styleTab = [
    ['key' => 'background', 'type' => 'background', 'tab' => 'style', 'label' => 'Background'],
];
$advancedTab = [
    ['key' => 'margin', 'type' => 'box', 'tab' => 'advanced', 'label' => 'Margin (px)'],
    ['key' => 'padding', 'type' => 'box', 'tab' => 'advanced', 'label' => 'Padding (px)'],
    ['key' => 'z_index', 'type' => 'number', 'tab' => 'advanced', 'label' => 'Z-Index'],
    ['key' => 'css_id', 'type' => 'text', 'tab' => 'advanced', 'label' => 'CSS ID', 'placeholder' => 'my-section'],
    ['key' => 'css_classes', 'type' => 'text', 'tab' => 'advanced', 'label' => 'CSS Classes', 'placeholder' => 'class-one class-two'],
    ['key' => 'hide_desktop', 'type' => 'toggle', 'tab' => 'advanced', 'label' => 'Hide on Desktop'],
    ['key' => 'hide_tablet', 'type' => 'toggle', 'tab' => 'advanced', 'label' => 'Hide on Tablet'],
    ['key' => 'hide_mobile', 'type' => 'toggle', 'tab' => 'advanced', 'label' => 'Hide on Mobile'],
    ['key' => 'custom_css', 'type' => 'code', 'tab' => 'advanced', 'label' => 'Custom CSS', 'rows' => 6, 'placeholder' => "#my-section {\n  border: 1px solid gold;\n}", 'help' => 'Scoped to this page. Target this block with the CSS ID/Class you set above.'],
];

return [
    'group' => [
        'label' => 'Group / Section', 'icon' => 'layer-group', 'category' => 'layout',
        'description' => 'Container for nested blocks with section width and spacing controls.',
        'keywords' => ['section', 'container', 'wrapper'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
        'fields' => [
            ['key' => 'width', 'type' => 'select', 'tab' => 'layout', 'label' => 'Content Width', 'default' => 'contained', 'options' => ['contained' => 'Boxed', 'wide' => 'Standard', 'full' => 'Full width (no side spacing)']],
            ['key' => 'spacing', 'type' => 'select', 'tab' => 'layout', 'label' => 'Vertical Spacing', 'default' => 'md', 'options' => ['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
            ...$styleTab,
            ...$advancedTab,
        ],
    ],
    'columns' => [
        'label' => 'Columns', 'icon' => 'columns', 'category' => 'layout',
        'description' => 'Responsive two-to-four-column layout containing Group blocks.',
        'keywords' => ['grid', 'layout', 'row'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => true],
        'fields' => [
            ['key' => 'width', 'type' => 'select', 'tab' => 'layout', 'label' => 'Content Width', 'default' => 'wide', 'options' => ['contained' => 'Boxed', 'wide' => 'Standard', 'full' => 'Full width (no side spacing)']],
            ['key' => 'columns', 'type' => 'select', 'tab' => 'layout', 'label' => 'Column Count', 'default' => '2', 'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns']],
            ['key' => 'gap', 'type' => 'select', 'tab' => 'layout', 'label' => 'Columns Gap', 'default' => 'md', 'options' => ['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
            ['key' => 'stack_mobile', 'type' => 'toggle', 'tab' => 'layout', 'label' => 'Stack columns vertically on mobile', 'default' => true],
            ...$styleTab,
            ...$advancedTab,
        ],
    ],
    'hero' => [
        'label' => 'Hero', 'icon' => 'photo', 'category' => 'content',
        'description' => 'Large opening section with image, headline, and call to action.',
        'keywords' => ['banner', 'header', 'intro'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'title', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Title', 'placeholder' => 'Main headline'],
            ['key' => 'subtitle', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Subtitle', 'placeholder' => 'Supporting text below headline'],
            ['key' => 'image', 'type' => 'image', 'label' => 'Hero Image', 'placeholder' => 'e.g. pages/hero.webp or https://...'],
            ['key' => 'cta_text', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'CTA Button Text', 'placeholder' => 'e.g. Explore Tours'],
            ['key' => 'cta_url', 'type' => 'text', 'label' => 'CTA Button URL', 'placeholder' => '/products'],
            ['key' => 'background_color', 'type' => 'color', 'label' => 'Background Color', 'default' => '#0f0f0f'],
            ['key' => 'min_height', 'type' => 'select', 'label' => 'Min Height', 'default' => 'large', 'options' => ['small' => 'Small (400px)', 'medium' => 'Medium (600px)', 'large' => 'Large (80vh)']],
            ['key' => 'overlay_opacity', 'type' => 'range', 'label' => 'Overlay Opacity', 'default' => 40, 'min' => 0, 'max' => 90, 'step' => 5, 'suffix' => '%'],
            ['key' => 'has_overlay', 'type' => 'toggle', 'label' => 'Enable gradient overlay', 'default' => true],
        ],
    ],
    'heading' => [
        'label' => 'Heading', 'icon' => 'heading', 'category' => 'content',
        'description' => 'Semantic section heading with level and alignment controls.',
        'keywords' => ['title', 'headline'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => true, 'children' => false],
        'fields' => [
            ['key' => 'text', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Heading Text', 'maxlength' => 500, 'placeholder' => 'Section heading'],
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
            ['key' => 'heading', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Heading', 'placeholder' => 'Section heading (optional)'],
            ['key' => 'body_html', 'type' => 'richtext', 'inline' => 'richtext', 'label' => 'Body HTML', 'rows' => 8, 'placeholder' => 'Allowed tags: p, br, strong, em, ul, ol, li, a, h2, h3, blockquote', 'help' => 'HTML is sanitized on save. Allowed: p, br, strong, em, ul, ol, li, a, h2, h3, blockquote'],
        ],
    ],
    'image' => [
        'label' => 'Image', 'icon' => 'photograph', 'category' => 'media',
        'description' => 'Single image with alternative text, caption, and width controls.',
        'keywords' => ['photo', 'media'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'src', 'type' => 'image', 'label' => 'Image', 'placeholder' => 'e.g. pages/about-hero.webp or https://...'],
            ['key' => 'alt', 'type' => 'text', 'label' => 'Alt Text', 'placeholder' => 'Describe the image for accessibility'],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Caption', 'placeholder' => 'Optional caption below image'],
            ['key' => 'width_class', 'type' => 'select', 'label' => 'Width', 'default' => 'full', 'options' => ['full' => 'Full width', 'half' => 'Half width', 'third' => 'One third']],
        ],
    ],
    'gallery' => [
        'label' => 'Gallery', 'icon' => 'collection', 'category' => 'media',
        'description' => 'Responsive image gallery with carousel and lightbox options.',
        'keywords' => ['photos', 'carousel', 'slider'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'images', 'type' => 'repeater', 'label' => 'Images', 'itemLabel' => 'Image', 'fields' => [
                ['key' => 'src', 'type' => 'image', 'label' => 'Image'],
                ['key' => 'alt', 'type' => 'text', 'label' => 'Alt text', 'placeholder' => 'Accessibility description'],
                ['key' => 'caption', 'type' => 'text', 'label' => 'Caption', 'placeholder' => 'Optional'],
            ]],
            ['key' => 'columns', 'type' => 'select', 'label' => 'Images per row', 'default' => '3', 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'], 'help' => 'Extra rows become a carousel.'],
            ['key' => 'gap', 'type' => 'select', 'label' => 'Spacing', 'default' => 'md', 'options' => ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
            ['key' => 'aspect_ratio', 'type' => 'select', 'label' => 'Aspect ratio', 'default' => 'auto', 'options' => ['auto' => 'Auto', 'square' => 'Square (1:1)', '16-9' => 'Wide (16:9)', '4-3' => 'Standard (4:3)']],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Gallery caption', 'placeholder' => 'Overall caption (optional)'],
            ['key' => 'show_captions', 'type' => 'toggle', 'label' => 'Per-image captions', 'default' => false],
            ['key' => 'lightbox_enabled', 'type' => 'toggle', 'label' => 'Lightbox on click', 'default' => true],
            ['key' => 'autoplay', 'type' => 'toggle', 'label' => 'Autoplay carousel', 'default' => false],
        ],
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
        'fields' => [
            ['key' => 'buttons', 'type' => 'repeater', 'label' => 'Buttons', 'itemLabel' => 'Button', 'max' => 6, 'fields' => [
                ['key' => 'text', 'type' => 'text', 'label' => 'Text', 'maxlength' => 100, 'placeholder' => 'Explore Tours'],
                ['key' => 'url', 'type' => 'text', 'label' => 'URL', 'placeholder' => '/products'],
                ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'primary', 'options' => ['primary' => 'Primary', 'secondary' => 'Secondary', 'link' => 'Text link']],
            ]],
            ['key' => 'alignment', 'type' => 'select', 'label' => 'Alignment', 'default' => 'left', 'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
        ],
    ],
    'stats' => [
        'label' => 'Stats', 'icon' => 'chart-bar', 'category' => 'content',
        'description' => 'Trust signals and key figures displayed in a responsive grid.',
        'keywords' => ['numbers', 'counter', 'facts'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => true, 'children' => false],
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section Heading', 'maxlength' => 255, 'placeholder' => 'Why travel with us'],
            ['key' => 'alignment', 'type' => 'select', 'label' => 'Alignment', 'default' => 'center', 'options' => ['left' => 'Left', 'center' => 'Center']],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Statistics', 'itemLabel' => 'Stat', 'max' => 8, 'fields' => [
                ['key' => 'value', 'type' => 'text', 'label' => 'Value', 'maxlength' => 50, 'placeholder' => '10+'],
                ['key' => 'label', 'type' => 'text', 'label' => 'Label', 'maxlength' => 100, 'placeholder' => 'Years of experience'],
                ['key' => 'description', 'type' => 'text', 'label' => 'Description', 'maxlength' => 500, 'placeholder' => 'Optional supporting detail'],
            ]],
        ],
    ],
    'tour_itinerary' => [
        'label' => 'Tour Itinerary', 'icon' => 'route', 'category' => 'travel',
        'description' => 'Day-by-day or step-by-step travel itinerary timeline.',
        'keywords' => ['timeline', 'schedule', 'trip'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section Heading', 'maxlength' => 255, 'placeholder' => 'Your itinerary'],
            ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introduction', 'rows' => 2, 'maxlength' => 1000, 'placeholder' => 'Optional overview'],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Timeline Items', 'itemLabel' => 'Step', 'max' => 30, 'fields' => [
                ['key' => 'marker', 'type' => 'text', 'label' => 'Marker', 'maxlength' => 50, 'placeholder' => 'Day 1'],
                ['key' => 'title', 'type' => 'text', 'label' => 'Title', 'maxlength' => 255, 'placeholder' => 'Arrival and resort check-in'],
                ['key' => 'description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3, 'maxlength' => 3000, 'placeholder' => 'Describe this part of the journey'],
            ]],
        ],
    ],
    'pricing_table' => [
        'label' => 'Pricing Table', 'icon' => 'tags', 'category' => 'conversion',
        'description' => 'Comparable package prices and included features.',
        'keywords' => ['price', 'package', 'plan'],
        'supports' => ['background' => true, 'spacing' => true, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section Heading', 'maxlength' => 255, 'placeholder' => 'Choose your package'],
            ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introduction', 'rows' => 2, 'maxlength' => 1000, 'placeholder' => 'Optional pricing context'],
            ['key' => 'plans', 'type' => 'repeater', 'label' => 'Plans', 'itemLabel' => 'Plan', 'max' => 6, 'fields' => [
                ['key' => 'name', 'type' => 'text', 'label' => 'Plan Name', 'maxlength' => 150, 'placeholder' => 'Island Explorer'],
                ['key' => 'currency', 'type' => 'text', 'label' => 'Currency', 'default' => 'IDR', 'maxlength' => 10],
                ['key' => 'price', 'type' => 'text', 'label' => 'Price', 'maxlength' => 50, 'placeholder' => '1,500,000'],
                ['key' => 'period', 'type' => 'text', 'label' => 'Period', 'default' => 'per person', 'maxlength' => 50],
                ['key' => 'features', 'type' => 'list', 'label' => 'Features', 'itemLabel' => 'feature', 'max' => 20, 'placeholder' => 'Hotel transfer included'],
                ['key' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'maxlength' => 100, 'placeholder' => 'Book now'],
                ['key' => 'button_url', 'type' => 'text', 'label' => 'Button URL', 'placeholder' => '/contact'],
                ['key' => 'featured', 'type' => 'toggle', 'label' => 'Highlight this plan', 'default' => false],
            ]],
        ],
    ],
    'cta' => [
        'label' => 'Call to Action', 'icon' => 'speakerphone', 'category' => 'conversion',
        'description' => 'Prominent call-to-action section with supporting copy.',
        'keywords' => ['banner', 'button', 'conversion'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'title', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Title', 'placeholder' => 'CTA headline'],
            ['key' => 'description', 'type' => 'textarea', 'inline' => 'plaintext', 'label' => 'Description', 'rows' => 3, 'placeholder' => 'Supporting text'],
            ['key' => 'button_text', 'type' => 'text', 'inline' => 'plaintext', 'label' => 'Button Text', 'placeholder' => 'e.g. Book Now'],
            ['key' => 'button_url', 'type' => 'text', 'label' => 'Button URL', 'placeholder' => '/products'],
            ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'dark', 'options' => ['dark' => 'Dark (black background)', 'light' => 'Light (white background)', 'gold' => 'Gold accent']],
        ],
    ],
    'products_grid' => [
        'label' => 'Products Grid', 'icon' => 'view-grid', 'category' => 'travel',
        'description' => 'Dynamic product selection filtered by category or destination.',
        'keywords' => ['tour', 'activity', 'product'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'category_id', 'type' => 'select', 'label' => 'Filter by Category', 'optionsFrom' => 'categories', 'emptyLabel' => 'All categories'],
            ['key' => 'destination_id', 'type' => 'select', 'label' => 'Filter by Destination', 'optionsFrom' => 'destinations', 'emptyLabel' => 'All destinations'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Number of Products', 'default' => 6, 'min' => 3, 'max' => 12, 'help' => 'Between 3 and 12.'],
            ['key' => 'show_price', 'type' => 'toggle', 'label' => 'Show price on product cards', 'default' => true],
        ],
    ],
    'content_query' => [
        'label' => 'Content Query', 'icon' => 'view-grid', 'category' => 'content',
        'description' => 'Dynamic list of content entries from a chosen content type (e.g. latest blog posts).',
        'keywords' => ['entries', 'list', 'dynamic', 'collection', 'loop'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'content_type', 'type' => 'select', 'label' => 'Content Type', 'optionsFrom' => 'content_types', 'emptyLabel' => '— Select a content type —'],
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section Heading', 'placeholder' => 'e.g. Latest Posts'],
            ['key' => 'orderby', 'type' => 'select', 'label' => 'Order by', 'default' => 'newest', 'options' => ['newest' => 'Newest first', 'oldest' => 'Oldest first', 'title' => 'Title (A–Z)', 'sort_order' => 'Manual sort order']],
            ['key' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '3', 'options' => ['1' => '1 column', '2' => '2 columns', '3' => '3 columns']],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Number of Entries', 'default' => 6, 'min' => 1, 'max' => 24, 'help' => 'Between 1 and 24.'],
            ['key' => 'show_excerpt', 'type' => 'toggle', 'label' => 'Show excerpt', 'default' => true],
        ],
    ],
    'faq' => [
        'label' => 'FAQ', 'icon' => 'question-mark-circle', 'category' => 'content',
        'description' => 'Frequently asked questions from inline content or the FAQ library.',
        'keywords' => ['accordion', 'questions', 'answers'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'source', 'type' => 'select', 'label' => 'FAQ Source', 'default' => 'inline', 'options' => ['inline' => 'Inline (write Q&A here)', 'ids' => 'From FAQ library (select by ID)']],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Questions & Answers', 'itemLabel' => 'Q&A', 'showIf' => ['key' => 'source', 'value' => 'inline'], 'fields' => [
                ['key' => 'question', 'type' => 'text', 'label' => 'Question', 'placeholder' => 'Question'],
                ['key' => 'answer', 'type' => 'textarea', 'label' => 'Answer', 'rows' => 2, 'placeholder' => 'Answer'],
            ]],
            ['key' => 'faq_ids', 'type' => 'text', 'label' => 'FAQ IDs (comma-separated)', 'showIf' => ['key' => 'source', 'value' => 'ids'], 'placeholder' => 'e.g. 1,3,5', 'help' => 'Enter IDs from your FAQ library separated by commas.'],
        ],
    ],
    'testimonials' => [
        'label' => 'Testimonials', 'icon' => 'chat-alt-2', 'category' => 'travel',
        'description' => 'Guest quotes with ratings and optional avatars.',
        'keywords' => ['reviews', 'rating', 'quote'],
        'supports' => ['background' => true, 'spacing' => false, 'alignment' => false, 'children' => false],
        'fields' => [
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Testimonials', 'itemLabel' => 'Testimonial', 'fields' => [
                ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'placeholder' => 'Name'],
                ['key' => 'rating', 'type' => 'number', 'label' => 'Rating (1–5)', 'default' => 5, 'min' => 1, 'max' => 5],
                ['key' => 'text', 'type' => 'textarea', 'label' => 'Testimonial text', 'rows' => 2, 'placeholder' => 'Testimonial text'],
                ['key' => 'avatar', 'type' => 'text', 'label' => 'Avatar path (optional)', 'placeholder' => 'Avatar path'],
            ]],
        ],
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
        'fields' => [
            ['key' => 'form_definition_id', 'type' => 'select', 'label' => 'Contact Form', 'optionsFrom' => 'forms', 'emptyLabel' => '— No form selected —'],
            ['key' => 'title', 'type' => 'text', 'label' => 'Section Title', 'placeholder' => 'e.g. Get In Touch'],
            ['key' => 'description', 'type' => 'text', 'label' => 'Description', 'placeholder' => 'Optional subtitle'],
        ],
    ],
];
