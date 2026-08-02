<?php

declare(strict_types=1);

namespace Workbench\App\Seeders;

use Illuminate\Database\Seeder;
use Lattice\Media\Models\Media;
use Workbench\App\Models\Product;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()->firstOrCreate(['name' => __('workbench.pages.product-media.product')]);

        $images = Media::factory()->count(6)->create();
        Media::factory()->document()->count(2)->create();

        $product->syncMedia($images->take(2)->pluck('id')->all(), 'gallery');
    }
}
