<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Command\AssignRole;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(AssignRoleHandler::class)]
final readonly class AssignRole implements Command
{
    public function __construct(
        public string $role,
        public string $subjectId,
        public string|null $scopeId = null,
    ) {}
}
