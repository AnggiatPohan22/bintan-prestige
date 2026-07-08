<?php

/*
|--------------------------------------------------------------------------
| Field Type catalog  (Phase 6 — Content Modeling)
|--------------------------------------------------------------------------
| Mirrors the convention of config/blocks.php.  Each entry declares the
| metadata and settings that the admin field-group builder and the
| field-rendering engine (B2 / B3) consume.
|
| Keys per type
|   label            — human-readable name shown in the admin UI
|   icon             — Heroicons icon name (same convention as blocks)
|   category         — basic | choice | date_time | media | relational | advanced
|   description      — one-line explanation for the admin type-picker
|   cast             — PHP-side storage cast  (string|integer|float|boolean|array)
|   is_filterable    — true = value projected to content_entry_index on save
|   sanitizer        — 'plaintext' | 'richtext' | null  (InlineContentSanitizer)
|   settings_schema  — sub-fields an editor can configure per field instance
|                      (same array format as `fields` in config/blocks.php)
|   validation_rules — base Laravel rules; {setting_key} = resolved at B2
|   admin_partial    — Blade path for the input rendered in the admin form (B3)
|   render_partial   — Blade path for the public render (B11)
|
| Read this catalog only via FieldTypeRegistry::all() / ::get() so it goes
| through config-cache and is never queried.
*/

