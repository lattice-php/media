<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\WorkbenchConfig;

use function Orchestra\Testbench\package_path;

class WorkbenchServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        config(WorkbenchConfig::lattice());

        $this->serveWorkbenchTranslations();
    }

    private function serveWorkbenchTranslations(): void
    {
        $this->callAfterResolving('translation.loader', function ($loader): void {
            $loader->addNamespace('workbench', package_path('workbench/lang'));
        });
    }
}
