<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Access\Application\Commands\CreateRole\CreateRole;
use NetCode\Access\Application\Commands\CreateRole\CreateRoleHandler;
use NetCode\Access\Application\Commands\DeleteRole\DeleteRole;
use NetCode\Access\Application\Commands\DeleteRole\DeleteRoleHandler;
use NetCode\Access\Application\Commands\SetRolePermissions\SetRolePermissions;
use NetCode\Access\Application\Commands\SetRolePermissions\SetRolePermissionsHandler;
use NetCode\Access\Application\Exceptions\UnknownPermissionException;
use NetCode\Access\Application\Services\DeclaredPermissions;
use NetCode\Access\Domain\Events\RoleCreated;
use NetCode\Access\Domain\Events\RolePermissionsChanged;
use NetCode\Access\Domain\Exceptions\RoleNameAlreadyTakenException;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Tests\Support\FakePermissionCatalog;
use NetCode\Access\Tests\Support\FixedClock;
use NetCode\Access\Tests\Support\InMemoryRoleRepository;
use NetCode\Access\Tests\Support\Permission;
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
            permissions: PermissionSet::from([Permission::InvoicesIssue]),
            now: new DateTimeImmutable,
        );

        $this->roles->save($role);
        $this->roles->published = [];

        return $role;
    }

    private function declaring(Permission ...$permissions): DeclaredPermissions
    {
        return new DeclaredPermissions(new FakePermissionCatalog(...$permissions));
    }

    private function create(Permission ...$declared): CreateRoleHandler
    {
        return new CreateRoleHandler(
            clock: new FixedClock,
            roles: $this->roles,
            permissions: $this->declaring(...$declared),
        );
    }

    private function setPermissions(Permission ...$declared): SetRolePermissionsHandler
    {
        return new SetRolePermissionsHandler(
            clock: new FixedClock,
            roles: $this->roles,
            permissions: $this->declaring(...$declared),
        );
    }

    #[Test]
    public function it_creates_a_role_with_its_permissions(): void
    {
        $roleId = $this->create()(new CreateRole(
            name: 'Manager',
            label: 'Manager',
            permissions: [Permission::InvoicesIssue->value, Permission::UsersInvite->value],
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

        $this->expectException(RoleNameAlreadyTakenException::class);

        $this->create()(new CreateRole(
            name: 'manager',
            label: 'Manager',
        ));
    }

    #[Test]
    public function it_rejects_a_permission_the_application_does_not_declare(): void
    {
        $this->expectException(UnknownPermissionException::class);

        $this->create(Permission::InvoicesIssue)(new CreateRole(
            name: 'manager',
            label: 'Manager',
            permissions: [Permission::UsersInvite->value],
        ));
    }

    #[Test]
    public function it_sets_the_permissions_of_a_role(): void
    {
        $role = $this->existingRole();

        $this->setPermissions()(new SetRolePermissions(
            roleId: $role->id()->value(),
            permissions: [Permission::UsersInvite->value],
        ));

        $this->assertSame(['users.invite'], $this->roles->getById($role->id())->permissions()->toStrings());
        $this->assertInstanceOf(RolePermissionsChanged::class, $this->roles->published[0]);
    }

    #[Test]
    public function setting_a_permission_the_application_does_not_declare_fails(): void
    {
        $role = $this->existingRole();

        $this->expectException(UnknownPermissionException::class);

        $this->setPermissions(Permission::InvoicesIssue)(new SetRolePermissions(
            roleId: $role->id()->value(),
            permissions: ['invoices.delete'],
        ));
    }

    #[Test]
    public function an_empty_catalog_declares_nothing_and_validates_nothing(): void
    {
        $role = $this->existingRole();

        $this->setPermissions()(new SetRolePermissions(
            roleId: $role->id()->value(),
            permissions: ['anything.at.all'],
        ));

        $this->assertSame(['anything.at.all'], $this->roles->getById($role->id())->permissions()->toStrings());
    }

    #[Test]
    public function setting_permissions_on_an_unknown_role_fails(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->setPermissions()(new SetRolePermissions(
            roleId: RoleId::random()->value(),
            permissions: [],
        ));
    }

    #[Test]
    public function it_deletes_a_role(): void
    {
        $role = $this->existingRole();

        new DeleteRoleHandler(roles: $this->roles)(new DeleteRole(
            roleId: $role->id()->value(),
        ));

        $this->assertSame([], $this->roles->roles);
    }
}
