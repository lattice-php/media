<?php
declare(strict_types=1);

use Lattice\Lattice\Forms\RichContent;
use Lattice\Media\Forms\RichEditor\MediaImage;

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
