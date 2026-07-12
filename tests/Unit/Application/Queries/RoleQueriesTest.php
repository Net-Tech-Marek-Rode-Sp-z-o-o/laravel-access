<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Application\Queries;

use NetCode\Access\Application\Queries\GetRole\GetRole;
use NetCode\Access\Application\Queries\GetRole\GetRoleHandler;
use NetCode\Access\Application\Queries\ListRoles\ListRoles;
use NetCode\Access\Application\Queries\ListRoles\ListRolesHandler;
use NetCode\Access\Application\ReadModels\RoleView;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Tests\Support\InMemoryRoleReadModel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleQueriesTest extends TestCase
{
    private function view(string $id, string $name): RoleView
    {
        return new RoleView(
            id: $id,
            name: $name,
            label: ucfirst($name),
            permissions: ['invoices.issue'],
        );
    }

    #[Test]
    public function it_lists_every_role(): void
    {
        $manager = $this->view(RoleId::random()->value(), 'manager');
        $customer = $this->view(RoleId::random()->value(), 'customer');

        $roles = new ListRolesHandler(new InMemoryRoleReadModel($manager, $customer))(new ListRoles);

        $this->assertSame([$manager, $customer], $roles);
    }

    #[Test]
    public function it_lists_nothing_when_no_role_exists(): void
    {
        $roles = new ListRolesHandler(new InMemoryRoleReadModel)(new ListRoles);

        $this->assertSame([], $roles);
    }

    #[Test]
    public function it_gets_a_role_by_id(): void
    {
        $manager = $this->view(RoleId::random()->value(), 'manager');

        $role = new GetRoleHandler(new InMemoryRoleReadModel($manager))(new GetRole(roleId: $manager->id));

        $this->assertSame($manager, $role);
    }

    #[Test]
    public function it_rejects_an_unknown_role(): void
    {
        $this->expectException(RoleNotFoundException::class);

        new GetRoleHandler(new InMemoryRoleReadModel)(new GetRole(roleId: RoleId::random()->value()));
    }
}
