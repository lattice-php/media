<?php

declare(strict_types=1);

return [
    'disk' => env('LATTICE_MEDIA_DISK', 'public'),
    'max_size' => env('LATTICE_MEDIA_MAX_SIZE', 10240),
    'signed_uploads' => env('LATTICE_MEDIA_SIGNED_UPLOADS', false),
];
