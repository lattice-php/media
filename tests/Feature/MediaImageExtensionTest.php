<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Lattice\Lattice\Forms\RichContent;
use Lattice\Media\Forms\RichEditor\MediaImage;
use Lattice\Media\Models\Media;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(workbenchTestUser());
});

/**
 * @param  array<string, mixed>  $attrs
 * @return array<string, mixed>
 */
function mediaImageDoc(array $attrs): array
{
    return ['type' => 'doc', 'content' => [['type' => 'mediaImage', 'attrs' => $attrs]]];
}

test('the extension wires its conversions and the picker library component', function (): void {
    $node = wire(MediaImage::make()->conversions('hero', 'wide'));

    expect($node['type'])->toBe('media-image')
        ->and($node['props']['conversions'])->toBe(['hero', 'wide'])
        ->and($node['props']['library']['type'])->toBe('media.library')
        ->and($node['props']['library']['props']['picker'])->toBeTrue()
        ->and(array_column($node['props']['library']['schema'], 'type'))->toContain('table');
});

test('ephemeral attrs are scrubbed from the canonical document', function (): void {
    $document = mediaImageDoc([
        'id' => 7,
        'alt' => 'Override',
        'conversion' => 'hero',
        'url' => 'https://cdn.test/a.jpg',
        'width' => 800,
        'height' => 600,
        'mediaAlt' => 'Library alt',
    ]);

    $canonical = RichContent::make($document, extensions: [MediaImage::make()])->toArray();
    $attrs = $canonical['content'][0]['attrs'];

    expect($canonical['content'][0]['type'])->toBe('mediaImage')
        ->and($attrs['id'])->toBe(7)
        ->and($attrs['alt'])->toBe('Override')
        ->and($attrs['conversion'])->toBe('hero')
        ->and($attrs)->not->toHaveKey('url')
        ->and($attrs)->not->toHaveKey('width')
        ->and($attrs)->not->toHaveKey('height')
        ->and($attrs)->not->toHaveKey('mediaAlt');
});

test('prepareDocument resolves media into ephemeral url, dimensions and alt', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['width' => 800, 'height' => 600, 'alt' => 'A lamp']]);

    $prepared = RichContent::make(mediaImageDoc(['id' => $media->getKey()]), extensions: [MediaImage::make()])
        ->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain($media->path)
        ->and($attrs['width'])->toBe(800)
        ->and($attrs['height'])->toBe(600)
        ->and($attrs['mediaAlt'])->toBe('A lamp');
});

test('a node conversion resolves to the conversion url and dimensions', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => [
        'width' => 800,
        'height' => 600,
        'conversions' => ['hero' => ['path' => 'media/conversions/hero.webp', 'width' => 1200, 'height' => 800]],
    ]]);

    $prepared = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'conversion' => 'hero']),
        extensions: [MediaImage::make()->conversions('hero')],
    )->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain('hero.webp')
        ->and($attrs['width'])->toBe(1200)
        ->and($attrs['height'])->toBe(800);
});

test('an ungenerated conversion falls back to the original url and dimensions', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['width' => 800, 'height' => 600]]);

    $prepared = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'conversion' => 'missing']),
        extensions: [MediaImage::make()],
    )->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain($media->path)
        ->and($attrs['width'])->toBe(800);
});

test('toHtml renders the img with a sanitizer-approved relative url', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['alt' => 'A lamp']]);

    $html = RichContent::make(mediaImageDoc(['id' => $media->getKey()]), extensions: [MediaImage::make()])->toHtml();

    expect($html)->toContain('<img')
        ->toContain($media->path)
        ->toContain('alt="A lamp"')
        ->toContain('data-media-id="'.$media->getKey().'"');
});

test('a node alt override beats the library alt in rendered html', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['alt' => 'Library alt']]);

    $html = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'alt' => 'Override']),
        extensions: [MediaImage::make()],
    )->toHtml();

    expect($html)->toContain('alt="Override"')->not->toContain('Library alt');
});

test('deleted media renders no img', function (): void {
    $html = RichContent::make(mediaImageDoc(['id' => 424242]), extensions: [MediaImage::make()])->toHtml();

    expect($html)->not->toContain('<img');
});
