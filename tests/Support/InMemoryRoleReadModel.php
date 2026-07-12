<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Access\Application\ReadModels\RoleView;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\ValueObjects\RoleId;

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
}
