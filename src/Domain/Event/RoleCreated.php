<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Event;

use DateTimeImmutable;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Domain\DomainEvent;

final readonly class RoleCreated implements DomainEvent
{
    public function __construct(
        public RoleId $roleId,
        public RoleName $name,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
