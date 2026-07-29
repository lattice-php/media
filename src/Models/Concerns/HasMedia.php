<?php
declare(strict_types=1);

namespace Lattice\Media\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Lattice\Media\Models\Attachment;
use Lattice\Media\Models\Media;

trait HasMedia
{
    /**
     * @return MorphToMany<Media, $this, Attachment>
     */
    public function media(string $collection = 'default'): MorphToMany
    {
        return $this->morphToMany(Media::class, 'attachable', 'media_attachments')
            ->using(Attachment::class)
            ->wherePivot('collection', $collection)
            ->withPivot(['collection', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    /**
     * @param  array<int, int|string>  $mediaIds
     */
    public function syncMedia(array $mediaIds, string $collection = 'default'): void
    {
        $sync = [];

        foreach (array_values($mediaIds) as $index => $id) {
            $sync[$id] = ['collection' => $collection, 'sort_order' => $index + 1];
        }

        $this->media($collection)->sync($sync);
    }

    public function firstMediaUrl(string $collection = 'default'): ?string
    {
        return $this->media($collection)->first()?->url();
    }
}
