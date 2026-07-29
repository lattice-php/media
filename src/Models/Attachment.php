<?php
declare(strict_types=1);

namespace Lattice\Media\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

final class Attachment extends MorphPivot
{
    protected $table = 'media_attachments';

    public $timestamps = false;

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
