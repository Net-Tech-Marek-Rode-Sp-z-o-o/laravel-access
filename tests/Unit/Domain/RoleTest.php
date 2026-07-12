<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Access\Domain\Events\RoleCreated;
use NetCode\Access\Domain\Events\RolePermissionsChanged;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionId;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    private function role(string ...$permissions): Role
    {
        return Role::create(
            id: RoleId::random(),
            name: new RoleName('manager'),
            label: 'Manager',
            permissions: PermissionSet::from($permissions),
            now: new DateTimeImmutable,
        );
    }

    #[Test]
    public function it_creates_a_role_and_records_the_event(): void
    {
        $id = RoleId::random();

        $role = Role::create(
            id: $id,
            name: new RoleName('manager'),
            label: 'Manager',
            permissions: PermissionSet::from(['invoices.issue']),
            now: new DateTimeImmutable,
        );

        $this->assertTrue($role->id()->equals($id));
        $this->assertSame('manager', $role->name()->value());
        $this->assertSame('Manager', $role->label());
        $this->assertSame(['invoices.issue'], $role->permissions()->toStrings());

        $events = $role->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleCreated::class, $events[0]);
    }

    #[Test]
    public function it_grants_only_the_permissions_it_holds(): void
    {
        $role = $this->role('invoices.issue');

        $this->assertTrue($role->allows(new PermissionId('invoices.issue')));
        $this->assertFalse($role->allows(new PermissionId('users.invite')));
    }

    #[Test]
    public function setting_permissions_replaces_the_set_and_records_the_event(): void
    {
        $role = $this->role('invoices.issue');
        $role->releaseEvents();

        $role->setPermissions(
            permissions: PermissionSet::from(['users.invite', 'users.remove']),
            now: new DateTimeImmutable,
        );

        $this->assertSame(['users.invite', 'users.remove'], $role->permissions()->toStrings());
        $this->assertFalse($role->allows(new PermissionId('invoices.issue')));

        $events = $role->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RolePermissionsChanged::class, $events[0]);
        $this->assertSame(['users.invite', 'users.remove'], $events[0]->permissions->toStrings());
    }

    #[Test]
    public function setting_the_same_permissions_records_nothing(): void
    {
        $role = $this->role('invoices.issue', 'users.invite');
        $role->releaseEvents();

        $role->setPermissions(
            permissions: PermissionSet::from(['users.invite', 'invoices.issue']),
            now: new DateTimeImmutable,
        );

        $this->assertSame([], $role->releaseEvents());
    }
}
