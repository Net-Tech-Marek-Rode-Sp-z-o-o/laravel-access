<?php

declare(strict_types=1);

namespace NetCode\Access\Domain;

use DateTimeImmutable;
use NetCode\Access\Domain\Event\RoleCreated;
use NetCode\Access\Domain\Event\RolePermissionsChanged;
use NetCode\Access\Domain\ValueObjects\PermissionId;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Domain\AggregateRoot;

final class Role extends AggregateRoot
{
    private function __construct(
        private readonly RoleId $id,
        private readonly RoleName $name,
        private string $label,
        private PermissionSet $permissions,
    ) {}

    public static function create(
        RoleId $id,
        RoleName $name,
        string $label,
        PermissionSet $permissions,
        DateTimeImmutable $now,
    ): self {
        $role = new self(
            id: $id,
            name: $name,
            label: $label,
            permissions: $permissions,
        );

        $role->recordThat(new RoleCreated(
            roleId: $id,
            name: $name,
            occurredOn: $now,
        ));

        return $role;
    }

    public static function reconstitute(
        RoleId $id,
        RoleName $name,
        string $label,
        PermissionSet $permissions,
    ): self {
        return new self(
            id: $id,
            name: $name,
            label: $label,
            permissions: $permissions,
        );
    }

    public function setPermissions(
        PermissionSet $permissions,
        DateTimeImmutable $now,
    ): void {
        if ($this->permissions->equals($permissions)) {
            return;
        }

        $this->permissions = $permissions;

        $this->recordThat(new RolePermissionsChanged(
            roleId: $this->id,
            permissions: $permissions,
            occurredOn: $now,
        ));
    }

    public function relabel(
        string $label,
    ): void {
        $this->label = $label;
    }

    public function allows(
        PermissionId $permission,
    ): bool {
        return $this->permissions->contains($permission);
    }

    public function id(): RoleId
    {
        return $this->id;
    }

    public function name(): RoleName
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function permissions(): PermissionSet
    {
        return $this->permissions;
    }
}
