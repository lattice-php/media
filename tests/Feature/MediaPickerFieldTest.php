<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Lattice\Lattice\Facades\Lattice;
use Lattice\Media\Actions\DeleteMediaAction;
use Lattice\Media\Actions\UpdateMediaAction;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Forms\Components\MediaPicker;
use Lattice\Media\Models\Media;
use Lattice\Media\Rules\AttachableMedia;
use Lattice\Media\Tables\MediaTable;
use Workbench\App\Forms\ProductMediaForm;
use Workbench\App\Models\Product;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Lattice::tables([MediaTable::class]);
    Lattice::actions([UploadMediaAction::class, UpdateMediaAction::class, DeleteMediaAction::class]);
    Lattice::forms([ProductMediaForm::class]);
    actingAs(workbenchTestUser());
});

test('hydrateState resolves stored ids to display descriptors in order', function (): void {
    [$a, $b] = Media::factory()->count(2)->create();

    $field = MediaPicker::make('gallery')->multiple();
    $field->hydrateState([$b->getKey(), $a->getKey()]);

    expect(array_column($field->selected, 'id'))->toBe([$b->getKey(), $a->getKey()])
        ->and($field->selected[0]['name'])->toBe($b->name)
        ->and($field->selected[0]['mime_type'])->toBe($b->mime_type);
});

test('hydrateState drops ids that no longer exist', function (): void {
    $media = Media::factory()->create();

    $field = MediaPicker::make('gallery')->multiple();
    $field->hydrateState([$media->getKey(), 999999]);

    expect(array_column($field->selected, 'id'))->toBe([$media->getKey()]);
});

test('the media picker carries the library as its child schema', function (): void {
    $node = wire(MediaPicker::make('gallery'));

    expect($node['type'])->toBe('field.media-picker')
        ->and($node['props']['multiple'])->toBeFalse()
        ->and($node['schema'][0]['type'])->toBe('media.library')
        ->and($node['schema'][0]['props']['picker'])->toBeTrue();
});

test('the attachable rule rejects unknown ids and accepts existing media', function (): void {
    $fails = fn (mixed $value): bool => Validator::make(
        ['gallery' => $value],
        ['gallery' => [new AttachableMedia]],
    )->fails();

    $media = Media::factory()->create();

    expect($fails($media->getKey()))->toBeFalse()
        ->and($fails(999999))->toBeTrue()
        ->and($fails('not-an-id'))->toBeTrue()
        ->and($fails($media->getKey().'.5'))->toBeTrue();
});

test('a form with a media picker syncs the pivot through HasMedia', function (): void {
    $product = Product::factory()->create();
    $media = Media::factory()->create();

    $this->submitForm(ProductMediaForm::class, [
        'gallery' => [$media->getKey()],
    ], ['product_id' => $product->getKey()])->assertRedirect('/media-picker');

    expect($product->media('gallery')->pluck('media.id')->all())->toBe([$media->getKey()]);
});

test('a form with a media picker rejects an id that is not attachable', function (): void {
    $product = Product::factory()->create();

    $this->submitForm(ProductMediaForm::class, [
        'gallery' => [999999],
    ], ['product_id' => $product->getKey()])->assertInvalid(['gallery.0']);

    expect($product->media('gallery')->count())->toBe(0);
});
