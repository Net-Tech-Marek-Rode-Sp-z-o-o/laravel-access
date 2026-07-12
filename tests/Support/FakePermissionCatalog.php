<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Application\Ports\PermissionCatalog;

final class FakePermissionCatalog implements PermissionCatalog
{
    /** @var list<Permission> */
    private array $permissions;

    public function __construct(Permission ...$permissions)
    {
        $this->permissions = $permissions;
    }

    /** @return iterable<Permission> */
    public function all(): iterable
    {
        return $this->permissions;
    }
}
