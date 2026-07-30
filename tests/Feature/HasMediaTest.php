<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lattice\Media\Models\Media;
use Workbench\App\Models\Product;

test('syncMedia attaches in order and re-sync replaces the set', function (): void {
    $product = Product::factory()->create();
    [$a, $b, $c] = Media::factory()->count(3)->create();

    $product->syncMedia([$b->getKey(), $a->getKey()], 'images');

    expect($product->media('images')->pluck('media.id')->all())
        ->toBe([$b->getKey(), $a->getKey()]);

    $product->syncMedia([$c->getKey()], 'images');

    expect($product->refresh()->media('images')->pluck('media.id')->all())
        ->toBe([$c->getKey()]);
});

test('syncMedia re-indexes a sparse ids array before assigning sort_order', function (): void {
    $product = Product::factory()->create();
    [$a, $b] = Media::factory()->count(2)->create();

    $product->syncMedia([1 => $b->getKey(), 3 => $a->getKey()], 'images');

    expect(
        DB::table('media_attachments')
            ->where('attachable_id', $product->getKey())
            ->where('collection', 'images')
            ->pluck('sort_order', 'media_id')
            ->all()
    )->toBe([$b->getKey() => 1, $a->getKey() => 2]);
});

test('collections are isolated from each other', function (): void {
    $product = Product::factory()->create();
    $image = Media::factory()->create();
    $manual = Media::factory()->document()->create();

    $product->syncMedia([$image->getKey()], 'images');
    $product->syncMedia([$manual->getKey()], 'manuals');

    expect($product->media('images')->count())->toBe(1)
        ->and($product->media('manuals')->count())->toBe(1);

    $product->syncMedia([], 'images');

    expect($product->refresh()->media('manuals')->count())->toBe(1);
});

test('firstMediaUrl returns the first attachment url or null', function (): void {
    Storage::fake('public');
    $product = Product::factory()->create();

    expect($product->firstMediaUrl('images'))->toBeNull();

    $media = Media::factory()->create();
    $product->syncMedia([$media->getKey()], 'images');

    expect($product->refresh()->firstMediaUrl('images'))->toContain($media->path);
});
