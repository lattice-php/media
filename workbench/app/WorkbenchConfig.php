<?php

declare(strict_types=1);

namespace Workbench\App;

use Workbench\App\Models\User;

/**
 * The config both consumers of the workbench share: the serve/CLI path
 * (WorkbenchServiceProvider) and the test path (tests/TestCase), which boots
 * without the workbench provider to keep the suite lean.
 */
final class WorkbenchConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function lattice(): array
    {
        return [
            'lattice.discover' => [__DIR__],
            'lattice.actions.middleware' => ['web'],
            'lattice.bulk-actions.middleware' => ['web'],
            'lattice.forms.middleware' => ['web'],
            'lattice.tables.middleware' => ['web'],
            'lattice.i18n.locales' => ['en', 'de'],
            'lattice.i18n.preload_locales' => ['en', 'de'],
            // Namespaced + nested output matches the frontend's namespace and key paths.
            'i18next.namespaces' => true,
            'i18next.output' => 'nested',
            'auth.providers.users.model' => User::class,
        ];
    }
}
