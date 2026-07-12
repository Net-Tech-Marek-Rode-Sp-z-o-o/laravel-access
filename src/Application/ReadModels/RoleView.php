<?php

declare(strict_types=1);

namespace NetCode\Access\Application\ReadModels;

final readonly class RoleView
{
    /** @param list<string> $permissions */
    public function __construct(
        public string $id,
        public string $name,
        public string $label,
        public array $permissions,
    ) {}
}
