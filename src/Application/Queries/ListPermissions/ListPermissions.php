<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\ListPermissions;

use NetCode\Bus\Query\HandledBy;
use NetCode\Bus\Query\Query;

/** @implements Query<list<string>> */
#[HandledBy(ListPermissionsHandler::class)]
final readonly class ListPermissions implements Query {}
