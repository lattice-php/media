<?php

declare(strict_types=1);

namespace Workbench\App\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Lattice\Lattice\Attributes\AsTable;
use Lattice\Lattice\Tables\Columns\Column;
use Lattice\Lattice\Tables\Columns\ImageColumn;
use Lattice\Lattice\Tables\Columns\TextColumn;
use Lattice\Lattice\Tables\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Lattice\Tables\TableQuery;
use Workbench\App\Models\Product;

/**
 * @extends EloquentTableDefinition<Product>
 */
#[AsTable('workbench.products.media')]
class ProductMediaTable extends EloquentTableDefinition
{
    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            ImageColumn::make('gallery_cover_url')->label(__('workbench.tables.columns.image'))->size(44),
            TextColumn::make('name')->label(__('workbench.tables.columns.name')),
        ];
    }

    /**
     * @return Builder<Product>
     */
    public function builder(TableQuery $query): Builder
    {
        $builder = Product::query()
            ->select(['id', 'name'])
            ->with('galleryMedia')
            ->afterQuery(function (Collection $products): Collection {
                $products->each->append('gallery_cover_url');

                return $products;
            });

        if ($query->sorts === []) {
            $builder->latest('id');
        }

        return $builder;
    }
}
