<?php
declare(strict_types=1);

namespace Workbench\App\Pages;

use Lattice\Core\Attributes\AsPage;
use Lattice\Http\Page;
use Lattice\Core\Enums\PageLayout;

#[AsPage(layout: PageLayout::App, middleware: ['web'])]
abstract class WorkbenchPage extends Page {}
