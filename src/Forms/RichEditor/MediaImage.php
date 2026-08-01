<?php
declare(strict_types=1);

namespace Lattice\Media\Forms\RichEditor;

use Lattice\Lattice\Forms\RichEditor\Attributes\AsEditorExtension;
use Lattice\Lattice\Forms\RichEditor\EditorExtension;
use Lattice\Media\Components\MediaLibrary;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Rich-editor extension for inserting library media as images. The document
 * stores `{id, alt, conversion}` per node; URLs are resolved on the way out
 * (prepareDocument) and never persisted, so temporary/signed disk URLs work.
 */
#[AsEditorExtension('media-image')]
class MediaImage extends EditorExtension
{
    /**
     * Conversion names offered in the node's size picker; empty means the
     * original only. Unknown names fall back to the original at render time.
     *
     * @var list<string>
     */
    public array $conversions = [];

    /** The picker dialog the toolbar button renders, as a wire component. */
    public ?MediaLibrary $library = null;

    #[\Override]
    public static function make(): static
    {
        $extension = parent::make();
        $extension->library = MediaLibrary::make('rich-editor-media-library')->picker();

        return $extension;
    }

    public function conversions(string ...$conversions): static
    {
        $this->conversions = array_values($conversions);

        return $this;
    }

    #[\Override]
    public function serverExtensions(): array
    {
        return [new MediaImageNode];
    }

    #[\Override]
    public function ephemeralAttributes(): array
    {
        return ['mediaImage' => ['url', 'width', 'height', 'mediaAlt']];
    }

    #[\Override]
    public function configureSanitizer(HtmlSanitizerConfig $config): HtmlSanitizerConfig
    {
        return $config
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'loading', 'data-media-id', 'data-conversion'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias();
    }
}
