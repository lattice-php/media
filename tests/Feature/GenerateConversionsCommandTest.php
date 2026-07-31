<?php
declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Lattice\Media\Jobs\GenerateMediaConversions;
use Lattice\Media\Models\Media;
use Lattice\Media\Tests\Fixtures\TwoConversionMedia;

use function Pest\Laravel\artisan;

/** The queue is sync in tests, so the command's dispatches run inline. */
beforeEach(function (): void {
    Storage::fake('public');
});

function convertibleMedia(string $name = 'source.jpg'): Media
{
    $path = 'media/'.pathinfo($name, PATHINFO_FILENAME).'/original.'.pathinfo($name, PATHINFO_EXTENSION);

    Storage::disk('public')->put($path, (string) UploadedFile::fake()->image($name, 320, 200)->getContent());

    return Media::factory()->create(['path' => $path, 'mime_type' => 'image/jpeg']);
}

test('the command backfills a media that never had its conversions generated', function (): void {
    $media = convertibleMedia();

    artisan('media:conversions')->assertSuccessful();

    $media->refresh();

    expect($media->conversionPath('thumb'))->toBe('media/source/thumb.webp')
        ->and(Storage::disk('public')->exists('media/source/thumb.webp'))->toBeTrue()
        ->and($media->width)->toBe(320)
        ->and($media->height)->toBe(200);
});

test('a media that is not convertible is never queued', function (): void {
    Media::factory()->create(['path' => 'media/logo/original.svg', 'mime_type' => 'image/svg+xml']);
    Bus::fake();

    artisan('media:conversions')->assertSuccessful();

    Bus::assertNothingDispatched();
});

test('force deletes the derivative it un-maps, so a renamed output leaves nothing behind', function (): void {
    $media = convertibleMedia();
    $media->mergeMeta(['conversions' => [
        'thumb' => ['path' => 'media/source/thumb.jpg', 'width' => 400, 'height' => 400],
    ]]);
    Storage::disk('public')->put('media/source/thumb.jpg', 'stale');

    artisan('media:conversions --force')->assertSuccessful();

    expect(Storage::disk('public')->exists('media/source/thumb.jpg'))->toBeFalse()
        ->and($media->refresh()->conversionPath('thumb'))->toBe('media/source/thumb.webp')
        ->and(Storage::disk('public')->exists('media/source/thumb.webp'))->toBeTrue();
});

test('force regenerates a derivative whose file was removed behind the map', function (): void {
    $media = convertibleMedia();
    artisan('media:conversions')->assertSuccessful();

    Storage::disk('public')->delete('media/source/thumb.webp');

    artisan('media:conversions')->assertSuccessful();
    expect(Storage::disk('public')->exists('media/source/thumb.webp'))->toBeFalse();

    artisan('media:conversions --force')->assertSuccessful();

    expect(Storage::disk('public')->exists('media/source/thumb.webp'))->toBeTrue()
        ->and($media->refresh()->conversionPath('thumb'))->toBe('media/source/thumb.webp');
});

test('only limits which conversions force drops and rebuilds', function (): void {
    config()->set('media.model', TwoConversionMedia::class);
    $media = convertibleMedia();
    artisan('media:conversions')->assertSuccessful();

    $media->refresh()->mergeMeta(['conversions' => [
        ...$media->conversions(),
        'square' => ['path' => 'media/source/stale.webp', 'width' => 1, 'height' => 1],
    ]]);

    artisan('media:conversions --force --only=thumb')->assertSuccessful();

    expect($media->refresh()->conversionPath('square'))->toBe('media/source/stale.webp')
        ->and($media->conversionPath('thumb'))->toBe('media/source/thumb.webp')
        ->and(Storage::disk('public')->exists('media/source/thumb.webp'))->toBeTrue();
});

test('missing skips a media whose conversions are all present', function (): void {
    $media = convertibleMedia();
    artisan('media:conversions')->assertSuccessful();
    Bus::fake();

    artisan('media:conversions --missing')->assertSuccessful();

    Bus::assertNothingDispatched();

    artisan('media:conversions')->assertSuccessful();
    Bus::assertDispatchedTimes(GenerateMediaConversions::class, 1);
});

test('missing covers a complete map whose dimensions were never recorded', function (): void {
    $media = convertibleMedia();
    artisan('media:conversions')->assertSuccessful();
    $media->refresh();
    $map = $media->conversions();
    $media->mergeMeta(['width' => null, 'height' => null]);

    artisan('media:conversions --missing')->assertSuccessful();

    $media->refresh();

    expect($media->width)->toBe(320)
        ->and($media->height)->toBe(200)
        ->and($media->conversions())->toBe($map);
});

test('a media whose stored mime is generic is still reached by the command', function (): void {
    $media = convertibleMedia();
    $media->update(['mime_type' => 'application/octet-stream']);

    artisan('media:conversions')->assertSuccessful();

    expect($media->refresh()->conversionPath('thumb'))->toBe('media/source/thumb.webp');
});

test('ids narrow the run to the given media', function (): void {
    $first = convertibleMedia('first.jpg');
    $second = convertibleMedia('second.jpg');

    artisan("media:conversions --id={$first->getKey()}")->assertSuccessful();

    expect($first->refresh()->hasConversion('thumb'))->toBeTrue()
        ->and($second->refresh()->hasConversion('thumb'))->toBeFalse();
});
