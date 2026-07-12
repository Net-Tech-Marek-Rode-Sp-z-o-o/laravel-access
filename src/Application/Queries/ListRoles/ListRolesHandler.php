<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\ListRoles;

use NetCode\Access\Application\Dto\RoleView;
use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Bus\Query\QueryHandler;

final readonly class ListRolesHandler implements QueryHandler
{
    public function __construct(
        private RoleReadModel $roles,
    ) {}

    /** @return list<RoleView> */
    public function __invoke(
        ListRoles $query,
    ): array {
        return $this->roles->all();
    }
}
