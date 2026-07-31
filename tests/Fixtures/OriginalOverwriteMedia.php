<?php
declare(strict_types=1);

namespace Lattice\Media\Tests\Fixtures;

use Closure;
use Illuminate\Image\Image;
use Lattice\Media\Models\Media;

/** A conversion keyed `original`, so the overwrite guard has something to catch. */
final class OriginalOverwriteMedia extends Media
{
    /**
     * @return array<string, Closure(Image): Image>
     */
    #[\Override]
    public function defaultConversions(): array
    {
        return [
            'original' => fn (Image $image): Image => $image->cover(50, 50),
        ];
    }
}
