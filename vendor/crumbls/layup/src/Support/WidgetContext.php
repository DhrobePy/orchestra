<?php

declare(strict_types=1);

namespace Crumbls\Layup\Support;

use Crumbls\Layup\Models\Page;

class WidgetContext
{
    public function __construct(
        public readonly ?Page $page,
        public readonly ?string $rowId,
        public readonly ?string $columnId,
        public readonly ?string $widgetId,
    ) {}
}
