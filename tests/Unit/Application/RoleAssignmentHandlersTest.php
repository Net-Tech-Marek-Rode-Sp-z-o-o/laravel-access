<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Application;

use DateTimeImmutable;
use NetCode\Access\Application\Commands\AssignRole\AssignRole;
use NetCode\Access\Application\Commands\AssignRole\AssignRoleHandler;
use NetCode\Access\Application\Commands\RevokeRole\RevokeRole;
use NetCode\Access\Application\Commands\RevokeRole\RevokeRoleHandler;
use NetCode\Access\Domain\Events\RoleAssigned;
use NetCode\Access\Domain\Events\RoleRevoked;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Access\Tests\Support\FixedClock;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\InMemoryRoleAssignmentRepository;
use NetCode\Access\Tests\Support\InMemoryRoleRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleAssignmentHandlersTest extends TestCase
{
    private Role $role;

    private InMemoryRoleRepository $roles;

    private InMemoryRoleAssignmentRepository $assignments;

    protected function setUp(): void
    {
        $this->role = Role::create(
            id: RoleId::random(),
            name: new RoleName('manager'),
            label: 'Manager',
            permissions: PermissionSet::from(['invoices.issue']),
            now: new DateTimeImmutable,
        );
        $this->role->releaseEvents();

        $this->roles = new InMemoryRoleRepository($this->role);
        $this->assignments = new InMemoryRoleAssignmentRepository;
    }

    private function assign(): AssignRoleHandler
    {
        return new AssignRoleHandler(
            clock: new FixedClock,
            roles: $this->roles,
            assignments: $this->assignments,
        );
    }

    private function revoke(): RevokeRoleHandler
    {
        return new RevokeRoleHandler(
            clock: new FixedClock,
            roles: $this->roles,
            assignments: $this->assignments,
        );
    }

    #[Test]
    public function it_assigns_a_role_at_a_scope(): void
    {
        $this->assign()(new AssignRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
            scopeId: Ids::STORE_A,
        ));

        $assignment = $this->assignments->find($this->role->id(), new SubjectId(Ids::SUBJECT), new ScopeId(Ids::STORE_A));

        $this->assertNotNull($assignment);
        $this->assertSame(Ids::STORE_A, $assignment->scopeId()?->value());
        $this->assertInstanceOf(RoleAssigned::class, $this->assignments->published[0]);
    }

    #[Test]
    public function it_assigns_a_role_globally_when_no_scope_is_given(): void
    {
        $this->assign()(new AssignRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
        ));

        $assignment = $this->assignments->find($this->role->id(), new SubjectId(Ids::SUBJECT), null);

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->isGlobal());
    }

    #[Test]
    public function assigning_twice_is_idempotent(): void
    {
        $command = new AssignRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
            scopeId: Ids::STORE_A,
        );

        $this->assign()($command);
        $this->assign()($command);

        $this->assertCount(1, $this->assignments->assignments);
        $this->assertCount(1, $this->assignments->published);
    }

    #[Test]
    public function assigning_an_unknown_role_fails(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->assign()(new AssignRole(
            role: 'ghost',
            subjectId: Ids::SUBJECT,
        ));
    }

    #[Test]
    public function it_revokes_an_assignment_and_records_the_event(): void
    {
        $this->assign()(new AssignRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
            scopeId: Ids::STORE_A,
        ));
        $this->assignments->published = [];

        $this->revoke()(new RevokeRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
            scopeId: Ids::STORE_A,
        ));

        $this->assertNull($this->assignments->find($this->role->id(), new SubjectId(Ids::SUBJECT), new ScopeId(Ids::STORE_A)));
        $this->assertInstanceOf(RoleRevoked::class, $this->assignments->published[0]);
    }

    #[Test]
    public function revoking_leaves_assignments_in_other_scopes_untouched(): void
    {
        $this->assign()(new AssignRole(role: 'manager', subjectId: Ids::SUBJECT, scopeId: Ids::STORE_A));
        $this->assign()(new AssignRole(role: 'manager', subjectId: Ids::SUBJECT, scopeId: Ids::STORE_B));

        $this->revoke()(new RevokeRole(role: 'manager', subjectId: Ids::SUBJECT, scopeId: Ids::STORE_A));

        $this->assertNull($this->assignments->find($this->role->id(), new SubjectId(Ids::SUBJECT), new ScopeId(Ids::STORE_A)));
        $this->assertNotNull($this->assignments->find($this->role->id(), new SubjectId(Ids::SUBJECT), new ScopeId(Ids::STORE_B)));
    }

    #[Test]
    public function revoking_an_assignment_that_does_not_exist_is_a_no_op(): void
    {
        $this->revoke()(new RevokeRole(
            role: 'manager',
            subjectId: Ids::SUBJECT,
        ));

        $this->assertSame([], $this->assignments->assignments);
        $this->assertSame([], $this->assignments->published);
    }
}
