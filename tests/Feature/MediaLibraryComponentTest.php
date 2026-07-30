<?php
declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lattice\Lattice\Facades\Lattice;
use Lattice\Media\Actions\DeleteMediaAction;
use Lattice\Media\Actions\UpdateMediaAction;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Components\MediaLibrary;
use Lattice\Media\Models\Media;
use Lattice\Media\Tables\MediaTable;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Lattice::tables([MediaTable::class]);
    Lattice::actions([UploadMediaAction::class, UpdateMediaAction::class, DeleteMediaAction::class]);
    actingAs(workbenchTestUser());
});

/**
 * @param  array<array-key, mixed>  $node
 * @return array<array-key, mixed>
 */
function uploadNode(array $node): array
{
    return (array) collect((array) $node['schema'])->firstWhere('key', 'media-upload');
}

test('the media library composes its table and action nodes', function (): void {

    $node = wire(MediaLibrary::make());

    expect($node['type'])->toBe('media.library')
        ->and($node['props']['picker'])->toBeFalse();

    $types = array_column($node['schema'], 'type');
    expect($types[0])->toBe('table');

    $keys = array_map(fn (array $child): ?string => $child['key'] ?? null, array_slice($node['schema'], 1));
    expect($keys)->toContain('media-upload', 'media-update', 'media-delete');
});

test('pick mode composes only the table and the upload action', function (): void {
    $node = wire(MediaLibrary::make()->picker());

    expect($node['props']['picker'])->toBeTrue()
        ->and($node['schema'])->toHaveCount(2);

    $types = array_column($node['schema'], 'type');
    expect($types[0])->toBe('table');

    $keys = array_map(fn (array $child): ?string => $child['key'] ?? null, array_slice($node['schema'], 1));
    expect($keys)->toBe(['media-upload']);
});

test('switching to pick mode after a render recomposes the children', function (): void {
    $library = MediaLibrary::make();
    wire($library);

    $node = wire($library->picker());

    expect($node['schema'])->toHaveCount(2);

    $keys = array_map(fn (array $child): ?string => $child['key'] ?? null, array_slice($node['schema'], 1));
    expect($keys)->toBe(['media-upload']);
});

test('the library offers the configured accepted types to the file picker', function (): void {
    config()->set('media.accepted_types', ['image/*', 'application/pdf']);

    expect(wire(MediaLibrary::make())['props']['accept'])->toBe('image/*,application/pdf');
});

test('per-instance upload settings reach both the wire prop and the sealed action context', function (): void {
    Storage::fake('uploads');
    Storage::disk('uploads')->put('tmp/team.jpg', 'bytes');

    $node = wire(MediaLibrary::make()->signedUpload()->disk('uploads'));
    $upload = uploadNode($node);

    expect($node['props']['signed'])->toBeTrue();

    $this->postJson($upload['props']['endpoint'], ['files' => ['tmp/team.jpg']], $this->latticeHeaders($upload))
        ->assertOk();

    $media = Media::query()->sole();

    expect($media->disk)->toBe('uploads')
        ->and($media->path)->toStartWith('media/')
        ->and(Storage::disk('uploads')->exists($media->path))->toBeTrue();
});

test('mutating the library after a render reseals the upload context', function (): void {
    Storage::fake('uploads');
    Storage::disk('uploads')->put('tmp/team.jpg', 'bytes');

    $library = MediaLibrary::make();
    wire($library);

    $node = wire($library->signedUpload()->disk('uploads'));
    $upload = uploadNode($node);

    expect($node['props']['signed'])->toBeTrue();

    $this->postJson($upload['props']['endpoint'], ['files' => ['tmp/team.jpg']], $this->latticeHeaders($upload))
        ->assertOk();

    expect(Media::query()->sole()->disk)->toBe('uploads');
});

test('an instance accept tolerates spaces between the mime patterns', function (): void {
    Storage::fake('public');

    $upload = uploadNode(wire(MediaLibrary::make()->accept('image/*, application/pdf')));

    $this->postJson(
        $upload['props']['endpoint'],
        ['files' => [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')]],
        $this->latticeHeaders($upload),
    )->assertOk();

    expect(Media::query()->sole()->name)->toBe('report.pdf');
});

test('an instance accept narrows what the upload endpoint takes', function (): void {
    Storage::fake('public');

    $upload = uploadNode(wire(MediaLibrary::make()->accept('image/*')));

    $this->postJson(
        $upload['props']['endpoint'],
        ['files' => [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')]],
        $this->latticeHeaders($upload),
    )->assertStatus(422);

    expect(Media::query()->count())->toBe(0);
});
