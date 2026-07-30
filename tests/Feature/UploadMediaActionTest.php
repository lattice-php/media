<?php
declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lattice\Lattice\Facades\Lattice;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Models\Media;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('public');
    Lattice::actions([UploadMediaAction::class]);
    actingAs(workbenchTestUser());
});

test('multipart uploads create media rows on the configured disk', function (): void {
    $this->callAction(UploadMediaAction::class, [
        'files' => [UploadedFile::fake()->image('team.jpg', 100, 100)],
    ])->assertOk();

    $media = Media::query()->sole();

    expect($media->name)->toBe('team.jpg')
        ->and($media->disk)->toBe('public')
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->uploaded_by)->toBe(auth()->id())
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue();
});

test('uploads outside the configured accepted types are rejected', function (): void {
    config()->set('media.accepted_types', ['image/*']);

    $this->callAction(UploadMediaAction::class, [
        'files' => [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')],
    ])->assertStatus(422);

    expect(Media::query()->count())->toBe(0);

    $this->callAction(UploadMediaAction::class, [
        'files' => [UploadedFile::fake()->image('team.jpg')],
    ])->assertOk();

    expect(Media::query()->count())->toBe(1);
});

test('signed temp keys are finalized out of the temp prefix', function (): void {
    config()->set('media.signed_uploads', true);
    Storage::disk('public')->put('tmp/abc123.jpg', 'bytes');

    $this->callAction(UploadMediaAction::class, ['files' => ['tmp/abc123.jpg']])
        ->assertOk();

    $media = Media::query()->sole();

    expect($media->path)->toStartWith('media/')
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue()
        ->and(Storage::disk('public')->exists('tmp/abc123.jpg'))->toBeFalse();
});

test('signed uploads fall back to the default mime type when the disk cannot resolve it', function (): void {
    config()->set('media.signed_uploads', true);
    $realDisk = Storage::disk('public');
    $realDisk->put('tmp/broken.bin', 'bytes');

    Storage::set('public', new class($realDisk->getDriver(), $realDisk->getAdapter(), $realDisk->getConfig()) extends FilesystemAdapter
    {
        /**
         * @param  string  $path
         */
        #[Override]
        public function mimeType($path): string|false
        {
            return false;
        }
    });

    $this->callAction(UploadMediaAction::class, ['files' => ['tmp/broken.bin']])->assertOk();

    $media = Media::query()->sole();

    expect($media->mime_type)->toBe('application/octet-stream')
        ->and($realDisk->exists($media->path))->toBeTrue();
});

test('guests cannot upload', function (): void {
    auth()->logout();

    $this->callDeniedAction(UploadMediaAction::class, [
        'files' => [UploadedFile::fake()->image('x.jpg')],
    ])->assertForbidden();
});
