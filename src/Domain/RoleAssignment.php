<?php

declare(strict_types=1);

namespace NetCode\Access\Domain;

use DateTimeImmutable;
use NetCode\Access\Domain\Events\RoleAssigned;
use NetCode\Access\Domain\Events\RoleRevoked;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Domain\AggregateRoot;

final class RoleAssignment extends AggregateRoot
{
    private function __construct(
        private readonly RoleId $roleId,
        private readonly SubjectId $subjectId,
        private readonly ScopeId|null $scopeId,
        private readonly DateTimeImmutable $grantedAt,
    ) {}

    public static function grant(
        RoleId $roleId,
        SubjectId $subjectId,
        ScopeId|null $scopeId,
        DateTimeImmutable $now,
    ): self {
        $assignment = new self(
            roleId: $roleId,
            subjectId: $subjectId,
            scopeId: $scopeId,
            grantedAt: $now,
        );

        $assignment->recordThat(new RoleAssigned(
            roleId: $roleId,
            subjectId: $subjectId,
            scopeId: $scopeId,
            occurredOn: $now,
        ));

        return $assignment;
    }

    public static function reconstitute(
        RoleId $roleId,
        SubjectId $subjectId,
        ScopeId|null $scopeId,
        DateTimeImmutable $grantedAt,
    ): self {
        return new self(
            roleId: $roleId,
            subjectId: $subjectId,
            scopeId: $scopeId,
            grantedAt: $grantedAt,
        );
    }

    public function revoke(
        DateTimeImmutable $now,
    ): void {
        $this->recordThat(new RoleRevoked(
            roleId: $this->roleId,
            subjectId: $this->subjectId,
            scopeId: $this->scopeId,
            occurredOn: $now,
        ));
    }

    public function isGlobal(): bool
    {
        return $this->scopeId === null;
    }

    public function grantsIn(
        ScopeId|null $scopeId,
    ): bool {
        return $this->scopeId === null || ($scopeId !== null && $this->scopeId->equals($scopeId));
    }

    public function roleId(): RoleId
    {
        return $this->roleId;
    }

    public function subjectId(): SubjectId
    {
        return $this->subjectId;
    }

    public function scopeId(): ScopeId|null
    {
        return $this->scopeId;
    }

    public function grantedAt(): DateTimeImmutable
    {
        return $this->grantedAt;
    }
}
