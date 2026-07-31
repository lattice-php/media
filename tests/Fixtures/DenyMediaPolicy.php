<?php
declare(strict_types=1);

namespace Lattice\Media\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

final class DenyMediaPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return false;
    }
}
