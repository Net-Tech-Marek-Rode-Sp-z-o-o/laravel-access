<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use BackedEnum;
use NetCode\Access\Application\Dto\RoleView;
use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;

final class InMemoryRoleReadModel implements RoleReadModel
{
    /** @var array<string, RoleView> */
    public array $roles = [];

    public function __construct(RoleView ...$roles)
    {
        foreach ($roles as $role) {
            $this->roles[$role->id] = $role;
        }
    }

    /** @return list<RoleView> */
    public function all(): array
    {
        return array_values($this->roles);
    }

    public function get(string $roleId): RoleView
    {
        $id = RoleId::fromString($roleId);

        return $this->roles[$id->value()] ?? throw RoleNotFoundException::withId($id);
    }

    public function getByName(string|BackedEnum $name): RoleView
    {
        $roleName = RoleName::from($name);

        foreach ($this->roles as $role) {
            if ($role->name === $roleName->value()) {
                return $role;
            }
        }

        throw RoleNotFoundException::withName($roleName);
    }
}
