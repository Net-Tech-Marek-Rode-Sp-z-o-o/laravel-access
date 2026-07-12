<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

use BackedEnum;

interface RoleAssignments
{
    public function assign(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void;

    public function revoke(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void;
}
