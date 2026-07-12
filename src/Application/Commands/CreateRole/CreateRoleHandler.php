<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\CreateRole;

use NetCode\Access\Application\Services\DeclaredPermissions;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\Exceptions\RoleNameAlreadyTakenException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;

final readonly class CreateRoleHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RoleRepository $roles,
        private DeclaredPermissions $permissions,
    ) {}

    public function __invoke(
        CreateRole $command,
    ): string {
        $name = new RoleName($command->name);

        if ($this->roles->findByName($name) !== null) {
            throw RoleNameAlreadyTakenException::for($name);
        }

        $permissions = PermissionSet::from($command->permissions);

        $this->permissions->assertDeclared($permissions);

        $role = Role::create(
            id: $this->roles->nextId(),
            name: $name,
            label: $command->label,
            permissions: $permissions,
            now: $this->clock->now(),
        );

        $this->roles->save($role);

        return $role->id()->value();
    }
}
