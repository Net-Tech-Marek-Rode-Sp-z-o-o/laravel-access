<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\CreateRole;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<string> */
#[HandledBy(CreateRoleHandler::class)]
final readonly class CreateRole implements Command
{
    /** @param list<string> $permissions */
    public function __construct(
        public string $name,
        public string $label,
        public array $permissions = [],
    ) {}
}
