<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Bus;

use BackedEnum;
use NetCode\Access\Application\Commands\AssignRole\AssignRole;
use NetCode\Access\Application\Commands\RevokeRole\RevokeRole;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Bus\Command\CommandBus;

final readonly class BusRoleAssignments implements RoleAssignments
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function assign(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void
    {
        $this->bus->dispatch(new AssignRole(
            role: RoleName::from($role)->value(),
            subjectId: $subjectId,
            scopeId: $scopeId,
        ));
    }

    public function revoke(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void
    {
        $this->bus->dispatch(new RevokeRole(
            role: RoleName::from($role)->value(),
            subjectId: $subjectId,
            scopeId: $scopeId,
        ));
    }
}
