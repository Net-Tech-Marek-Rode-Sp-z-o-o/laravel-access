<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Event;

use DateTimeImmutable;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Domain\DomainEvent;

final readonly class RolePermissionsChanged implements DomainEvent
{
    public function __construct(
        public RoleId $roleId,
        public PermissionSet $permissions,
        private DateTimeImmutable $occurredOn,
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
