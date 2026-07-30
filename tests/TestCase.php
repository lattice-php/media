<?php
declare(strict_types=1);

namespace Lattice\Media\Tests;

use Bambamboole\LaravelI18Next\I18NextServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Lattice\Lattice\LatticeServiceProvider;
use Lattice\Lattice\Support\Testing\InteractsWithLatticeComponents;
use Lattice\Media\MediaServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Workbench\App\WorkbenchConfig;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithLatticeComponents;
    use RefreshDatabase;
    use WithLaravelMigrations;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        foreach (WorkbenchConfig::lattice() as $key => $value) {
            $app['config']->set($key, $value);
        }
        $app['config']->set('view.paths', [
            ...$app['config']->get('view.paths', []),
            dirname(__DIR__).'/workbench/resources/views',
        ]);

        // The workbench lang namespace is registered by the workbench provider,
        // which the suite boots without; register it here so page/form/table
        // labels resolve to strings instead of raw keys.
        $app->resolving('translation.loader', function ($loader): void {
            $loader->addNamespace('workbench', dirname(__DIR__).'/workbench/lang');
        });
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            I18NextServiceProvider::class,
            LatticeServiceProvider::class,
            MediaServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/workbench/database/migrations');
    }
}
