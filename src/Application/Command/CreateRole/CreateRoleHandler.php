<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Command\CreateRole;

use NetCode\Access\Domain\Contract\RoleRepository;
use NetCode\Access\Domain\Exception\RoleNameAlreadyTakenException;
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
    ) {}

    public function __invoke(
        CreateRole $command,
    ): string {
        $name = new RoleName($command->name);

        if ($this->roles->findByName($name) !== null) {
            throw RoleNameAlreadyTakenException::for($name);
        }

        $role = Role::create(
            id: $this->roles->nextId(),
            name: $name,
            label: $command->label,
            permissions: PermissionSet::from($command->permissions),
            now: $this->clock->now(),
        );

        $this->roles->save($role);

        return $role->id()->value();
    }
}
