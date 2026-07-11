<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Command\SetRolePermissions;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(SetRolePermissionsHandler::class)]
final readonly class SetRolePermissions implements Command
{
    /** @param list<string> $permissions */
    public function __construct(
        public string $roleId,
        public array $permissions,
    ) {}
}
