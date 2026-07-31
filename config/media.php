<?php

declare(strict_types=1);

use Lattice\Media\Models\Media;

return [
    /**
     * Multi-tenancy: register a resolver in a service provider —
     * `Media::resolveTenantUsing(fn () => auth()->user()?->tenant_id);`
     * — to scope every media query, stamp new rows, and prefix storage
     * paths per tenant. Add the tenant column yourself:
     * `$table->string('tenant_id')->nullable()->index();` on `media`.
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
