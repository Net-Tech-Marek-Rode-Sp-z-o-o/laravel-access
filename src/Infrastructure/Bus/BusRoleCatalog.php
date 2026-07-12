<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Bus;

use BackedEnum;
use NetCode\Access\Application\Commands\CreateRole\CreateRole;
use NetCode\Access\Application\Commands\DeleteRole\DeleteRole;
use NetCode\Access\Application\Commands\SetRolePermissions\SetRolePermissions;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Bus\Command\CommandBus;

final readonly class BusRoleCatalog implements RoleCatalog
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    /** @param iterable<string|BackedEnum> $permissions */
    public function create(string|BackedEnum $name, string $label, iterable $permissions = []): string
    {
        return $this->bus->dispatch(new CreateRole(
            name: RoleName::from($name)->value(),
            label: $label,
            permissions: PermissionSet::from($permissions)->toStrings(),
        ));
    }

    /** @param iterable<string|BackedEnum> $permissions */
    public function setPermissions(string $roleId, iterable $permissions): void
    {
        $this->bus->dispatch(new SetRolePermissions(
            roleId: $roleId,
            permissions: PermissionSet::from($permissions)->toStrings(),
        ));
    }

    public function delete(string $roleId): void
    {
        $this->bus->dispatch(new DeleteRole(
            roleId: $roleId,
        ));
    }
}
