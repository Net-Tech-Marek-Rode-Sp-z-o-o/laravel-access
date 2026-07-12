<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Authorization;

use NetCode\Access\Domain\Events\RoleAssigned;
use NetCode\Access\Domain\Events\RolePermissionsChanged;
use NetCode\Access\Domain\Events\RoleRevoked;

final readonly class FlushResolvedPermissions
{
    public function __construct(
        private DatabaseAuthorizer $authorizer,
    ) {}

    public function __invoke(
        RoleAssigned|RoleRevoked|RolePermissionsChanged $event,
    ): void {
        $subjectId = $event instanceof RolePermissionsChanged ? null : $event->subjectId->value();

        $this->authorizer->flush($subjectId);
    }
}
