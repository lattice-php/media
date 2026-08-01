<?php

declare(strict_types=1);

return [
    'navigation' => [
        'media' => 'Media',
        'media-picker' => 'Media picker',
    ],

    'pages' => [
        'product-media' => [
            'title' => 'Media picker',
            'heading' => 'Product gallery',
            'submit' => 'Save gallery',
            'product' => 'Media demo product',
        ],
    ],

    'forms' => [
        'product-media' => [
            'fields' => [
                'gallery' => 'Gallery',
                'gallery-help-text' => 'Pick images from the media library.',
                'caption' => 'Caption',
                'body' => 'Article body',
            ],
        ],
    ],

    'tables' => [
        'columns' => [
            'image' => 'Image',
            'name' => 'Name',
        ],
    ],
];
