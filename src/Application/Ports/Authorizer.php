<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

use BackedEnum;

interface Authorizer
{
    public function can(string $subjectId, string|BackedEnum $permission, string|null $scopeId = null): bool;

    public function hasRole(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): bool;

    /** @return list<string> */
    public function rolesOf(string $subjectId, string|null $scopeId = null): array;

    /** @return list<string> */
    public function permissionsOf(string $subjectId, string|null $scopeId = null): array;
}
