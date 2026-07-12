<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Contracts;

use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;

interface RoleRepository
{
    public function nextId(): RoleId;

    public function findByName(RoleName $name): Role|null;

    /** @throws RoleNotFoundException */
    public function getById(RoleId $id): Role;

    /** @throws RoleNotFoundException */
    public function getByName(RoleName $name): Role;

    public function save(Role $role): void;

    public function delete(Role $role): void;
}
