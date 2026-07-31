<?php
declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Lattice\Lattice\Facades\Lattice;
use Lattice\Lattice\Tables\Components\Table;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Forms\Components\MediaPicker;
use Lattice\Media\Models\Media;
use Lattice\Media\Rules\AttachableMedia;
use Lattice\Media\Tables\MediaTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

afterEach(fn () => Media::resolveTenantUsing(null));

test('no resolver leaves queries unscoped and rows unstamped', function (): void {
    Media::factory()->count(2)->create();

    expect(Media::modelQuery()->count())->toBe(2)
        ->and(Media::modelQuery()->first()->getAttribute('tenant_id'))->toBeNull();
});

test('queries only return the current tenant and follow resolver switches', function (): void {
    Media::factory()->create(['tenant_id' => 'acme']);
    Media::factory()->create(['tenant_id' => 'globex']);

    $tenant = 'acme';
    Media::resolveTenantUsing(function () use (&$tenant): string {
        return $tenant;
    });

    expect(Media::modelQuery()->pluck('tenant_id')->all())->toBe(['acme']);

    $tenant = 'globex';

    expect(Media::modelQuery()->pluck('tenant_id')->all())->toBe(['globex']);
});

test('creating stamps the tenant column when unset', function (): void {
    Media::resolveTenantUsing(fn (): string => 'acme');

    $stamped = Media::factory()->create();
    $explicit = Media::factory()->create(['tenant_id' => 'globex']);

    expect($stamped->getAttribute('tenant_id'))->toBe('acme')
        ->and($explicit->getAttribute('tenant_id'))->toBe('globex');
});

test('AttachableMedia rejects another tenant id', function (): void {
    $foreign = Media::factory()->create(['tenant_id' => 'globex']);
    Media::resolveTenantUsing(fn (): string => 'acme');

    $fails = Validator::make(
        ['media' => $foreign->getKey()],
        ['media' => [new AttachableMedia]],
    )->fails();

    expect($fails)->toBeTrue();
});

test('picker hydrateState drops another tenant id', function (): void {
    $own = Media::factory()->create(['tenant_id' => 'acme']);
    $foreign = Media::factory()->create(['tenant_id' => 'globex']);
    Media::resolveTenantUsing(fn (): string => 'acme');

    $field = MediaPicker::make('gallery')->multiple();
    $field->hydrateState([$own->getKey(), $foreign->getKey()]);

    expect(array_column($field->selected, 'id'))->toBe([$own->getKey()]);
});

test('the media table listing only returns the current tenant', function (): void {
    Lattice::tables([MediaTable::class]);
    actingAs(workbenchTestUser());

    $own = Media::factory()->create(['tenant_id' => 'acme', 'name' => 'own.jpg']);
    Media::factory()->create(['tenant_id' => 'globex', 'name' => 'foreign.jpg']);
    Media::resolveTenantUsing(fn (): string => 'acme');

    $ref = $this->latticeRef(wire(Table::use(MediaTable::class)));

    getJson('/lattice/tables/media.library', ['X-Lattice-Ref' => $ref])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->getKey());
});

test('uploads store under a tenant-prefixed per-media directory and stamp the row', function (): void {
    Storage::fake('public');
    Lattice::actions([UploadMediaAction::class]);
    actingAs(workbenchTestUser());
    Media::resolveTenantUsing(fn (): string => 'acme');

    $this->callAction(UploadMediaAction::class, [
        'files' => [UploadedFile::fake()->image('team.jpg', 100, 100)],
    ])->assertOk();

    $media = Media::modelQuery()->sole();

    expect($media->path)->toMatch('#^media/acme/[0-9a-f-]{36}/original\.jpg$#')
        ->and($media->getAttribute('tenant_id'))->toBe('acme')
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue();
});
