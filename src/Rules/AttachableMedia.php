<?php
declare(strict_types=1);

namespace Lattice\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Lattice\Media\Models\Media;

final class AttachableMedia implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $media = is_string($value) && Str::isUuid($value) ? Media::modelQuery()->find($value) : null;

        if (! $media instanceof Media || Gate::denies('attach', $media)) {
            $fail(__('media::media.validation.not-attachable'));
        }
    }
}
