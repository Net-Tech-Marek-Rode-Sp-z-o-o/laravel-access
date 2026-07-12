<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Domain\DomainEvent;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var array<string, Role> */
    public array $roles = [];

    /** @var list<DomainEvent> */
    public array $published = [];

    public function __construct(Role ...$roles)
    {
        foreach ($roles as $role) {
            $this->roles[$role->id()->value()] = $role;
        }
    }

    public function nextId(): RoleId
    {
        return RoleId::random();
    }

    public function findByName(RoleName $name): Role|null
    {
        foreach ($this->roles as $role) {
            if ($role->name()->equals($name)) {
                return $role;
            }
        }

        return null;
    }

    public function getById(RoleId $id): Role
    {
        return $this->roles[$id->value()] ?? throw RoleNotFoundException::withId($id);
    }

    public function getByName(RoleName $name): Role
    {
        return $this->findByName($name) ?? throw RoleNotFoundException::withName($name);
    }

    public function save(Role $role): void
    {
        $this->roles[$role->id()->value()] = $role;

        $this->published = [...$this->published, ...$role->releaseEvents()];
    }

    public function delete(Role $role): void
    {
        unset($this->roles[$role->id()->value()]);

        $this->published = [...$this->published, ...$role->releaseEvents()];
    }
}
