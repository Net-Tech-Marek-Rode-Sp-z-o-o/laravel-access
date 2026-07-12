<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\GetRoleByName;

use BackedEnum;
use NetCode\Access\Application\Dto\RoleView;
use NetCode\Bus\Query\HandledBy;
use NetCode\Bus\Query\Query;

/** @implements Query<RoleView> */
#[HandledBy(GetRoleByNameHandler::class)]
final readonly class GetRoleByName implements Query
{
    public function __construct(
        public string|BackedEnum $name,
    ) {}
}
