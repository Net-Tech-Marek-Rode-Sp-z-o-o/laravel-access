<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\ListPermissions;

use NetCode\Access\Application\Services\DeclaredPermissions;
use NetCode\Bus\Query\QueryHandler;

final readonly class ListPermissionsHandler implements QueryHandler
{
    public function __construct(
        private DeclaredPermissions $permissions,
    ) {}

    /** @return list<string> */
    public function __invoke(
        ListPermissions $query,
    ): array {
        return $this->permissions->all()->toStrings();
    }
}
