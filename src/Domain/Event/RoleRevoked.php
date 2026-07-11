<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Event;

use DateTimeImmutable;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Domain\DomainEvent;

final readonly class RoleRevoked implements DomainEvent
{
    public function __construct(
        public RoleId $roleId,
        public SubjectId $subjectId,
        public ScopeId|null $scopeId,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
