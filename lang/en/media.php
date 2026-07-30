<?php

declare(strict_types=1);

return [
    'library' => [
        'empty' => 'No media yet. Drop files anywhere to upload.',
        'no-results' => 'No media matches your search.',
        'search' => 'Search media',
        'select' => 'Select :name',
        'selected' => ':count selected',
        'upload-failed' => 'Upload failed',
    ],
    'detail' => [
        'download' => 'Download',
        'save' => 'Save',
    ],
    'picker' => [
        'confirm' => 'Select :count item(s)',
        'heading' => 'Choose media',
        'open' => 'Choose from library',
        'remove' => 'Remove :name',
        'selected-of-max' => ':count/:max selected',
    ],
    'validation' => [
        'not-attachable' => 'The selected file is not available.',
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
            'all' => 'All types',
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
        'update' => [
            'label' => 'Edit',
            'toast' => 'File updated',
        ],
        'delete' => [
            'confirm-description' => 'This file is attached to :count record(s). Deleting removes it everywhere.',
            'confirm-title' => 'Delete this file?',
            'label' => 'Delete',
            'toast' => 'File deleted',
        ],
        'delete-selected' => [
            'label' => 'Delete selected',
            'toast' => ':count file(s) deleted',
        ],
    ],
];
