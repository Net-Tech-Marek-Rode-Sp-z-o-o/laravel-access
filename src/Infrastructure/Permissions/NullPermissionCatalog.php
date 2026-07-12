<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Permissions;

use NetCode\Access\Application\Ports\PermissionCatalog;

final readonly class NullPermissionCatalog implements PermissionCatalog
{
    /** @return iterable<string> */
    public function all(): iterable
    {
        return [];
    }
}
