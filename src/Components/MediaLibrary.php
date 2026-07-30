<?php
declare(strict_types=1);

namespace Lattice\Media\Components;

use Lattice\Lattice\Actions\Components\Action;
use Lattice\Lattice\Attributes\AsComponent;
use Lattice\Lattice\Tables\Components\Table;
use Lattice\Lattice\Ui\Components\ContainerComponent;
use Lattice\Media\Actions\DeleteMediaAction;
use Lattice\Media\Actions\UpdateMediaAction;
use Lattice\Media\Actions\UploadMediaAction;
use Lattice\Media\Tables\MediaTable;

#[AsComponent('media.library')]
final class MediaLibrary extends ContainerComponent
{
    public bool $picker = false;

    public ?string $accept = null;

    public bool $signed = false;

    public static function make(string $key = 'media-library'): static
    {
        $library = new self($key);
        $library->signed = (bool) config('media.signed_uploads');
        $library->accept = UploadMediaAction::field()->accept;

        return $library->schema([
            Table::use(MediaTable::class),
            Action::use(UploadMediaAction::class)->key('media-upload'),
            Action::use(UpdateMediaAction::class)->key('media-update'),
            Action::use(DeleteMediaAction::class)->key('media-delete'),
        ]);
    }

    public function picker(bool $picker = true): static
    {
        $this->picker = $picker;

        return $this;
    }

    public function accept(string $accept): static
    {
        $this->accept = $accept;

        return $this;
    }
}
