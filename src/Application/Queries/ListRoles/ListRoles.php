<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\ListRoles;

use NetCode\Access\Application\Dto\RoleView;
use NetCode\Bus\Query\HandledBy;
use NetCode\Bus\Query\Query;

/** @implements Query<list<RoleView>> */
#[HandledBy(ListRolesHandler::class)]
final readonly class ListRoles implements Query {}
