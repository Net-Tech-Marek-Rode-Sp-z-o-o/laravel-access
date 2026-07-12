<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

use NetCode\Access\Application\Dto\RoleView;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;

interface RoleReadModel
{
    /** @return list<RoleView> */
    public function all(): array;

    /** @throws RoleNotFoundException */
    public function get(string $roleId): RoleView;
}
