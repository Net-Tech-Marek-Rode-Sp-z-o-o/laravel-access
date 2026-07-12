<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\GetRole;

use NetCode\Access\Application\Dto\RoleView;
use NetCode\Bus\Query\HandledBy;
use NetCode\Bus\Query\Query;

/** @implements Query<RoleView> */
#[HandledBy(GetRoleHandler::class)]
final readonly class GetRole implements Query
{
    public function __construct(
        public string $roleId,
    ) {}
}
