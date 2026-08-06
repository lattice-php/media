<?php
declare(strict_types=1);

namespace Workbench\App\Layouts;

use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsLayout;
use Lattice\Ui\PageSchema;
use Lattice\Layouts\Components\Menu;
use Lattice\Layouts\Components\MenuItem;
use Lattice\Layouts\Components\Outlet;
use Lattice\Layouts\Components\Sidebar;
use Lattice\Layouts\LayoutDefinition;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\StackDirection;
use Lattice\Ui\Enums\Width;
use Workbench\App\Pages\MediaLibraryPage;
use Workbench\App\Pages\ProductMediaPage;

#[AsLayout('app')]
final class AppLayout extends LayoutDefinition
{
    public function schema(PageSchema $schema, Request $request): PageSchema
    {
        return $schema->schema([
            Stack::make('app-shell')
                ->direction(StackDirection::Row)
                ->gap(Gap::None)
                ->schema([
                    Sidebar::make('app-sidebar')->items([
                        Menu::make('sidebar')->items([
                            MenuItem::fromPage(MediaLibraryPage::class)->key('media')->label(__('workbench.navigation.media')),
                            MenuItem::fromPage(ProductMediaPage::class)->key('media-picker')->label(__('workbench.navigation.media-picker')),
                        ]),
                    ]),
                    Stack::make('app-main')
                        ->width(Width::Fill)
                        ->schema([Outlet::make()]),
                ]),
        ]);
    }
}
