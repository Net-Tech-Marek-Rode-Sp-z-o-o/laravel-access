<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use NetCode\Access\Application\Ports\Authorizer;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Application\Ports\ScopeContext;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Infrastructure\DataAccess\Tables;
use NetCode\Access\Tests\Support\FakeCurrentSubject;
use NetCode\Access\Tests\Support\FakeScopeContext;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AdminRolesTest extends TestCase
{
    use RefreshDatabase;

    private FakeCurrentSubject $subject;

    private FakeScopeContext $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FakeCurrentSubject;
        $this->scope = new FakeScopeContext;

        $this->app->instance(CurrentSubject::class, $this->subject);
        $this->app->instance(ScopeContext::class, $this->scope);
    }

    private function actingAsAdmin(): void
    {
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

    private function authorizer(): Authorizer
    {
        return $this->app->make(Authorizer::class);
    }

    private function createManager(): string
    {
        $roleId = $this->postJson('/access/roles', [
            'name' => 'manager',
            'label' => 'Manager',
        ])->json('data.id');

        $this->assertIsString($roleId);

        return $roleId;
    }

    #[Test]
    public function an_admin_creates_lists_permissions_assigns_and_revokes_a_role(): void
    {
        $this->actingAsAdmin();

        $created = $this->postJson('/access/roles', [
            'name' => 'manager',
            'label' => 'Manager',
        ]);

        $created->assertCreated()->assertJsonStructure(['data' => ['id']]);

        $roleId = $created->json('data.id');
        $this->assertIsString($roleId);

        $this->getJson('/access/roles')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.id', $roleId)
            ->assertJsonPath('data.1.name', 'manager')
            ->assertJsonPath('data.1.label', 'Manager')
            ->assertJsonPath('data.1.permissions', []);

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => [Permission::InvoicesIssue->value],
        ])->assertNoContent();

        $this->getJson('/access/roles')
            ->assertOk()
            ->assertJsonPath('data.1.permissions', [Permission::InvoicesIssue->value]);

        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $roleId,
        ])->assertNoContent();

        $this->assertTrue($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue));

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$roleId}")
            ->assertNoContent();

        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue));
    }

    #[Test]
    public function a_scoped_assignment_grants_only_inside_that_scope(): void
    {
        $this->actingAsAdmin();

        $roleId = $this->createManager();

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => [Permission::InvoicesIssue->value],
        ])->assertNoContent();

        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $roleId,
            'scope_id' => Ids::STORE_A,
        ])->assertNoContent();

        $this->assertTrue($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue, Ids::STORE_A));
        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue, Ids::STORE_B));
        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue));

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$roleId}?scope_id=".Ids::STORE_A)
            ->assertNoContent();

        $this->assertFalse($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue, Ids::STORE_A));
    }

    #[Test]
    public function a_revoke_without_a_scope_leaves_a_scoped_assignment_alone(): void
    {
        $this->actingAsAdmin();

        $roleId = $this->createManager();

        $this->putJson("/access/roles/{$roleId}/permissions", [
            'permissions' => [Permission::InvoicesIssue->value],
        ])->assertNoContent();

        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', [
            'role_id' => $roleId,
            'scope_id' => Ids::STORE_A,
        ])->assertNoContent();

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$roleId}")
            ->assertNoContent();

        $this->assertTrue($this->authorizer()->can(Ids::OTHER_SUBJECT, Permission::InvoicesIssue, Ids::STORE_A));
    }

    #[Test]
    public function a_deleted_role_disappears_from_the_catalogue(): void
    {
        $this->actingAsAdmin();

        $roleId = $this->createManager();

        $this->deleteJson("/access/roles/{$roleId}")->assertNoContent();

        $this->getJson('/access/roles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', Role::GlobalAdmin->value);
    }

    #[Test]
    public function a_subject_without_the_admin_permission_is_forbidden(): void
    {
        $this->app->make(RoleCatalog::class)->create(
            name: Role::Customer,
            label: 'Customer',
            permissions: [Permission::InvoicesIssue],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::OTHER_SUBJECT,
            role: Role::Customer,
        );

        $this->subject->becomes(Ids::OTHER_SUBJECT);

        $this->getJson('/access/roles')->assertForbidden();
        $this->postJson('/access/roles', ['name' => 'manager', 'label' => 'Manager'])->assertForbidden();
    }

    #[Test]
    public function an_anonymous_request_is_unauthenticated(): void
    {
        $this->getJson('/access/roles')->assertUnauthorized();
    }

    #[Test]
    public function the_admin_permission_is_scoped_like_any_other(): void
    {
        $this->app->make(RoleCatalog::class)->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::RolesManage],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::SUBJECT,
            role: Role::Manager,
            scopeId: Ids::STORE_A,
        );

        $this->subject->becomes(Ids::SUBJECT);

        $this->scope->enters(Ids::STORE_B);
        $this->getJson('/access/roles')->assertForbidden();

        $this->scope->enters(Ids::STORE_A);
        $this->getJson('/access/roles')->assertOk();
    }

    #[Test]
    public function invalid_input_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/access/roles', ['label' => 'Manager'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson('/access/roles', ['name' => 'Not A Slug', 'label' => 'Manager'])
            ->assertUnprocessable();

        $roleId = $this->createManager();

        $this->putJson("/access/roles/{$roleId}/permissions", ['permissions' => 'invoices.issue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions');

        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', ['role_id' => 'not-a-uuid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role_id');
    }

    #[Test]
    public function a_route_parameter_that_is_not_a_uuid_matches_no_route(): void
    {
        $this->actingAsAdmin();

        $this->deleteJson('/access/roles/not-a-uuid')->assertNotFound();
        $this->putJson('/access/roles/not-a-uuid/permissions', ['permissions' => []])->assertNotFound();
        $this->postJson('/access/subjects/not-a-uuid/roles', ['role_id' => RoleId::random()->value()])
            ->assertNotFound();
    }

    #[Test]
    public function the_whole_catalogue_is_listed_with_one_query(): void
    {
        $this->actingAsAdmin();

        $this->createManager();

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->getJson('/access/roles')->assertOk()->assertJsonCount(2, 'data');

        $reads = array_values(array_filter(
            DB::connection()->getQueryLog(),
            static fn (array $query): bool => str_contains((string) $query['query'], Tables::roles())
                && ! str_contains((string) $query['query'], Tables::roleUser()),
        ));

        $this->assertCount(1, $reads, 'the catalogue must be read with a single query');
        $this->assertStringContainsString(Tables::rolePermission(), (string) $reads[0]['query']);
    }

    #[Test]
    public function a_duplicate_role_name_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/access/roles', ['name' => 'manager', 'label' => 'Manager'])->assertCreated();
        $this->postJson('/access/roles', ['name' => 'manager', 'label' => 'Manager again'])->assertUnprocessable();
    }

    #[Test]
    public function an_unknown_role_is_not_found(): void
    {
        $this->actingAsAdmin();

        $unknown = RoleId::random()->value();

        $this->deleteJson("/access/roles/{$unknown}")->assertNotFound();
        $this->putJson("/access/roles/{$unknown}/permissions", ['permissions' => []])->assertNotFound();

        $this->postJson('/access/subjects/'.Ids::OTHER_SUBJECT.'/roles', ['role_id' => $unknown])
            ->assertNotFound();

        $this->deleteJson('/access/subjects/'.Ids::OTHER_SUBJECT."/roles/{$unknown}")
            ->assertNotFound();
    }
}
