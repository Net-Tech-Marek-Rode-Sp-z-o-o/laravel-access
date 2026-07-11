<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess\Mappers;

use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Infrastructure\DataAccess\Models\RoleModel;

final class RoleMapper
{
    public function toDomain(RoleModel $model, PermissionSet $permissions): Role
    {
        return Role::reconstitute(
            id: $model->id,
            name: new RoleName($model->name),
            label: $model->label,
            permissions: $permissions,
        );
    }

    public function hydrate(Role $role, RoleModel $model): void
    {
        $model->id = $role->id();
        $model->name = $role->name()->value();
        $model->label = $role->label();
    }
}
