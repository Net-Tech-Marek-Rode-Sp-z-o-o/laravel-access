<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Command\DeleteRole;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(DeleteRoleHandler::class)]
final readonly class DeleteRole implements Command
{
    public function __construct(
        public string $roleId,
    ) {}
}
