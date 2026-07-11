<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Application;

use DateTimeImmutable;
use NetCode\Access\Application\Command\CreateRole\CreateRole;
use NetCode\Access\Application\Command\CreateRole\CreateRoleHandler;
use NetCode\Access\Application\Command\DeleteRole\DeleteRole;
use NetCode\Access\Application\Command\DeleteRole\DeleteRoleHandler;
use NetCode\Access\Application\Command\SetRolePermissions\SetRolePermissions;
use NetCode\Access\Application\Command\SetRolePermissions\SetRolePermissionsHandler;
use NetCode\Access\Domain\Event\RoleCreated;
use NetCode\Access\Domain\Event\RolePermissionsChanged;
use NetCode\Access\Domain\Exception\RoleNameAlreadyTakenException;
use NetCode\Access\Domain\Exception\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Tests\Support\FixedClock;
use NetCode\Access\Tests\Support\InMemoryRoleRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleCatalogHandlersTest extends TestCase
{
    private InMemoryRoleRepository $roles;

    protected function setUp(): void
    {
        $this->roles = new InMemoryRoleRepository;
    }

    private function existingRole(string $name = 'manager'): Role
    {
        $role = Role::create(
            id: RoleId::random(),
            name: new RoleName($name),
            label: 'Manager',
            permissions: PermissionSet::from(['invoices.issue']),
            now: new DateTimeImmutable,
        );

        $this->roles->save($role);
        $this->roles->published = [];

        return $role;
    }

    #[Test]
    public function it_creates_a_role_with_its_permissions(): void
    {
        $handler = new CreateRoleHandler(clock: new FixedClock, roles: $this->roles);

        $roleId = $handler(new CreateRole(
            name: 'Manager',
            label: 'Manager',
            permissions: ['invoices.issue', 'users.invite'],
        ));

        $role = $this->roles->getById(RoleId::fromString($roleId));
        $this->assertSame('manager', $role->name()->value());
        $this->assertSame(['invoices.issue', 'users.invite'], $role->permissions()->toStrings());
        $this->assertInstanceOf(RoleCreated::class, $this->roles->published[0]);
    }

    #[Test]
    public function it_rejects_a_duplicate_role_name(): void
    {
        $this->existingRole('manager');
        $handler = new CreateRoleHandler(clock: new FixedClock, roles: $this->roles);

        $this->expectException(RoleNameAlreadyTakenException::class);

        $handler(new CreateRole(
            name: 'manager',
            label: 'Manager',
        ));
    }

    #[Test]
    public function it_sets_the_permissions_of_a_role(): void
    {
        $role = $this->existingRole();
        $handler = new SetRolePermissionsHandler(clock: new FixedClock, roles: $this->roles);

        $handler(new SetRolePermissions(
            roleId: $role->id()->value(),
            permissions: ['users.invite'],
        ));

        $this->assertSame(['users.invite'], $this->roles->getById($role->id())->permissions()->toStrings());
        $this->assertInstanceOf(RolePermissionsChanged::class, $this->roles->published[0]);
    }

    #[Test]
    public function setting_permissions_on_an_unknown_role_fails(): void
    {
        $handler = new SetRolePermissionsHandler(clock: new FixedClock, roles: $this->roles);

        $this->expectException(RoleNotFoundException::class);

        $handler(new SetRolePermissions(
            roleId: RoleId::random()->value(),
            permissions: [],
        ));
    }

    #[Test]
    public function it_deletes_a_role(): void
    {
        $role = $this->existingRole();
        $handler = new DeleteRoleHandler(roles: $this->roles);

        $handler(new DeleteRole(
            roleId: $role->id()->value(),
        ));

        $this->assertSame([], $this->roles->roles);
    }
}
