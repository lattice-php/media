<?php

declare(strict_types=1);

use Lattice\Media\Models\Media;

return [
    /**
     * Multi-tenancy: register a resolver in a service provider —
     * `Media::resolveTenantUsing(fn () => auth()->user()?->tenant_id);`
     * — to scope every media query, stamp new rows, and prefix storage
     * paths per tenant. The base migration ships a nullable indexed
     * `tenant_id` string column on `media`; pass `column:` to point at a
     * different one you add yourself.
     */

    /** Subclass `Media` and point this at your class to override `defaultConversions()`. */
    'model' => Media::class,

    'disk' => env('LATTICE_MEDIA_DISK', 'public'),
    'max_size' => env('LATTICE_MEDIA_MAX_SIZE', 10240),

    /** Mime patterns, `image/*` wildcards included; empty accepts every type. */
    'accepted_types' => [],
    'signed_uploads' => env('LATTICE_MEDIA_SIGNED_UPLOADS', false),

    /** Queue for conversion jobs; null uses the default queue. */
    'queue' => null,
];
