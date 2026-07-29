<?php

declare(strict_types=1);

return [
    'library' => [
        'heading' => 'Media library',
        'empty' => 'No media yet. Drop files anywhere to upload.',
    ],
    'columns' => [
        'preview' => 'Preview',
        'name' => 'Name',
        'type' => 'Type',
        'size' => 'Size',
        'alt' => 'Alt text',
        'uploaded-at' => 'Uploaded',
        'usage' => 'Used',
    ],
    'filters' => [
        'type' => [
            'label' => 'Type',
            'image' => 'Images',
            'video' => 'Video',
            'audio' => 'Audio',
            'document' => 'Documents',
        ],
    ],
    'actions' => [
        'upload' => [
            'label' => 'Upload',
            'toast' => ':count file(s) uploaded',
        ],
    ],
];
