<?php
declare(strict_types=1);

use Lattice\Media\Tests\BrowserTestCase;
use Lattice\Media\Tests\TestCase;

require_once __DIR__.'/Support/Browser.php';

uses(TestCase::class)->in('Feature');
uses(BrowserTestCase::class)->in('Browser');

/**
 * @return array<array-key, mixed>
 */
function wire(mixed $value): array
{
    return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true);
}
