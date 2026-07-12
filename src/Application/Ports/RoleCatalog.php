<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

use BackedEnum;

interface RoleCatalog
{
    /** @param iterable<string|BackedEnum> $permissions */
    public function create(string|BackedEnum $name, string $label, iterable $permissions = []): string;

    /** @param iterable<string|BackedEnum> $permissions */
    public function setPermissions(string $roleId, iterable $permissions): void;

    public function delete(string $roleId): void;
}
