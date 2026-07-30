<?php
declare(strict_types=1);

namespace Lattice\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Lattice\Media\Database\Factories\MediaFactory;
use Throwable;

/**
 * @property string $disk
 * @property string $path
 * @property string $name
 * @property string $mime_type
 * @property int $size
 * @property string|null $alt
 * @property int|null $uploaded_by
 */
final class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $table = 'media';

    protected $guarded = [];

    #[\Override]
    protected static function booted(): void
    {
        self::deleted(function (Media $media): void {
            $media->attachments()->delete();
            Storage::disk($media->disk)->delete($media->path);
        });
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function url(): ?string
    {
        $disk = Storage::disk($this->disk);

        try {
            return $disk->temporaryUrl($this->path, now()->addMinutes((int) config('lattice.files.url_ttl', 5)));
        } catch (Throwable) {
        }

        try {
            return $disk->url($this->path);
        } catch (Throwable) {
            return null;
        }
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(): array
    {
        return [...parent::toArray(), 'url' => $this->url()];
    }

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }
}
