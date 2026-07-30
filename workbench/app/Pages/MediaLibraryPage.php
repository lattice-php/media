<?php
declare(strict_types=1);

namespace Workbench\App\Pages;

use Lattice\Lattice\Attributes\AsPage;
use Lattice\Lattice\Core\PageSchema;
use Lattice\Media\Components\MediaLibrary;

#[AsPage(route: '/media')]
final class MediaLibraryPage extends WorkbenchPage
{
    public function title(): string
    {
        return __('workbench.navigation.media');
    }

    public function render(PageSchema $schema): PageSchema
    {
        $library = MediaLibrary::make();

        if (request()->boolean('signed')) {
            $library->signedUpload()->disk('s3');
        }

        return $schema->schema([$library]);
    }
}
