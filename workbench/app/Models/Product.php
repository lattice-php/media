<?php
declare(strict_types=1);

namespace Workbench\App\Models;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Image\Image;
use Lattice\Media\Models\Attachment;
use Lattice\Media\Models\Concerns\HasMedia;
use Lattice\Media\Models\Media;
use Workbench\App\Factories\ProductFactory;

/**
 * @property string $name
 * @property-read string|null $gallery_cover_url
 * @property-read Collection<int, Media> $galleryMedia
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasMedia;
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['name'];

    /** @return MorphToMany<Media, $this, Attachment> */
    public function galleryMedia(): MorphToMany
    {
        return $this->media('gallery');
    }

    /**
     * @return array<array-key, string|Closure(Image): Image>
     */
    public function mediaConversions(string $collection): array
    {
        return $collection === 'gallery'
            ? ['card' => fn (Image $image): Image => $image->cover(1200, 800)->optimize('webp', 75)]
            : [];
    }

    public function getGalleryCoverUrlAttribute(): ?string
    {
        return $this->galleryMedia->first()?->url();
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
