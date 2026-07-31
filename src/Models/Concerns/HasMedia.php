<?php
declare(strict_types=1);

namespace Lattice\Media\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Image\Image;
use Lattice\Media\Jobs\GenerateMediaConversions;
use Lattice\Media\Models\Attachment;
use Lattice\Media\Models\Media;

trait HasMedia
{
    /**
     * @return MorphToMany<Media, $this, Attachment>
     */
    public function media(string $collection = 'default'): MorphToMany
    {
        return $this->morphToMany(Media::modelClass(), 'attachable', 'media_attachments', null, 'media_id')
            ->using(Attachment::class)
            ->wherePivot('collection', $collection)
            ->withPivot(['collection', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    /**
     * Conversions this collection needs on top of the media's defaults.
     *
     * Override this hook on the consuming model. Keys are conversion names,
     * values are callbacks over an immutable `Image` that must return the
     * transformed image. A bare string entry (`['thumb']`) reuses the
     * globally defined conversion of that name from the media's
     * `defaultConversions()`.
     *
     * Conversion names are a global namespace: one name is one spec
     * everywhere, so two collections that need different sizes must ask for
     * different names.
     *
     * @return array<array-key, string|Closure(Image): Image>
     */
    public function mediaConversions(string $collection): array
    {
        return [];
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

        $attached = $this->media($collection)->sync($sync)['attached'];

        foreach (Media::modelQuery()->findMany($attached) as $media) {
            GenerateMediaConversions::dispatch($media, $this, $collection);
        }
    }

    public function firstMediaUrl(string $collection = 'default'): ?string
    {
        return $this->media($collection)->first()?->url();
    }
}
