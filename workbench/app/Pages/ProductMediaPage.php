<?php
declare(strict_types=1);

namespace Workbench\App\Pages;

use Lattice\Lattice\Attributes\AsPage;
use Lattice\Lattice\Core\PageSchema;
use Lattice\Lattice\Forms\Components\Form;
use Lattice\Lattice\Tables\Components\Table;
use Lattice\Lattice\Ui\Components\Heading;
use Lattice\Lattice\Ui\Components\Stack;
use Lattice\Lattice\Ui\Enums\Gap;
use Lattice\Lattice\Ui\Enums\HttpMethod;
use Workbench\App\Forms\ProductMediaForm;
use Workbench\App\Models\Product;
use Workbench\App\Tables\ProductMediaTable;

#[AsPage(route: '/media-picker')]
final class ProductMediaPage extends WorkbenchPage
{
    public function title(): string
    {
        return __('workbench.pages.product-media.title');
    }

    public function render(PageSchema $schema): PageSchema
    {
        $product = $this->product();

        return $schema->schema([
            Stack::make('product-media-page')
                ->gap(Gap::Large)
                ->schema([
                    Heading::make(__('workbench.pages.product-media.heading')),
                    Form::use(ProductMediaForm::class, ['product_id' => $product->getKey()])
                        ->method(HttpMethod::Post)
                        ->submitLabel(__('workbench.pages.product-media.submit'))
                        ->fill(['gallery' => $product->mediaPickerValue('gallery')]),
                    Table::use(ProductMediaTable::class),
                ]),
        ]);
    }

    private function product(): Product
    {
        return Product::query()->firstOrCreate(['name' => __('workbench.pages.product-media.product')]);
    }
}
