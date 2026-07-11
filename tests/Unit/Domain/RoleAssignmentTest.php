<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Access\Domain\Event\RoleAssigned;
use NetCode\Access\Domain\Event\RoleRevoked;
use NetCode\Access\Domain\RoleAssignment;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoleAssignmentTest extends TestCase
{
    private function grant(ScopeId|null $scopeId): RoleAssignment
    {
        return RoleAssignment::grant(
            roleId: RoleId::random(),
            subjectId: new SubjectId('subject-1'),
            scopeId: $scopeId,
            now: new DateTimeImmutable,
        );
    }

    #[Test]
    public function granting_records_the_event(): void
    {
        $assignment = $this->grant(new ScopeId('store-a'));

        $events = $assignment->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleAssigned::class, $events[0]);
        $this->assertSame('store-a', $events[0]->scopeId?->value());
    }

    #[Test]
    public function revoking_records_the_event(): void
    {
        $assignment = $this->grant(null);
        $assignment->releaseEvents();

        $assignment->revoke(new DateTimeImmutable);

        $events = $assignment->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoleRevoked::class, $events[0]);
        $this->assertNull($events[0]->scopeId);
    }

    #[Test]
    public function a_global_grant_applies_in_every_scope(): void
    {
        $assignment = $this->grant(null);

        $this->assertTrue($assignment->isGlobal());
        $this->assertTrue($assignment->grantsIn(null));
        $this->assertTrue($assignment->grantsIn(new ScopeId('store-a')));
        $this->assertTrue($assignment->grantsIn(new ScopeId('store-b')));
    }

    #[Test]
    public function a_scoped_grant_applies_only_in_its_own_scope(): void
    {
        $assignment = $this->grant(new ScopeId('store-a'));

        $this->assertFalse($assignment->isGlobal());
        $this->assertTrue($assignment->grantsIn(new ScopeId('store-a')));
        $this->assertFalse($assignment->grantsIn(new ScopeId('store-b')));
        $this->assertFalse($assignment->grantsIn(null));
    }
}