return [

    // ------------------------------------------------------------------ basic

    'text' => [
        'label'       => 'Text',
        'icon'        => 'pencil',
        'category'    => 'basic',
        'description' => 'Single-line text input.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => 'plaintext',
        'settings_schema' => [
            ['key' => 'placeholder', 'type' => 'text',   'label' => 'Placeholder'],
            ['key' => 'maxlength',   'type' => 'number', 'label' => 'Max Length', 'default' => 255, 'min' => 1, 'max' => 65535],
        ],
        'validation_rules' => ['nullable', 'string', 'max:{maxlength}'],
        'admin_partial'  => 'fields.text',
        'render_partial' => 'content.fields.text',
    ],

    'textarea' => [
        'label'       => 'Textarea',
        'icon'        => 'menu-alt-2',
        'category'    => 'basic',
        'description' => 'Multi-line plain text area.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => 'plaintext',
        'settings_schema' => [
            ['key' => 'placeholder', 'type' => 'text',   'label' => 'Placeholder'],
            ['key' => 'maxlength',   'type' => 'number', 'label' => 'Max Length', 'default' => 5000, 'min' => 1, 'max' => 65535],
            ['key' => 'rows',        'type' => 'number', 'label' => 'Visible Rows', 'default' => 4, 'min' => 2, 'max' => 30],
        ],
        'validation_rules' => ['nullable', 'string', 'max:{maxlength}'],
        'admin_partial'  => 'fields.textarea',
        'render_partial' => 'content.fields.textarea',
    ],

    'richtext' => [
        'label'       => 'Rich Text',
        'icon'        => 'document-text',
        'category'    => 'basic',
        'description' => 'HTML rich text editor. Sanitized via InlineContentSanitizer::richtext() on save.',
        'cast'        => 'string',
        'is_filterable' => false,
        'sanitizer'   => 'richtext',
        'settings_schema' => [
            ['key' => 'rows', 'type' => 'number', 'label' => 'Editor Rows', 'default' => 8, 'min' => 4, 'max' => 40],
        ],
        'validation_rules' => ['nullable', 'string'],
        'admin_partial'  => 'fields.richtext',
        'render_partial' => 'content.fields.richtext',
    ],

    'number' => [
        'label'       => 'Number',
        'icon'        => 'hashtag',
        'category'    => 'basic',
        'description' => 'Integer or decimal numeric input.',
        'cast'        => 'float',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'min',         'type' => 'number', 'label' => 'Minimum'],
            ['key' => 'max',         'type' => 'number', 'label' => 'Maximum'],
            ['key' => 'step',        'type' => 'number', 'label' => 'Step', 'default' => 1],
            ['key' => 'prefix',      'type' => 'text',   'label' => 'Prefix (e.g. $)'],
            ['key' => 'suffix',      'type' => 'text',   'label' => 'Suffix (e.g. kg)'],
            ['key' => 'placeholder', 'type' => 'text',   'label' => 'Placeholder'],
        ],
        'validation_rules' => ['nullable', 'numeric', 'min:{min}', 'max:{max}'],
        'admin_partial'  => 'fields.number',
        'render_partial' => 'content.fields.number',
    ],

    'email' => [
        'label'       => 'Email',
        'icon'        => 'at-symbol',
        'category'    => 'basic',
        'description' => 'Email address input with format validation.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => 'plaintext',
        'settings_schema' => [
            ['key' => 'placeholder', 'type' => 'text', 'label' => 'Placeholder'],
        ],
        'validation_rules' => ['nullable', 'string', 'email:rfc,dns', 'max:255'],
        'admin_partial'  => 'fields.email',
        'render_partial' => 'content.fields.email',
    ],

    'url' => [
        'label'       => 'URL',
        'icon'        => 'link',
        'category'    => 'basic',
        'description' => 'URL input with http/https scheme validation.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => 'plaintext',
        'settings_schema' => [
            ['key' => 'placeholder',      'type' => 'text',   'label' => 'Placeholder'],
            ['key' => 'open_in_new_tab',  'type' => 'toggle', 'label' => 'Open in new tab', 'default' => false],
        ],
        'validation_rules' => ['nullable', 'url', 'max:2048'],
        'admin_partial'  => 'fields.url',
        'render_partial' => 'content.fields.url',
    ],

    // ----------------------------------------------------------------- choice

    'toggle' => [
        'label'       => 'Toggle',
        'icon'        => 'switch-horizontal',
        'category'    => 'choice',
        'description' => 'Boolean on/off toggle.',
        'cast'        => 'boolean',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'on_label',  'type' => 'text',   'label' => '"On" label',  'default' => 'Yes'],
            ['key' => 'off_label', 'type' => 'text',   'label' => '"Off" label', 'default' => 'No'],
            ['key' => 'default',   'type' => 'toggle', 'label' => 'Default value', 'default' => false],
        ],
        'validation_rules' => ['nullable', 'boolean'],
        'admin_partial'  => 'fields.toggle',
        'render_partial' => 'content.fields.toggle',
    ],

    'select' => [
        'label'       => 'Select',
        'icon'        => 'selector',
        'category'    => 'choice',
        'description' => 'Dropdown with a fixed list of options; supports single or multiple selection.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'multiple',    'type' => 'toggle',   'label' => 'Allow multiple selections', 'default' => false],
            ['key' => 'placeholder', 'type' => 'text',     'label' => 'Placeholder', 'default' => '— Select —'],
            ['key' => 'choices',     'type' => 'repeater', 'label' => 'Choices', 'itemLabel' => 'Choice', 'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                ['key' => 'value', 'type' => 'text', 'label' => 'Value'],
            ]],
        ],
        'validation_rules' => ['nullable', 'string'],
        'admin_partial'  => 'fields.select',
        'render_partial' => 'content.fields.select',
    ],

    'radio' => [
        'label'       => 'Radio',
        'icon'        => 'check-circle',
        'category'    => 'choice',
        'description' => 'Radio buttons for single choice from a fixed list.',
        'cast'        => 'string',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'layout',  'type' => 'select', 'label' => 'Layout', 'default' => 'vertical', 'options' => ['vertical' => 'Vertical', 'horizontal' => 'Horizontal']],
            ['key' => 'choices', 'type' => 'repeater', 'label' => 'Choices', 'itemLabel' => 'Choice', 'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                ['key' => 'value', 'type' => 'text', 'label' => 'Value'],
            ]],
        ],
        'validation_rules' => ['nullable', 'string'],
        'admin_partial'  => 'fields.radio',
        'render_partial' => 'content.fields.radio',
    ],

    'checkbox' => [
        'label'       => 'Checkbox',
        'icon'        => 'check-square',
        'category'    => 'choice',
        'description' => 'Checkbox group allowing zero or more selections.',
        'cast'        => 'array',
        'is_filterable' => false,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'min_selections', 'type' => 'number', 'label' => 'Min selections', 'min' => 0],
            ['key' => 'max_selections', 'type' => 'number', 'label' => 'Max selections'],
            ['key' => 'choices',        'type' => 'repeater', 'label' => 'Choices', 'itemLabel' => 'Choice', 'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                ['key' => 'value', 'type' => 'text', 'label' => 'Value'],
            ]],
        ],
        'validation_rules' => ['nullable', 'array'],
        'admin_partial'  => 'fields.checkbox',
        'render_partial' => 'content.fields.checkbox',
    ],

    // --------------------------------------------------------------- date_time

    'date' => [
        'label'       => 'Date',
        'icon'        => 'calendar',
        'category'    => 'date_time',
        'description' => 'Date picker stored as YYYY-MM-DD.',
        'cast'        => 'date',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'display_format', 'type' => 'text', 'label' => 'Display Format', 'default' => 'd M Y', 'help' => 'PHP date() format string.'],
            ['key' => 'min_date',       'type' => 'text', 'label' => 'Earliest date (YYYY-MM-DD)'],
            ['key' => 'max_date',       'type' => 'text', 'label' => 'Latest date (YYYY-MM-DD)'],
        ],
        'validation_rules' => ['nullable', 'date_format:Y-m-d'],
        'admin_partial'  => 'fields.date',
        'render_partial' => 'content.fields.date',
    ],

    'datetime' => [
        'label'       => 'Date & Time',
        'icon'        => 'clock',
        'category'    => 'date_time',
        'description' => 'Date + time picker stored as YYYY-MM-DD HH:MM:SS.',
        'cast'        => 'datetime',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'display_format', 'type' => 'text', 'label' => 'Display Format', 'default' => 'd M Y H:i', 'help' => 'PHP date() format string.'],
            ['key' => 'min_date',       'type' => 'text', 'label' => 'Earliest (YYYY-MM-DD HH:MM)'],
            ['key' => 'max_date',       'type' => 'text', 'label' => 'Latest (YYYY-MM-DD HH:MM)'],
        ],
        'validation_rules' => ['nullable', 'date_format:Y-m-d H:i:s', 'date_format:Y-m-d H:i'],
        'admin_partial'  => 'fields.datetime',
        'render_partial' => 'content.fields.datetime',
    ],

    // ------------------------------------------------------------------ media

    'image' => [
        'label'       => 'Image',
        'icon'        => 'photograph',
        'category'    => 'media',
        'description' => 'Single image selected from the Media Library.',
        'cast'        => 'integer',
        'is_filterable' => false,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'max_size_kb',  'type' => 'number', 'label' => 'Max file size (KB)', 'min' => 1],
            ['key' => 'preview_size', 'type' => 'select', 'label' => 'Preview size', 'default' => 'thumbnail', 'options' => ['thumbnail' => 'Thumbnail', 'medium' => 'Medium', 'full' => 'Full']],
        ],
        'validation_rules' => ['nullable', 'integer', 'exists:media,id'],
        'admin_partial'  => 'fields.image',
        'render_partial' => 'content.fields.image',
    ],

    'gallery' => [
        'label'       => 'Gallery',
        'icon'        => 'collection',
        'category'    => 'media',
        'description' => 'Multiple images selected from the Media Library.',
        'cast'        => 'array',
        'is_filterable' => false,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'min_items', 'type' => 'number', 'label' => 'Min images', 'default' => 0, 'min' => 0],
            ['key' => 'max_items', 'type' => 'number', 'label' => 'Max images', 'default' => 20, 'min' => 1],
        ],
        'validation_rules' => ['nullable', 'array'],
        'admin_partial'  => 'fields.gallery',
        'render_partial' => 'content.fields.gallery',
    ],

    'file' => [
        'label'       => 'File',
        'icon'        => 'paper-clip',
        'category'    => 'media',
        'description' => 'File attachment from the Media Library (any type).',
        'cast'        => 'integer',
        'is_filterable' => false,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'max_size_kb',          'type' => 'number', 'label' => 'Max file size (KB)', 'min' => 1],
            ['key' => 'allowed_extensions',    'type' => 'text',   'label' => 'Allowed extensions', 'placeholder' => 'pdf,docx,xlsx'],
            ['key' => 'show_filename_on_render', 'type' => 'toggle', 'label' => 'Show filename on frontend', 'default' => true],
        ],
        'validation_rules' => ['nullable', 'integer', 'exists:media,id'],
        'admin_partial'  => 'fields.file',
        'render_partial' => 'content.fields.file',
    ],

    // --------------------------------------------------------------- relational

    'relationship' => [
        'label'       => 'Relationship',
        'icon'        => 'external-link',
        'category'    => 'relational',
        'description' => 'Link to one or more content entries of a specific type.',
        'cast'        => 'array',
        'is_filterable' => true,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'content_type_slugs', 'type' => 'text',   'label' => 'Allowed content types (comma-separated slugs)', 'placeholder' => 'blog,hotel'],
            ['key' => 'min',                'type' => 'number', 'label' => 'Min entries', 'default' => 0, 'min' => 0],
            ['key' => 'max',                'type' => 'number', 'label' => 'Max entries', 'default' => 10, 'min' => 1],
            ['key' => 'return_format',      'type' => 'select', 'label' => 'Return format', 'default' => 'id', 'options' => ['id' => 'Entry IDs (array)', 'object' => 'Entry objects (loaded)']],
        ],
        'validation_rules' => ['nullable', 'array', 'min:{min}', 'max:{max}'],
        'admin_partial'  => 'fields.relationship',
        'render_partial' => 'content.fields.relationship',
    ],

    // ---------------------------------------------------------------- advanced

    'color' => [
        'label'       => 'Color',
        'icon'        => 'color-swatch',
        'category'    => 'advanced',
        'description' => 'Color picker stored as a hex string.',
        'cast'        => 'string',
        'is_filterable' => false,
        'sanitizer'   => 'plaintext',
        'settings_schema' => [
            ['key' => 'alpha',   'type' => 'toggle', 'label' => 'Enable alpha (opacity)', 'default' => false],
            ['key' => 'palette', 'type' => 'text',   'label' => 'Preset palette (comma-separated hex values)', 'placeholder' => '#000000,#ffffff'],
        ],
        'validation_rules' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3,8})$/'],
        'admin_partial'  => 'fields.color',
        'render_partial' => 'content.fields.color',
    ],

    'repeater' => [
        'label'       => 'Repeater',
        'icon'        => 'duplicate',
        'category'    => 'advanced',
        'description' => 'Repeatable set of sub-fields (max 1 nesting level). Sub-fields defined in the field group UI.',
        'cast'        => 'array',
        'is_filterable' => false,
        'sanitizer'   => null,
        'settings_schema' => [
            ['key' => 'item_label', 'type' => 'text',   'label' => 'Row label', 'default' => 'Item', 'placeholder' => 'e.g. Feature'],
            ['key' => 'min_rows',   'type' => 'number', 'label' => 'Min rows', 'default' => 0, 'min' => 0],
            ['key' => 'max_rows',   'type' => 'number', 'label' => 'Max rows', 'default' => 20, 'min' => 1],
        ],
        'validation_rules' => ['nullable', 'array', 'max:{max_rows}'],
        'admin_partial'  => 'fields.repeater',
        'render_partial' => 'content.fields.repeater',
    ],

];
