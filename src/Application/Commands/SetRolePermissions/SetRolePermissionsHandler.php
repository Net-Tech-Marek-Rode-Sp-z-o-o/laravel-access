<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\SetRolePermissions;

use NetCode\Access\Application\Services\DeclaredPermissions;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;

final readonly class SetRolePermissionsHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RoleRepository $roles,
        private DeclaredPermissions $permissions,
    ) {}

    public function __invoke(
        SetRolePermissions $command,
    ): void {
        $role = $this->roles->getById(RoleId::fromString($command->roleId));

        $permissions = PermissionSet::from($command->permissions);

        $this->permissions->assertDeclared($permissions);

        $role->setPermissions(
            permissions: $permissions,
            now: $this->clock->now(),
        );

        $this->roles->save($role);
    }
}
