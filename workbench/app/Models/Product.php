<?php
declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
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

    /** @var list<string> */
    protected $fillable = ['name'];

    /** @return MorphToMany<Media, $this, Attachment> */
    public function galleryMedia(): MorphToMany
    {
        return $this->media('gallery');
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
