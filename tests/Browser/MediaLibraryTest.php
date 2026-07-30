<?php
declare(strict_types=1);

use Lattice\Media\Models\Media;

it('renders the media grid and narrows it through the search box', function (): void {
    Media::factory()->create(['name' => 'alpha.jpg']);
    Media::factory()->create(['name' => 'beta.jpg']);

    $page = $this->visitAsWorkbenchUser('/media')
        ->assertPresent('@media-library')
        ->assertSee('alpha.jpg')
        ->assertSee('beta.jpg');

    $page->fill('@media-search', 'alpha');

    assertDontSeeEventually($page, 'beta.jpg');

    $page->assertSee('alpha.jpg')
        ->assertNoSmoke();
});

it('uploads a file through the dropzone input', function (): void {
    $page = $this->visitAsWorkbenchUser('/media')
        ->assertPresent('@media-upload-button');

    $page->attach('@media-upload-input', __DIR__.'/fixtures/avatar.jpg');

    // The plugin's in-process server drops uploaded files (LaravelHttpServer
    // builds the kernel request with an empty files array), so the action stores
    // nothing — UploadMediaActionTest covers the server-side store. The toast is
    // client-side and counts accepted requests, so reaching it with one file
    // proves the client transport ran.
    assertSeeEventually($page, '1 file(s) uploaded');

    $page->assertNoSmoke();
});

it('edits alt text and deletes a file from the detail slideout', function (): void {
    $media = Media::factory()->create(['name' => 'detail.jpg']);

    $page = $this->visitAsWorkbenchUser('/media')->assertSee('detail.jpg');

    $page->click('@media-card')
        ->fill('@media-detail-alt', 'Alt text here')
        ->click('@media-detail-save');

    retryUntil(function () use ($media): void {
        expect($media->fresh()->alt)->toBe('Alt text here');
    });

    $page->click('@media-card')
        ->click('@media-detail-delete')
        ->click('@confirm-accept');

    retryUntil(function () use ($media): void {
        expect($media->fresh())->toBeNull();
    });

    $page->assertNoSmoke();
});

it('bulk deletes the selected media', function (): void {
    Media::factory()->create(['name' => 'doomed.jpg']);

    $page = $this->visitAsWorkbenchUser('/media')->assertSee('doomed.jpg');

    $page->click('@media-card-select')
        ->click('@media-bulk-delete');

    assertDontSeeEventually($page, 'doomed.jpg');

    retryUntil(function (): void {
        expect(Media::query()->count())->toBe(0);
    });

    $page->assertNoSmoke();
});
