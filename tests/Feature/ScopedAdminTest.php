<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Access\Application\Ports\Authorizer;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Application\Ports\ScopeContext;
use NetCode\Access\Tests\Support\FakeCurrentSubject;
use NetCode\Access\Tests\Support\FakeScopeContext;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ScopedAdminTest extends TestCase
{
    use RefreshDatabase;

    private FakeCurrentSubject $subject;

    private FakeScopeContext $scope;

    private string $roleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FakeCurrentSubject;
        $this->scope = new FakeScopeContext;

        $this->app->instance(CurrentSubject::class, $this->subject);
        $this->app->instance(ScopeContext::class, $this->scope);

        $catalog = $this->app->make(RoleCatalog::class);

        $catalog->create(
            name: Role::GlobalAdmin,
            label: 'Store admin',
            permissions: [Permission::RolesManage],
        );

        $this->roleId = $catalog->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::UsersRemove],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::SUBJECT,
            role: Role::GlobalAdmin,
            scopeId: Ids::STORE_A,
        );

        $this->subject->becomes(Ids::SUBJECT);
        $this->scope->enters(Ids::STORE_A);
    }

    private function authorizer(): Authorizer
    {
        return $this->app->make(Authorizer::class);
    }

    #[Test]
    public function a_scoped_admin_assigns_inside_its_own_scope(): void
    {
        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $this->roleId,
            'scope_id' => Ids::STORE_A,
        ])->assertNoContent();

        $this->assertTrue($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::UsersRemove, Ids::STORE_A));

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$this->roleId}?scope_id=".Ids::STORE_A)
            ->assertNoContent();

        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::UsersRemove, Ids::STORE_A));
    }

    #[Test]
    public function a_scoped_admin_cannot_grant_globally(): void
    {
        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $this->roleId,
        ])->assertForbidden();

        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::UsersRemove));
    }

    #[Test]
    public function a_scoped_admin_cannot_grant_into_another_scope(): void
    {
        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $this->roleId,
            'scope_id' => Ids::STORE_B,
        ])->assertForbidden();

        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::UsersRemove, Ids::STORE_B));
    }

    #[Test]
    public function a_scoped_admin_cannot_revoke_in_another_scope(): void
    {
        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::OTHER_SUBJECT,
            role: Role::Manager,
            scopeId: Ids::STORE_B,
        );

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$this->roleId}?scope_id=".Ids::STORE_B)
            ->assertForbidden();

        $this->assertTrue($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::UsersRemove, Ids::STORE_B));
    }

    #[Test]
    public function a_scoped_admin_cannot_touch_the_global_role_catalogue(): void
    {
        $this->postJson('/access/roles', ['name' => 'auditor', 'label' => 'Auditor'])->assertForbidden();

        $this->putJson("/access/roles/{$this->roleId}/permissions", [
            'permissions' => [Permission::InvoicesIssue->value],
        ])->assertForbidden();

        $this->deleteJson("/access/roles/{$this->roleId}")->assertForbidden();

        $this->getJson('/access/roles')->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_global_admin_still_grants_anywhere(): void
    {
        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::OTHER_SUBJECT,
            role: Role::GlobalAdmin,
        );

        $this->subject->becomes(Ids::OTHER_SUBJECT);
        $this->scope->enters(Ids::STORE_A);

        $this->postJson('/access/subjects/'.Ids::SUBJECT.'/roles', [
            'role_id' => $this->roleId,
            'scope_id' => Ids::STORE_B,
        ])->assertNoContent();

        $this->postJson('/access/subjects/'.Ids::SUBJECT.'/roles', [
            'role_id' => $this->roleId,
        ])->assertNoContent();

        $this->postJson('/access/roles', ['name' => 'auditor', 'label' => 'Auditor'])->assertCreated();
    }
}
