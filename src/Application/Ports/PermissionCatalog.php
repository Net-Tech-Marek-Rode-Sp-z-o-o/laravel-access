<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

use BackedEnum;

interface PermissionCatalog
{
    /** @return iterable<string|BackedEnum> */
    public function all(): iterable;
}
