<?php
declare(strict_types=1);

use Lattice\Lattice\Facades\Lattice;
use Lattice\Media\Actions\DeleteMediaAction;
use Lattice\Media\Actions\UpdateMediaAction;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Components\MediaLibrary;
use Lattice\Media\Tables\MediaTable;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Lattice::tables([MediaTable::class]);
    Lattice::actions([UploadMediaAction::class, UpdateMediaAction::class, DeleteMediaAction::class]);
    actingAs(workbenchTestUser());
});

test('the media library composes its table and action nodes', function (): void {

    $node = wire(MediaLibrary::make());

    expect($node['type'])->toBe('media.library')
        ->and($node['props']['picker'])->toBeFalse();

    $types = array_column($node['schema'], 'type');
    expect($types[0])->toBe('table');

    $keys = array_map(fn (array $child): ?string => $child['key'] ?? null, array_slice($node['schema'], 1));
    expect($keys)->toContain('media-upload', 'media-update', 'media-delete');
});

test('the library offers the configured accepted types to the file picker', function (): void {
    config()->set('media.accepted_types', ['image/*', 'application/pdf']);

    expect(wire(MediaLibrary::make())['props']['accept'])->toBe('image/*,application/pdf');
});
