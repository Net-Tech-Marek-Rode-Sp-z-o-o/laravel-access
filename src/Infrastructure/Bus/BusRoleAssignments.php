<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Bus;

use BackedEnum;
use NetCode\Access\Application\Commands\AssignRole\AssignRole;
use NetCode\Access\Application\Commands\RevokeRole\RevokeRole;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Queries\GetRoleByName\GetRoleByName;
use NetCode\Bus\Command\CommandBus;
use NetCode\Bus\Query\QueryBus;

final readonly class BusRoleAssignments implements RoleAssignments
{
    public function __construct(
        private CommandBus $bus,
        private QueryBus $queries,
    ) {}

    public function assign(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void
    {
        $this->bus->dispatch(new AssignRole(
            roleId: $this->roleId($role),
            subjectId: $subjectId,
            scopeId: $scopeId,
        ));
    }

    public function revoke(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): void
    {
        $this->bus->dispatch(new RevokeRole(
            roleId: $this->roleId($role),
            subjectId: $subjectId,
            scopeId: $scopeId,
        ));
    }

    private function roleId(string|BackedEnum $role): string
    {
        return $this->queries->ask(new GetRoleByName(
            name: $role,
        ))->id;
    }
}
