<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\RevokeRole;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(RevokeRoleHandler::class)]
final readonly class RevokeRole implements Command
{
    public function __construct(
        public string $role,
        public string $subjectId,
        public string|null $scopeId = null,
    ) {}
}
