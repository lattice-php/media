<?php

declare(strict_types=1);

namespace Lattice\Media;

use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media.php', 'media');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Registered directly on the loader (not loadTranslationsFrom): the
        // i18next JSON route resolves only the translation loader, never the
        // translator, so the deferred callback would not fire for it.
        $this->app->make('translation.loader')->addNamespace('media', __DIR__.'/../lang');
    }
}
