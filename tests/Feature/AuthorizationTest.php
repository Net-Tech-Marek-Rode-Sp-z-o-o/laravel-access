<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use NetCode\Access\Application\Port\Authorizer;
use NetCode\Access\Application\Port\RoleAssignments;
use NetCode\Access\Application\Port\RoleCatalog;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const string SUBJECT = 'user-1';

    private Authorizer $authorizer;

    private RoleCatalog $catalog;

    private RoleAssignments $assignments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizer = $this->app->make(Authorizer::class);
        $this->catalog = $this->app->make(RoleCatalog::class);
        $this->assignments = $this->app->make(RoleAssignments::class);
    }

    private function seedRoles(): void
    {
        $this->catalog->create(
            name: Role::GlobalAdmin,
            label: 'Global admin',
            permissions: [Permission::UsersInvite, Permission::UsersRemove],
        );

        $this->catalog->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::InvoicesIssue],
        );
    }

    #[Test]
    public function a_role_grants_its_permissions_and_denies_the_rest(): void
    {
        $this->seedRoles();
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::UsersInvite, 'store-a'));
        $this->assertTrue($this->authorizer->hasRole(self::SUBJECT, Role::Manager, 'store-a'));
        $this->assertFalse($this->authorizer->hasRole(self::SUBJECT, Role::GlobalAdmin, 'store-a'));
        $this->assertSame(['manager'], $this->authorizer->rolesOf(self::SUBJECT, 'store-a'));
        $this->assertSame(['invoices.issue'], $this->authorizer->permissionsOf(self::SUBJECT, 'store-a'));
    }

    #[Test]
    public function a_subject_without_any_role_is_denied(): void
    {
        $this->seedRoles();

        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertSame([], $this->authorizer->rolesOf(self::SUBJECT, 'store-a'));
        $this->assertSame([], $this->authorizer->permissionsOf(self::SUBJECT));
    }

    #[Test]
    public function a_global_role_grants_in_every_scope(): void
    {
        $this->seedRoles();
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::GlobalAdmin);

        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::UsersInvite));
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::UsersInvite, 'store-a'));
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::UsersInvite, 'store-b'));
    }

    #[Test]
    public function a_scoped_role_is_isolated_to_its_own_scope(): void
    {
        $this->seedRoles();
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-b'));
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue));
        $this->assertSame([], $this->authorizer->rolesOf(self::SUBJECT, 'store-b'));
    }

    #[Test]
    public function the_same_subject_holds_different_roles_in_different_scopes(): void
    {
        $this->seedRoles();
        $this->catalog->create(name: Role::Customer, label: 'Customer', permissions: []);

        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Customer, scopeId: 'store-b');

        $this->assertSame(['manager'], $this->authorizer->rolesOf(self::SUBJECT, 'store-a'));
        $this->assertSame(['customer'], $this->authorizer->rolesOf(self::SUBJECT, 'store-b'));
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-b'));
    }

    #[Test]
    public function revoking_a_role_flips_the_check(): void
    {
        $this->seedRoles();

        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertDatabaseCount('access_role_user', 1);

        $this->assignments->revoke(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        $this->assertDatabaseCount('access_role_user', 0);
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
    }

    #[Test]
    public function changing_the_permissions_of_a_role_changes_what_it_grants(): void
    {
        $roleId = $this->catalog->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::InvoicesIssue],
        );
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));

        $this->catalog->setPermissions($roleId, [Permission::UsersInvite]);

        $this->assertDatabaseHas('access_role_permission', ['role_id' => $roleId, 'permission' => 'users.invite']);
        $this->assertDatabaseMissing('access_role_permission', ['role_id' => $roleId, 'permission' => 'invoices.issue']);
        $this->assertFalse($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::UsersInvite, 'store-a'));
    }

    #[Test]
    public function deleting_a_role_cascades_to_its_permissions_and_assignments(): void
    {
        $roleId = $this->catalog->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::InvoicesIssue],
        );
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        $this->catalog->delete($roleId);

        $this->assertDatabaseCount('access_roles', 0);
        $this->assertDatabaseCount('access_role_permission', 0);
        $this->assertDatabaseCount('access_role_user', 0);
    }

    #[Test]
    public function the_permission_set_is_resolved_with_a_single_query_and_memoized(): void
    {
        $this->seedRoles();
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::GlobalAdmin);
        $this->assignments->assign(subjectId: self::SUBJECT, role: Role::Manager, scopeId: 'store-a');

        DB::connection()->enableQueryLog();
        DB::connection()->flushQueryLog();

        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::InvoicesIssue, 'store-a'));
        $this->assertTrue($this->authorizer->can(self::SUBJECT, Permission::UsersInvite, 'store-a'));
        $this->assertTrue($this->authorizer->hasRole(self::SUBJECT, Role::Manager, 'store-a'));
        $this->assertSame(['global-admin', 'manager'], $this->authorizer->rolesOf(self::SUBJECT, 'store-a'));
        $this->assertSame(
            ['invoices.issue', 'users.invite', 'users.remove'],
            $this->authorizer->permissionsOf(self::SUBJECT, 'store-a'),
        );

        $this->assertCount(1, DB::connection()->getQueryLog());
    }
}
