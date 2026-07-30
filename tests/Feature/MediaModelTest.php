<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Lattice\Media\Models\Media;

test('url falls back from temporary url to public url', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['path' => 'media/a.jpg']);

    expect($media->url())->toContain('media/a.jpg');
});

test('isImage is derived from the mime type', function (): void {
    expect(Media::factory()->create()->isImage())->toBeTrue()
        ->and(Media::factory()->document()->create()->isImage())->toBeFalse();
});

test('deleting media removes the disk object and cascades attachments', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create();
    Storage::disk('public')->put($media->path, 'bytes');

    DB::table('media_attachments')->insert([
        'media_id' => $media->getKey(),
        'attachable_type' => 'demo',
        'attachable_id' => 1,
        'collection' => 'default',
        'sort_order' => 1,
    ]);

    $media->delete();

    expect(Storage::disk('public')->exists($media->path))->toBeFalse()
        ->and(DB::table('media_attachments')->count())->toBe(0);
});

test('attachments are cascaded even when the disk cannot be reached', function (): void {
    $media = Media::factory()->create();

    DB::table('media_attachments')->insert([
        'media_id' => $media->getKey(),
        'attachable_type' => 'demo',
        'attachable_id' => 1,
        'collection' => 'default',
        'sort_order' => 1,
    ]);

    Storage::shouldReceive('disk')->andThrow(new RuntimeException('disk unavailable'));

    expect(fn (): bool => (bool) $media->delete())->toThrow(RuntimeException::class)
        ->and(DB::table('media_attachments')->count())->toBe(0);
});
