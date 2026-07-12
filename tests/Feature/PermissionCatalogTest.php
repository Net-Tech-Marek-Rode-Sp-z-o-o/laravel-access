<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\PermissionCatalog;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Tests\Support\FakeCurrentSubject;
use NetCode\Access\Tests\Support\FakePermissionCatalog;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    private FakeCurrentSubject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FakeCurrentSubject;

        $this->app->instance(CurrentSubject::class, $this->subject);

        $this->app->make(RoleCatalog::class)->create(
            name: Role::GlobalAdmin,
            label: 'Global admin',
            permissions: [Permission::RolesManage],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::SUBJECT,
            role: Role::GlobalAdmin,
        );

        $this->subject->becomes(Ids::SUBJECT);
    }

    private function declares(Permission ...$permissions): void
    {
        $this->app->instance(PermissionCatalog::class, new FakePermissionCatalog(...$permissions));
    }

    #[Test]
    public function it_lists_the_permissions_the_application_declares(): void
    {
        $this->declares(Permission::UsersInvite, Permission::InvoicesIssue);

        $this->getJson('/access/permissions')
            ->assertOk()
            ->assertExactJson(['data' => [Permission::InvoicesIssue->value, Permission::UsersInvite->value]]);
    }

    #[Test]
    public function it_lists_nothing_when_the_application_declares_no_catalog(): void
    {
        $this->getJson('/access/permissions')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    #[Test]
    public function the_permissions_of_a_role_cannot_be_set_to_an_undeclared_one(): void
    {
        $this->declares(Permission::InvoicesIssue);

        $roleId = $this->postJson('/access/roles', [
            'name' => 'manager',
            'label' => 'Manager',
        ])->json('data.id');

        $this->assertIsString($roleId);

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => [Permission::UsersInvite->value],
        ])->assertUnprocessable();

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => [Permission::InvoicesIssue->value],
        ])->assertNoContent();
    }

    #[Test]
    public function an_undeclared_catalog_accepts_any_permission(): void
    {
        $roleId = $this->postJson('/access/roles', [
            'name' => 'manager',
            'label' => 'Manager',
        ])->json('data.id');

        $this->assertIsString($roleId);

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => ['anything.at.all'],
        ])->assertNoContent();
    }
}
